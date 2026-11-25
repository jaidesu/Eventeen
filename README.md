# Eventeen

Aplicação web para gerenciamento de eventos acadêmicos e institucionais da Fatec Ourinhos: divulgação, inscrição com controle de vagas, programação, presença por atividade, certificados, relatórios e controle financeiro dos eventos.

Projeto da disciplina de Engenharia de Software II (ADS, 2026). A documentação do desenvolvimento está em `docs/Documentação do Desenvolvimento - Eventeen v2.docx`.

## Tecnologias

- PHP 8.1 ou superior (PDO MySQL, mbstring e openssl), sem frameworks nem dependências externas.
- MySQL 8 ou MariaDB 10.6+ (testado com o MariaDB do XAMPP e com MariaDB 11.4).
- HTML e CSS responsivos (`css/style.css` para a área do participante, `css/gestao.css` para as áreas de gestão e `css/landing.css` para a página inicial).

## Configuração do banco de dados

1. Inicie o Apache e o MySQL pelo XAMPP.
2. No phpMyAdmin, abra **Importar**, selecione `banco.sql` e execute. O script cria o banco `eventeen`, as tabelas e dados de demonstração para usuários, eventos, programação, inscrições, certificados e financeiro.
3. No XAMPP não é preciso configurar nada: o padrão é o usuário `root`, sem senha. Para outro ambiente, veja [Publicação em servidor](#publicação-em-servidor).
4. Acesse `http://localhost/Eventeen/` (página inicial) e clique em **Entrar** para usar uma conta de demonstração ou crie uma conta.

O arquivo também pode ser importado pelo terminal:

```sh
mysql -u root < banco.sql
```

O `banco.sql` pode ser reimportado sobre um banco existente: ele cria as tabelas que faltam e adiciona as colunas novas (`eventos.valor_inscricao`, `patrocinios.tipo_apoio` e `patrocinios.descricao_apoio`) sem apagar dados.

### Contas de demonstração

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Administrador | `admin.demo@eventeen.local` | `AdminDemo2026!` |
| Organizador | `organizador.demo@eventeen.local` | `Organizador2026!` |
| Participante | `participante.demo@eventeen.local` | `Participante2026!` |
| Participante | `participante2.demo@eventeen.local` | `Participante2026!` |

Essas contas e os dados relacionados são apenas para demonstração; altere ou remova as senhas padrão antes de disponibilizar o sistema publicamente.

### Primeiro administrador

No cadastro público, escolha a aba de participante ou organizador. Para criar uma conta de organizador, informe o código de acesso fornecido pela organização do evento. Depois de criar sua conta, promova-a a administrador uma única vez pelo phpMyAdmin ou pelo terminal:

```sql
UPDATE eventeen.usuarios SET perfil = 'administrador' WHERE email = 'seu-email@exemplo.com';
```

A partir daí, o administrador concede ou revoga os perfis de organizador e administrador pela tela **Usuários**. O último administrador não pode ser revogado.

## Funcionalidades

| Área | Funcionalidades |
| --- | --- |
| Acesso | Cadastro em abas de participante ou organizador (organizador exige o código de acesso definido em `codigo_organizador`), login, "manter conectado", logout por POST com proteção CSRF, alteração de dados e de senha. Recuperação de senha por link enviado ao e-mail (válido por 60 minutos e de uso único). Login bloqueado temporariamente após 5 falhas para o mesmo e-mail (ou 20 do mesmo IP) em 15 minutos. Todos os campos têm limite de tamanho na tela (`maxlength`) e no servidor, conforme a coluna do banco; senhas novas têm de 8 a 64 caracteres, buscas até 100, e requisições acima de 1 MB são recusadas. A sessão expira após 2 horas sem uso (com "manter conectado": 30 dias para participante e 12 horas para gestão) e é encerrada nos outros dispositivos quando a senha muda. O perfil é relido a cada requisição, então concessões e revogações valem imediatamente. |
| Administração | Painel com totais reais, busca e filtro de usuários, edição de dados e de perfil (participante, organizador, administrador), com proteção do último administrador e do organizador único de um evento ativo (que pode ser promovido a administrador, mas não rebaixado a participante). Administradores também têm acesso às áreas de participante e organização, podendo gerenciar todos os eventos. |
| Eventos | Cadastro, edição, publicação, cancelamento e encerramento, com vários organizadores (organizadores ou administradores), vagas, local e valor de inscrição opcional. Não é possível agendar eventos no passado: o calendário só oferece datas a partir de agora e o servidor recusa datas passadas. As alterações preservam inscrições e demais registros. |
| Consulta e inscrição | Busca e filtro de eventos publicados, detalhes com programação, vagas restantes e valor; inscrição transacional com bloqueio de concorrência e sem duplicidade; e-mail de confirmação da inscrição. |
| Cancelamento | Permitido até 24 horas antes do início do evento e enquanto não houver presença registrada; liberação da vaga uma única vez. |
| Programação | Palestrantes, espaços e atividades por evento; cada atividade tem espaço e ao menos um palestrante, começa sugerida no início do evento e tem a capacidade preenchida com as vagas do espaço escolhido, ocorre dentro do período do evento e respeita a capacidade do espaço e do evento, sem conflito de espaço ou de palestrante. |
| Presença | Registro e remoção de presença por atividade, somente para inscrições Confirmadas e atividades do próprio evento. |
| Histórico e certificados | Histórico de eventos e atividades do participante; emissão de certificado em PDF por presença em atividade concluída, com código único e página pública de validação (`validar-certificado.php`). Organizadores e administradores veem e baixam os certificados dos participantes dos seus eventos na tela de presenças. |
| Relatórios | Inscrições confirmadas e canceladas, participantes presentes (distintos) e taxa de presença por evento; presenças por atividade; filtros por evento e período; impressão. |
| Parceiros e finanças | Parceiros; patrocínios financeiros, materiais ou institucionais; pagamentos de patrocínio até o valor acordado; pagamentos de inscrição (um por inscrição, confirmação com data e estorno em até 7 dias); entradas e despesas com rateio entre eventos; saldo por evento. |
| Interface e navegação | Menu de gestão agrupado (Eventos, Programação, Financeiro e, para administradores, Administração e Área do participante); atalhos de programação e presenças em cada evento; seletor de evento padronizado; registro de presença em lote com filtro por nome; confirmação antes de ações irreversíveis; marcação de campos obrigatórios; link "Pular para o conteúdo" e foco visível; telas utilizáveis de 360 a 1366 pixels. Administradores navegam pela área do participante com atalho de volta à administração. |

## Organização do código

- `index.php`: página inicial pública (landing page) com apresentação, próximos eventos publicados e validação de certificado; quem já entrou vai direto para a sua área.
- `entrar.php`, `cadastro.php`, `sair.php`, `esqueci-senha.php`, `redefinir-senha.php`, `configuracoes-conta.php` e `validar-certificado.php`: acesso, recuperação de senha, sessão, conta e validação pública de certificados.
- `config.php`: conexão, sessão e funções comuns.
- `includes/base.php`: configuração (lê `config.local.php` quando existir; veja `config.exemplo.php`), endereços e escape de HTML, sem depender do banco.
- `includes/pagina.php`: cabeçalho comum das páginas (título, SEO, ícones, PWA) e página de erro; `erro.php` exibe os erros 400, 403, 404, 405, 500, 503 e outros.
- `manifest.json`, `sw.js`, `js/pwa.js` e `offline.html`: aplicativo instalável (PWA) e página exibida sem internet.
- `robots.txt`, `sitemap.xml` e `.htaccess`: buscadores, páginas de erro do Apache, bloqueio de pastas internas e cache.
- `img/`: logo, marca (cabeçalhos), imagem de compartilhamento e ícones (`img/icones/`); `favicon.ico` fica na raiz.
- `participante/`: área do participante (início, eventos, detalhes, inscrições, cancelamento, histórico e certificado).
- `organizador/`: painel, eventos, programação, presenças, relatórios, parceiros e financeiro.
- `administrador/`: painel administrativo e gestão de usuários e permissões.
- `includes/`: regras de negócio reutilizadas pelas telas e pelos testes (`autenticacao`, `email`, `inscricoes`, `programacao`, `presencas`, `certificados`, `relatorios`, `financeiro`, `usuarios`) e a estrutura das telas de gestão (`gestao.php`).
- `tests/`: testes automatizados executados pela linha de comando.
- `docs/`: documentação, estudos e protótipos das telas.
- `storage/`: logs e e-mails gerados pela aplicação (fora do Git e bloqueado para acesso pela web).

Os nomes antigos das páginas do participante na raiz (`eventos.php`, `minhas-inscricoes.php` etc.) continuam disponíveis como atalhos para preservar links existentes.

## Como executar

Com o Apache do XAMPP, acesse `http://localhost/Eventeen/`. Alternativamente, na raiz do projeto:

```sh
php -S 127.0.0.1:8000
```

## Testes automatizados

Com o MySQL/MariaDB iniciado e o banco `eventeen` atualizado por `banco.sql`, execute na raiz do projeto (no XAMPP, use `C:\xampp\php\php.exe` no lugar de `php`):

```sh
php tests/autenticacao.php
php tests/inscricoes.php
php tests/programacao.php
php tests/presencas.php
php tests/certificados.php
php tests/relatorios.php
php tests/usuarios.php
php tests/financeiro.php
php tests/pagamentos.php
```

Cada teste cria dados temporários, imprime `OK` com o número de verificações e remove os dados ao final.

| Arquivo | O que verifica |
| --- | --- |
| `autenticacao.php` | Login, bloqueio por tentativas (por e-mail e por IP) e expiração do bloqueio, recuperação de senha (resposta igual para e-mail inexistente, hash do código, link único, validade e uso único), limites de sessão e cabeçalhos de e-mail. |
| `inscricoes.php` | Duplicidade, horário, lotação, cancelamento com 24 h de antecedência e sem presença, reativação e oito inscrições concorrentes para duas vagas. |
| `programacao.php` | Vínculos ao evento, capacidade, período do evento, conflitos de espaço e de palestrante, edição e exclusão. |
| `presencas.php` | Inscrição Confirmada, atividade do evento, permissão do organizador, idempotência, remoção e histórico. |
| `certificados.php` | Exigência de presença, atividade concluída, código único, reemissão sem duplicidade, carga horária, PDF válido, validação pelo código, bloqueio da remoção de presença certificada e acesso da organização do evento (e só dela) aos certificados dos participantes. |
| `relatorios.php` | Massa do critério CA-RF13 (3 confirmadas, 1 cancelada, 2 presentes), contagem distinta entre atividades, filtro de período e isolamento entre organizadores. |
| `usuarios.php` | Hash com salt, concessão e revogação de perfis, e-mail único, último administrador e organizador único de evento (promoção a administrador permitida). |
| `financeiro.php` | Parceiros, patrocínios, limite de pagamentos do patrocínio, permissões por evento, soma de rateios e saldo. |
| `pagamentos.php` | Pagamento único por inscrição, evento gratuito, permissões, confirmação com data, estorno em até 7 dias, saldo e tipos de apoio. |

O teste de "último administrador" só é aplicado quando o banco não tem outros administradores.

## E-mails no ambiente local

Sem configuração, os e-mails (recuperação de senha e confirmação de inscrição) não são enviados: cada mensagem é gravada como arquivo `.eml` em `storage/emails`, que pode ser aberto no Outlook ou em qualquer editor de texto. Assim é possível demonstrar a recuperação de senha sem servidor de e-mail.

## Publicação em servidor

1. Hospedagem com HTTPS, PHP 8.1+ e MySQL 8 ou MariaDB 10.6+.
2. Crie um usuário próprio do banco, com permissão apenas sobre o banco `eventeen`, e importe `banco.sql`. Repita a importação a cada atualização do sistema: o script cria o que falta sem apagar dados.
3. Copie `config.exemplo.php` para `config.local.php` e preencha:
   - `ambiente` => `producao`: oculta mensagens técnicas e grava erros em `storage/logs/php-erros.log`;
   - `url_base`: endereço público, usado nos links dos e-mails e dos certificados;
   - `forcar_https` => `true`;
   - `db`: usuário e senha do banco;
   - `codigo_organizador`: código exigido para criar conta de organizador no cadastro público (o padrão do repositório é público e deve ser trocado; `''` desativa esse cadastro);
   - `fuso_horario`: fuso dos eventos (padrão `America/Sao_Paulo`); o banco é ajustado para o mesmo fuso a cada conexão;
   - `email`: servidor SMTP (porta 587 com `tls` ou 465 com `ssl`) e remetente.
4. O `config.local.php` fica fora do Git: nunca envie senhas ao repositório. Se preferir, os dados do banco podem vir das variáveis de ambiente `EVENTEEN_DB_HOST`, `EVENTEEN_DB_NOME`, `EVENTEEN_DB_USUARIO` e `EVENTEEN_DB_SENHA`.
5. No Apache, o `.htaccess` da raiz liga as páginas de erro (`ErrorDocument ... /erro.php`), bloqueia `includes/`, `tests/`, `docs/`, `banco.sql` e outros arquivos internos, desativa a listagem de pastas e define cache e compressão. Ele supõe o sistema na raiz do domínio (`https://eventeen.com.br/`); numa subpasta, ajuste o caminho do `ErrorDocument` (no XAMPP, `/Eventeen/erro.php`). A hospedagem precisa permitir `.htaccess` (`AllowOverride All`). No Nginx, use `error_page 403 404 500 502 503 504 /erro.php;` e bloqueie as mesmas pastas.
6. Em `url_base`, use o endereço definitivo (`https://eventeen.com.br`): ele é o endereço canônico das páginas para os buscadores. `robots.txt` e `sitemap.xml` já apontam para esse domínio; troque-os se o domínio mudar. Depois de publicar, envie `https://eventeen.com.br/sitemap.xml` no Google Search Console.
7. O app instalável (PWA) e o modo offline só funcionam com HTTPS. Ao mudar a lista de arquivos pré-carregados em `sw.js`, aumente a constante `VERSAO`.
8. A pasta `storage/` precisa de permissão de escrita para o PHP. No Apache, o `.htaccess` dela impede o acesso pela web; em outro servidor (como o Nginx), bloqueie a pasta na configuração ou aponte `pasta_dados` para fora da pasta publicada.
9. Configure cópia de segurança periódica do banco.
10. Limite o tamanho das requisições também no servidor (no Apache, `LimitRequestBody 1048576`; no Nginx, `client_max_body_size 1m`) e no PHP (`post_max_size = 1M`). Contra ataques distribuídos de negação de serviço (DDoS), use o limite de requisições ou o serviço de proteção da hospedagem (CDN ou firewall de aplicação): a aplicação sozinha não resolve esse tipo de ataque.

Sem `config.local.php`, o sistema só se considera em desenvolvimento quando acessado da própria máquina (`localhost`); qualquer acesso externo é tratado como produção e não exibe erros técnicos.

Antes de abrir ao público, também são necessários os documentos exigidos pela LGPD (política de privacidade e termos de uso) e um canal para o titular pedir a exclusão dos dados.

## Modelo de negócio e licença

O Eventeen é oferecido como serviço: a equipe Eventeen implanta, hospeda, mantém e dá suporte ao sistema, e a instituição paga uma mensalidade pela licença de uso e pelo suporte. A taxa de 10% sobre inscrições pagas está arquivada até a plataforma receber os pagamentos por um intermediador (hoje o sistema apenas registra pagamentos informados pelo organizador).

O código-fonte, a marca e a documentação pertencem à equipe Eventeen. A Fatec, caso adote o sistema, é apenas usuária licenciada: a propriedade não é transferida e o sistema não pode ser copiado, modificado, redistribuído ou instalado em outros servidores sem autorização por escrito da equipe. A publicação deste repositório não concede licença de uso: todos os direitos são reservados. Os dados cadastrados pela instituição pertencem a ela e são entregues em caso de encerramento do contrato.

Detalhes, pontos em aberto e condições de contratação estão na seção 2.7 da documentação do desenvolvimento.
