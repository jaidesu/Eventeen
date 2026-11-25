<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

// Quem já entrou vai direto para a sua área, como antes.
if (!empty($_SESSION['usuario'])) {
    header('Location: ' . profileHomePath((string) ($_SESSION['usuario']['perfil'] ?? 'participante')));
    exit;
}

// Próximos eventos publicados (dados reais do banco). Se a consulta falhar, a seção apenas não aparece.
$proximosEventos = [];
try {
    $proximosEventos = $pdo->query(
        "SELECT e.id, e.titulo, e.categoria, e.local, e.data_inicio, e.capacidade, e.valor_inscricao,
                (SELECT COUNT(*) FROM inscricoes i WHERE i.evento_id = e.id AND i.status = 'ativa') AS inscritos
         FROM eventos e
         WHERE e.status = 'publicado' AND e.data_fim >= NOW()
         ORDER BY e.data_inicio
         LIMIT 3"
    )->fetchAll();
} catch (Throwable) {
    $proximosEventos = [];
}

const LANDING_MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

function landingDataEvento(string $data): array
{
    $momento = new DateTimeImmutable($data);
    return [
        'dia' => $momento->format('d'),
        'mes' => LANDING_MESES[(int) $momento->format('n') - 1],
        'hora' => $momento->format('H\hi'),
        'iso' => $momento->format('Y-m-d\TH:i'),
    ];
}

function landingVagas(array $evento): string
{
    if ($evento['capacidade'] === null) {
        return 'Vagas ilimitadas';
    }
    $restantes = max(0, (int) $evento['capacidade'] - (int) $evento['inscritos']);
    return match (true) {
        $restantes === 0 => 'Vagas esgotadas',
        $restantes === 1 => '1 vaga restante',
        default => $restantes . ' vagas restantes',
    };
}

