<?php
// Login via Clerk. Espera $clerkPortal = 'admin' | 'docente' | 'aluno'
$clerkFrontendApi = \App\Auth\Clerk::frontendApi();
$clerkJsVersion = getenv('CLERK_JS_VERSION') ?: '5';
$clerkLocalizationVersion = getenv('CLERK_LOCALIZATIONS_VERSION') ?: '3';
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
                footerAction: { padding: '12px 0 0' }
            }
        };

        let opening = false;

        function showError(code) {
            statusBox.textContent = messages[code] || 'Não foi possível entrar.';
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.textContent = 'Entrar com outra conta';
            retry.onclick = async () => { await window.Clerk.signOut(); location.reload(); };
            statusBox.appendChild(retry);
            statusBox.hidden = false;
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
                const mod = await import('https://cdn.jsdelivr.net/npm/@clerk/localizations@<?= htmlspecialchars($clerkLocalizationVersion) ?>/+esm');
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
            if (params.has('logout')) {
                await clerk.signOut();
                history.replaceState(null, '', window.location.pathname);
            }

            const target = document.getElementById('clerk-sign-in');
            target.innerHTML = '';

            if (clerk.user) {
                target.innerHTML = '<p class="loading">Entrando…</p>';
                openSession();
                return;
            }

            clerk.mountSignIn(target, {
                withSignUp: true,
                forceRedirectUrl: window.location.pathname,
                signUpForceRedirectUrl: window.location.pathname
            });

            clerk.addListener(function (resources) {
                if (resources.user) openSession();
            });
        });
    })();
</script>
