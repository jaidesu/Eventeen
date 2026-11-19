/*
 * Service worker do Eventeen (PWA).
 *
 * - Páginas: sempre buscadas na rede, nunca guardadas em cache (têm dados pessoais e
 *   tokens de segurança). Sem internet, exibe a página offline.html.
 * - CSS e JS: rede primeiro; o cache só é usado quando não há conexão.
 * - Imagens e ícones: cache primeiro, atualizado em segundo plano.
 *
 * Ao alterar a lista de arquivos pré-carregados, aumente a VERSAO para descartar o cache antigo.
 */
const VERSAO = 'eventeen-v1';
const PAGINA_OFFLINE = new URL('offline.html', self.location).href;
const PRE_CARREGAR = [
    'offline.html',
    'css/style.css',
    'css/gestao.css',
    'js/pwa.js',
    'favicon.ico',
    'manifest.json',
    'img/logo-eventeen-web.png',
    'img/eventeen-marca.png',
    'img/icones/icone-192.png',
    'img/icones/favicon-32.png',
].map((caminho) => new URL(caminho, self.location).href);

self.addEventListener('install', (evento) => {
    evento.waitUntil(
        caches.open(VERSAO)
            .then((cache) => cache.addAll(PRE_CARREGAR))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (evento) => {
    evento.waitUntil((async () => {
        const nomes = await caches.keys();
        await Promise.all(nomes.filter((nome) => nome.startsWith('eventeen-') && nome !== VERSAO).map((nome) => caches.delete(nome)));
        if (self.registration.navigationPreload) {
            await self.registration.navigationPreload.enable();
        }
        await self.clients.claim();
    })());
});

function ehEstatico(url) {
    return /\.(?:css|js|png|jpe?g|gif|svg|webp|ico|woff2?)$/i.test(url.pathname) || url.pathname.endsWith('/manifest.json');
}

async function guardarNoCache(requisicao, resposta) {
    if (resposta && resposta.ok && resposta.type === 'basic') {
        const cache = await caches.open(VERSAO);
        await cache.put(requisicao, resposta.clone());
    }
    return resposta;
}

async function redePrimeiro(requisicao) {
    try {
        return await guardarNoCache(requisicao, await fetch(requisicao));
    } catch (erro) {
        const guardada = await caches.match(requisicao);
        if (guardada) {
            return guardada;
        }
        throw erro;
    }
}

async function cachePrimeiro(evento) {
    const guardada = await caches.match(evento.request);
    const atualizacao = fetch(evento.request).then((resposta) => guardarNoCache(evento.request, resposta));
    if (guardada) {
        evento.waitUntil(atualizacao.catch(() => undefined));
        return guardada;
    }
    return atualizacao;
}

self.addEventListener('fetch', (evento) => {
    const requisicao = evento.request;
    if (requisicao.method !== 'GET') {
        return;
    }
    const url = new URL(requisicao.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (requisicao.mode === 'navigate') {
        evento.respondWith((async () => {
            try {
                const preCarregada = await evento.preloadResponse;
                return preCarregada || await fetch(requisicao);
            } catch (erro) {
                return (await caches.match(PAGINA_OFFLINE)) || Response.error();
            }
        })());
        return;
    }

    if (!ehEstatico(url)) {
        return; // PDFs de certificado e demais respostas dinâmicas seguem direto para a rede.
    }

    evento.respondWith(/\.(?:css|js)$/i.test(url.pathname) ? redePrimeiro(requisicao) : cachePrimeiro(evento));
});
