/*
 * PWA do Eventeen: registra o service worker e controla o botão "Instalar o app".
 * O botão só aparece nas páginas que têm o bloco [data-instalar-app] e quando o
 * navegador permite a instalação; no iPhone/iPad, mostra a instrução manual.
 */
(function () {
    'use strict';

    var script = document.currentScript;
    var raiz = script ? new URL('../', script.src) : new URL('./', window.location.href);

    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(new URL('sw.js', raiz).href, { scope: raiz.pathname })
                .catch(function () { /* sem PWA, o site continua funcionando normalmente */ });
        });
    }

    var instalado = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    var pedidoInstalacao = null;

    function blocos() {
        return document.querySelectorAll('[data-instalar-app]');
    }

    function mostrar(seletor) {
        blocos().forEach(function (bloco) {
            var alvo = bloco.querySelector(seletor);
            if (alvo) {
                alvo.hidden = false;
                bloco.hidden = false;
            }
        });
    }

    function esconderTudo() {
        blocos().forEach(function (bloco) { bloco.hidden = true; });
    }

    window.addEventListener('beforeinstallprompt', function (evento) {
        // Só substitui o aviso do navegador quando a página tem o botão próprio.
        if (instalado || blocos().length === 0) {
            return;
        }
        evento.preventDefault();
        pedidoInstalacao = evento;
        mostrar('[data-instalar-app-botao]');
    });

    window.addEventListener('appinstalled', function () {
        pedidoInstalacao = null;
        esconderTudo();
    });

    document.addEventListener('click', function (evento) {
        var botao = evento.target.closest ? evento.target.closest('[data-instalar-app-botao]') : null;
        if (!botao || !pedidoInstalacao) {
            return;
        }
        pedidoInstalacao.prompt();
        pedidoInstalacao.userChoice.finally(function () {
            pedidoInstalacao = null;
            esconderTudo();
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        var ios = /iphone|ipad|ipod/i.test(window.navigator.userAgent)
            || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
        if (!instalado && ios) {
            mostrar('[data-instalar-app-ios]');
        }
    });
})();
