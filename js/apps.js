/*
 * Caixa "Apps da Eventeen" no topo da landing page (index.php).
 * Abre e fecha pelo botão de pontinhos; fecha com Esc, clique fora ou ao escolher um app.
 */
(function () {
    var botao = document.querySelector('.lp-apps__botao');
    var painel = document.getElementById('lp-apps-painel');
    if (!botao || !painel) {
        return;
    }

    var temporizador = null;

    function estaAberto() {
        return botao.getAttribute('aria-expanded') === 'true';
    }

    function abrir() {
        window.clearTimeout(temporizador);
        painel.hidden = false;
        botao.setAttribute('aria-expanded', 'true');
        // Espera um quadro para a animação de entrada partir do estado fechado.
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                painel.classList.add('is-aberto');
            });
        });
        var primeiro = painel.querySelector('a');
        if (primeiro) {
            primeiro.focus({ preventScroll: true });
        }
    }

    function fechar(devolverFoco) {
        if (!estaAberto()) {
            return;
        }
        botao.setAttribute('aria-expanded', 'false');
        painel.classList.remove('is-aberto');
        temporizador = window.setTimeout(function () {
            painel.hidden = true;
        }, 180);
        if (devolverFoco) {
            botao.focus();
        }
    }

    botao.addEventListener('click', function () {
        if (estaAberto()) {
            fechar(false);
        } else {
            abrir();
        }
    });

    document.addEventListener('click', function (evento) {
        if (estaAberto() && !painel.contains(evento.target) && !botao.contains(evento.target)) {
            fechar(false);
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && estaAberto()) {
            fechar(true);
        }
    });

    // Ao sair da caixa com Tab, ela fecha.
    painel.addEventListener('focusout', function (evento) {
        if (evento.relatedTarget && !painel.contains(evento.relatedTarget) && evento.relatedTarget !== botao) {
            fechar(false);
        }
    });

    painel.addEventListener('click', function (evento) {
        if (evento.target.closest('a')) {
            fechar(false);
        }
    });
})();