function landingValor(array $evento): string
{
    $valor = (float) $evento['valor_inscricao'];
    return $valor > 0 ? 'R$ ' . number_format($valor, 2, ',', '.') : 'Gratuito';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php renderHeadPagina('Eventeen: do convite ao certificado | Eventos acadêmicos da Fatec Ourinhos', ['titulo_completo' => true, 'indexar' => true, 'canonico' => '/', 'dados_estruturados' => true]); ?>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/landing.css">
</head>
<body class="landing">
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

    <header class="lp-topo">
        <div class="lp-container lp-topo__barra">
            <a class="lp-topo__marca" href="index.php" aria-label="Eventeen - página inicial">
                <img src="img/logo-eventeen-web.png" alt="Eventeen" width="150" height="54">
            </a>
            <nav class="lp-topo__menu" aria-label="Seções da página">
                <a href="#como-funciona">Como funciona</a>
                <a href="#recursos">Recursos</a>
                <?php if ($proximosEventos !== []): ?><a href="#eventos">Eventos</a><?php endif; ?>
                <a href="#certificados">Certificados</a>
            </nav>
            <div class="lp-topo__acoes">
                <a class="lp-link-entrar" href="entrar.php">Entrar</a>
                <a class="lp-botao lp-botao--pequeno" href="cadastro.php">Criar conta</a>
            </div>
        </div>
    </header>

    <main id="conteudo">
        <!-- Abertura -->
        <section class="lp-hero" aria-labelledby="lp-titulo">
            <div class="lp-container lp-hero__grade">
                <div class="lp-hero__texto">
                    <p class="lp-selo"><span class="lp-selo__ponto" aria-hidden="true"></span> Eventos acadêmicos da Fatec Ourinhos</p>
                    <h1 id="lp-titulo">Do convite ao <span class="lp-destaque">certificado</span>.</h1>
                    <p class="lp-hero__resumo">Encontre os eventos da faculdade, garanta sua vaga em poucos cliques, registre sua presença e receba o certificado em PDF, tudo em um só lugar.</p>
                    <div class="lp-hero__acoes">
                        <a class="lp-botao" href="cadastro.php">Criar minha conta <span aria-hidden="true">→</span></a>
                        <a class="lp-botao lp-botao--contorno" href="entrar.php">Já tenho conta</a>
                    </div>
                    <ul class="lp-hero__garantias">
                        <li>Inscrição sem filas</li>
                        <li>Certificado com validação pública</li>
                        <li>Funciona no celular</li>
                    </ul>
                </div>

                <div class="lp-hero__visual" aria-hidden="true">
                    <div class="lp-cartao lp-cartao--evento">
                        <div class="lp-cartao__topo">
                            <span class="lp-icone lp-icone--calendario"></span>
                            <div>
                                <strong>Seu próximo evento</strong>
                                <span>Programação, local e vagas</span>
                            </div>
                        </div>
                        <div class="lp-linhas">
                            <span></span><span></span><span class="lp-linhas__curta"></span>
                        </div>
                        <span class="lp-pilula lp-pilula--verde">● Inscrição confirmada</span>
                    </div>
                    <div class="lp-cartao lp-cartao--presenca">
                        <span class="lp-icone lp-icone--check"></span>
                        <div>
                            <strong>Presença registrada</strong>
                            <span>por atividade</span>
                        </div>
                    </div>
                    <div class="lp-cartao lp-cartao--certificado">
                        <div class="lp-certificado__faixa"></div>
                        <span class="lp-certificado__rotulo">Certificado</span>
                        <div class="lp-linhas lp-linhas--centro">
                            <span></span><span class="lp-linhas__curta"></span>
                        </div>
                        <span class="lp-certificado__codigo">XXXX-XXXX-XXXX-XXXX</span>
                        <span class="lp-certificado__selo"></span>
                    </div>
                    <div class="lp-bolha lp-bolha--1"></div>
                    <div class="lp-bolha lp-bolha--2"></div>
                </div>
            </div>
        </section>

        <!-- Como funciona -->
        <section class="lp-secao" id="como-funciona" aria-labelledby="titulo-como-funciona">
            <div class="lp-container">
                <header class="lp-secao__cabecalho">
                    <p class="eyebrow">Como funciona</p>
                    <h2 id="titulo-como-funciona">Quatro passos, nenhuma planilha</h2>
                    <p>Do anúncio do evento até o certificado na sua mão, o Eventeen acompanha cada etapa.</p>
                </header>
                <ol class="lp-passos">
                    <li class="lp-passo">
                        <span class="lp-passo__numero">1</span>
                        <h3>Encontre</h3>
                        <p>Busque os eventos publicados por nome, local ou categoria e veja a programação completa.</p>
                    </li>
                    <li class="lp-passo">
                        <span class="lp-passo__numero">2</span>
                        <h3>Inscreva-se</h3>
                        <p>Garanta sua vaga com controle em tempo real e receba a confirmação por e-mail.</p>
                    </li>
                    <li class="lp-passo">
                        <span class="lp-passo__numero">3</span>
                        <h3>Participe</h3>
                        <p>A organização registra sua presença em cada atividade de que você participar.</p>
                    </li>
                    <li class="lp-passo">
                        <span class="lp-passo__numero">4</span>
                        <h3>Receba</h3>
                        <p>Emita o certificado em PDF, com código único que qualquer pessoa pode validar.</p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Recursos por público -->
        <section class="lp-secao lp-secao--suave" id="recursos" aria-labelledby="titulo-recursos">
            <div class="lp-container">
                <header class="lp-secao__cabecalho">
                    <p class="eyebrow">Recursos</p>
                    <h2 id="titulo-recursos">Feito para quem participa e para quem organiza</h2>
                </header>
                <div class="lp-publicos">
                    <article class="lp-publico">
                        <span class="lp-publico__tag">Participantes</span>
                        <h3>Sua vida acadêmica, organizada</h3>
                        <ul class="lp-lista">
                            <li>Consulta de eventos com vagas restantes e valor</li>
                            <li>Inscrição e cancelamento em poucos cliques</li>
                            <li>Histórico de eventos e atividades</li>
                            <li>Certificados em PDF sempre à mão</li>
                        </ul>
                        <a class="lp-botao" href="cadastro.php">Quero participar</a>
                    </article>
                    <article class="lp-publico lp-publico--escuro">
                        <span class="lp-publico__tag">Organizadores</span>
                        <h3>Do planejamento ao balanço final</h3>
                        <ul class="lp-lista">
                            <li>Programação com palestrantes, espaços e atividades sem conflito de horário</li>
                            <li>Lista de inscritos e presença em lote</li>
                            <li>Relatórios de inscrições e taxa de presença</li>
                            <li>Parceiros, patrocínios, pagamentos e despesas</li>
                        </ul>
                        <a class="lp-botao lp-botao--claro" href="cadastro.php">Organizar um evento</a>
                    </article>
                </div>
            </div>
        </section>

        <?php if ($proximosEventos !== []): ?>
        <!-- Próximos eventos (dados reais) -->
        <section class="lp-secao" id="eventos" aria-labelledby="titulo-eventos">
            <div class="lp-container">
                <header class="lp-secao__cabecalho lp-secao__cabecalho--linha">
                    <div>
                        <p class="eyebrow">Agenda</p>
                        <h2 id="titulo-eventos">Próximos eventos</h2>
                    </div>
                    <a class="lp-link-seta" href="entrar.php">Ver todos os eventos <span aria-hidden="true">→</span></a>
                </header>
                <div class="lp-eventos">
                    <?php foreach ($proximosEventos as $evento): $data = landingDataEvento((string) $evento['data_inicio']); ?>
                        <article class="lp-evento">
                            <time class="lp-evento__data" datetime="<?= h($data['iso']) ?>">
                                <span class="lp-evento__dia"><?= h($data['dia']) ?></span>
                                <span class="lp-evento__mes"><?= h($data['mes']) ?></span>
                            </time>
                            <div class="lp-evento__corpo">
                                <span class="lp-evento__categoria"><?= h($evento['categoria']) ?></span>
                                <h3><?= h($evento['titulo']) ?></h3>
                                <p class="lp-evento__info"><?= h($data['hora']) ?> · <?= h($evento['local']) ?></p>
                                <p class="lp-evento__rodape">
                                    <span><?= h(landingVagas($evento)) ?></span>
                                    <span class="lp-evento__valor"><?= h(landingValor($evento)) ?></span>
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Validação de certificado -->
        <section class="lp-secao lp-secao--estreita" id="certificados" aria-labelledby="titulo-certificados">
            <div class="lp-container">
                <div class="lp-validar">
                    <div class="lp-validar__texto">
                        <p class="eyebrow">Certificados</p>
                        <h2 id="titulo-certificados">Recebeu um certificado? Confira se é autêntico.</h2>
                        <p>Todo certificado emitido pelo Eventeen tem um código único. Digite-o abaixo para ver a quem e a qual atividade ele pertence.</p>
                    </div>
                    <form class="lp-validar__form" action="validar-certificado.php" method="get">
                        <label class="sr-only" for="lp-codigo">Código do certificado</label>
                        <input type="text" id="lp-codigo" name="codigo" maxlength="24" autocomplete="off" placeholder="XXXX-XXXX-XXXX-XXXX" required>
                        <button class="lp-botao" type="submit">Validar</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Chamada final -->
        <section class="lp-cta" aria-labelledby="titulo-cta">
            <div class="lp-container lp-cta__conteudo">
                <h2 id="titulo-cta">Seu próximo encontro começa aqui.</h2>
                <p>Crie sua conta gratuita e acompanhe os eventos da Fatec Ourinhos.</p>
                <div class="lp-hero__acoes lp-cta__acoes">
                    <a class="lp-botao lp-botao--claro" href="cadastro.php">Criar conta grátis</a>
                    <a class="lp-botao lp-botao--fantasma" href="entrar.php">Entrar</a>
                </div>
                <?php renderInstalarApp('instalar-app lp-cta__instalar'); ?>
            </div>
        </section>
    </main>

    <footer class="lp-rodape">
        <div class="lp-container lp-rodape__grade">
            <div>
                <img src="img/logo-eventeen-web.png" alt="Eventeen" width="130" height="47">
                <p>Do convite ao certificado.</p>
            </div>
            <nav aria-label="Links do rodapé">
                <a href="entrar.php">Entrar</a>
                <a href="cadastro.php">Criar conta</a>
                <a href="validar-certificado.php">Validar certificado</a>
            </nav>
            <p class="lp-rodape__assinatura">Fatec Ourinhos · Análise e Desenvolvimento de Sistemas · <?= date('Y') ?></p>
        </div>
    </footer>
</body>
</html>
