<?php
// Login via Clerk. Espera $clerkPortal = 'admin' | 'docente' | 'aluno'
// Opcionais: $clerkAllowSignUp (padrão true) e $clerkRequireFreshLogin (padrão false)
$clerkAllowSignUp = $clerkAllowSignUp ?? true;
$clerkRequireFreshLogin = $clerkRequireFreshLogin ?? false;
$clerkFrontendApi = \App\Auth\Clerk::frontendApi();
$clerkJsVersion = getenv('CLERK_JS_VERSION') ?: '5';
?>

<div id="clerk-status" class="alert" role="alert" hidden></div>
<div id="clerk-sign-in"><p class="loading">Carregando…</p></div>

<script
    async
    crossorigin="anonymous"
    data-clerk-publishable-key="<?= htmlspecialchars(\App\Auth\Clerk::publishableKey()) ?>"
    src="https://<?= htmlspecialchars($clerkFrontendApi) ?>/npm/@clerk/clerk-js@<?= htmlspecialchars($clerkJsVersion) ?>/dist/clerk.browser.js"
    type="text/javascript"></script>

<script>
    (function () {
        const portal = <?= json_encode($clerkPortal) ?>;
        const allowSignUp = <?= json_encode((bool) $clerkAllowSignUp) ?>;
        const requireFreshLogin = <?= json_encode((bool) $clerkRequireFreshLogin) ?>;
        const statusBox = document.getElementById('clerk-status');
        const messages = {
            'sem-cadastro': 'Seu e-mail não está vinculado a nenhum cadastro deste portal. Procure a secretaria da escola.',
            'sem-matricula': 'Não encontramos uma matrícula ativa para o seu cadastro.',
            'conta-desativada': 'Sua conta está desativada.',
            'token-invalido': 'Não foi possível validar o login. Tente novamente.'
        };

        // Visual da Mater no componente do Clerk
        const appearance = {
            layout: { logoPlacement: 'none', socialButtonsVariant: 'blockButton', unsafe_disableDevelopmentModeWarnings: true },
            variables: {
                colorPrimary: '#1868E8',
                colorText: '#14213D',
                colorTextSecondary: '#5B6B84',
                colorInputBackground: '#FFFFFF',
                colorInputText: '#14213D',
                fontFamily: 'Inter, system-ui, sans-serif',
                fontSize: '15px',
                borderRadius: '10px'
            },
            elements: {
                rootBox: { width: '100%' },
                cardBox: { width: '100%', boxShadow: 'none', border: '0', overflow: 'visible' },
                card: { boxShadow: 'none', border: '0', padding: '4px', background: 'transparent' },
                header: { display: 'none' },
                formButtonPrimary: { fontSize: '14px', fontWeight: 600, textTransform: 'none', padding: '11px 18px' },
                formFieldInput: { padding: '11px 14px' },
                footer: { background: 'transparent', marginTop: '8px' },
                // sem cadastro: some o "Não tem conta? Cadastre-se"
                footerAction: allowSignUp ? { padding: '12px 0 0' } : { display: 'none' }
            }
        };

        let opening = false;

        function showError(code) {
            statusBox.textContent = messages[code] || 'Não foi possível entrar.';
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.textContent = 'Entrar com outra conta';
            // sem redirectUrl o Clerk manda para "/" (afterSignOutUrl padrão) em vez de voltar a esta tela
            retry.onclick = () => window.Clerk.signOut({ redirectUrl: window.location.pathname });
            statusBox.appendChild(retry);
            statusBox.hidden = false;

            // limpa o "Entrando…" e monta o Clerk para permitir trocar de conta
            const target = document.getElementById('clerk-sign-in');
            target.innerHTML = '';
        }

        async function openSession() {
            if (opening) return;
            opening = true;
            const token = await window.Clerk.session.getToken();
            const response = await fetch('/auth/clerk/sessao', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token, portal })
            });
            const data = await response.json().catch(() => ({}));
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                opening = false;
                showError(data.error);
            }
        }

        async function loadLocalization() {
            try {
                // tradução servida pelo próprio site (antes vinha do jsDelivr, sem versão fixa, na tela onde se digita a senha)
                const mod = await import(<?= json_encode(asset('/assets/js/vendor/clerk-localization-pt-BR.js')) ?>);
                return mod.ptBR;
            } catch (e) {
                return undefined; // sem tradução, o Clerk fica em inglês
            }
        }

        window.addEventListener('load', async function () {
            const clerk = window.Clerk;
            const [localization] = await Promise.all([loadLocalization()]);
            await clerk.load({ localization, appearance });

            const params = new URLSearchParams(window.location.search);
            // ?entrou=1: o Clerk acabou de devolver o usuário a esta tela depois do login feito aqui
            const justSignedIn = params.get('entrou') === '1';
            if (params.toString()) history.replaceState(null, '', window.location.pathname);

            // admin exige login explícito: descarta a sessão do Clerk vinda de outro portal, mas não a que
            // acabou de ser aberta nesta tela (antes descartava, e o signOut sem destino levava para "/")
            if (params.has('logout') || (requireFreshLogin && clerk.user && !justSignedIn)) {
                await clerk.signOut({ redirectUrl: window.location.pathname });
                return;
            }

            const target = document.getElementById('clerk-sign-in');
            target.innerHTML = '';

            if (clerk.user) {
                target.innerHTML = '<p class="loading">Entrando…</p>';
                openSession();
                return;
            }

            // depois do login/cadastro o Clerk volta para esta tela marcada com ?entrou=1 (ver acima)
            const back = window.location.pathname + '?entrou=1';
            clerk.mountSignIn(target, {
                withSignUp: allowSignUp,
                forceRedirectUrl: back,
                signUpForceRedirectUrl: back
            });

            clerk.addListener(function (resources) {
                if (resources.user) openSession();
            });
        });
    })();
</script>
