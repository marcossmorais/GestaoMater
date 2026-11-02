// Mater — app instalável: registra o service worker e mostra o botão "Instalar app" quando o navegador permite
(function () {

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }

    const buttons = Array.from(document.querySelectorAll('[data-install-app]'));
    const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const ios = /iphone|ipad|ipod/i.test(navigator.userAgent);
    let prompt = null;

    function showButtons(visible) {
        buttons.forEach((b) => { b.hidden = !visible; });
    }

    if (standalone) return showButtons(false);

    // iPhone/iPad não têm o aviso de instalação: o botão explica o caminho pelo Safari
    if (ios) showButtons(true);

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        prompt = event;
        showButtons(true);
    });

    window.addEventListener('appinstalled', () => showButtons(false));

    buttons.forEach((button) => button.addEventListener('click', async (event) => {
        event.preventDefault();
        if (prompt) {
            prompt.prompt();
            await prompt.userChoice;
            prompt = null;
            showButtons(false);
        } else if (ios) {
            window.alert('No Safari, toque em Compartilhar (quadrado com seta) e depois em "Adicionar à Tela de Início".');
        }
    }));

})();
