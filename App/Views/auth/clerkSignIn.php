<?php
// Login via Clerk. Espera $clerkPortal = 'admin' | 'docente' | 'aluno'
$clerkFrontendApi = \App\Auth\Clerk::frontendApi();
$clerkJsVersion = getenv('CLERK_JS_VERSION') ?: '5';
?>

<div class="form-row mt-2 form-body">
    <div class="col-12 d-flex justify-content-center mt-3">
        <div id="clerk-sign-in"></div>
    </div>
    <div class="col-md-10 mx-auto mt-3">
        <div id="clerk-status" class="alert alert-warning text-center d-none" role="alert"></div>
    </div>
</div>

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
        let opening = false;

        function showError(code) {
            statusBox.innerHTML = '';
            statusBox.appendChild(document.createTextNode(messages[code] || 'Não foi possível entrar.'));
            const retry = document.createElement('button');
            retry.className = 'btn btn-link d-block mx-auto';
            retry.textContent = 'Entrar com outra conta';
            retry.onclick = async () => { await window.Clerk.signOut(); location.reload(); };
            statusBox.appendChild(retry);
            statusBox.classList.remove('d-none');
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

        window.addEventListener('load', async function () {
            const clerk = window.Clerk;
            await clerk.load();

            const params = new URLSearchParams(window.location.search);
            if (params.has('logout')) {
                await clerk.signOut();
                history.replaceState(null, '', window.location.pathname);
            }

            if (clerk.user) {
                openSession();
                return;
            }

            clerk.mountSignIn(document.getElementById('clerk-sign-in'), {
                withSignUp: true,
                forceRedirectUrl: window.location.pathname,
                signUpForceRedirectUrl: window.location.pathname,
                appearance: {
                    variables: { colorPrimary: '#1868E8', borderRadius: '8px' }
                }
            });

            clerk.addListener(function (resources) {
                if (resources.user) openSession();
            });
        });
    })();
</script>
