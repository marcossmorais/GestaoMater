// Mater — service worker mínimo para o app instalável.
// Guarda só arquivos estáticos (CSS, JS, fontes, imagens). Páginas e dados de alunos nunca vão para o cache (LGPD).
const CACHE = 'mater-static-v1';
const STATIC = /\/(assets|node_modules)\/.+\.(css|js|woff2?|png|jpe?g|svg|gif)(\?.*)?$/;

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
        .then(() => self.clients.claim()));
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);

    if (url.origin === self.location.origin && STATIC.test(url.pathname) && !url.pathname.includes('ProfilePhotos')) {
        // arquivo estático: responde do cache e atualiza em segundo plano
        event.respondWith(caches.open(CACHE).then(async (cache) => {
            const cached = await cache.match(request);
            const network = fetch(request).then((response) => {
                if (response.ok) cache.put(request, response.clone());
                return response;
            }).catch(() => cached);
            return cached || network;
        }));
        return;
    }

    if (request.mode === 'navigate') {
        // sem internet: aviso simples em vez da tela de erro do navegador
        event.respondWith(fetch(request).catch(() => new Response(
            '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
            '<title>Mater — sem conexão</title><body style="font-family:system-ui;padding:32px;text-align:center;color:#14213D">' +
            '<h2>Sem conexão</h2><p>Verifique a internet e tente de novo.</p><button onclick="location.reload()" style="min-height:44px;padding:0 20px;border-radius:12px;border:0;background:#1868E8;color:#fff;font-weight:600">Tentar de novo</button>',
            { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        )));
    }
});
