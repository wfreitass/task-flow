# TaskFlow

Aplicação de gerenciamento de tarefas (To-Do List) com quadro Kanban, comentários, anexos, histórico e atualização em tempo real. É um monólito modular: **frontend em Angular 17** e **backend em PHP 8.1 + Symfony 6.4**, falando por REST (e WebSocket), com MySQL como fonte de verdade.

O que o usuário consegue fazer:

- Cadastrar-se e entrar por **e-mail e senha** (JWT).
- **Criar, editar, listar e excluir** tarefas (a exclusão é lógica), com prioridade, datas, anexos, comentários e histórico de alterações.
- **Atribuir** a tarefa a outro usuário, que recebe um **e-mail** (enviado em segundo plano).
- Ver o quadro **Kanban** ou a tabela, **filtrar** e **pesquisar** (busca textual com relevância).
- Ver a **mudança de status em tempo real** em todas as abas e dispositivos de quem enxerga a tarefa.
- **Perfis de acesso (RBAC):** o executor executa as tarefas, mas não as exclui nem cancela; o administrador faz tudo e gerencia perfis.

---

## Estrutura do repositório

```text
task-flow/
├── agu-angular/     frontend (Angular 17)
├── agu-php/         backend: backend/ (Symfony), docker-compose.yml, AGENTS.md
```

## Visão geral da arquitetura

```text
                        ┌──────────────────────────┐
  Navegador ───────────▶│  Angular 17  :4200       │
      │                 └──────────────────────────┘
      │ REST /api   WebSocket /ws
      ▼
┌──────────┐   ┌─────────────────┐        ┌────────────┐
│  Nginx   │──▶│ PHP-FPM         │───────▶│  MySQL 8   │  fonte de verdade
│  :8000   │   │ Symfony 6.4 API │        └────────────┘
└────┬─────┘   └───┬───────┬─────┘        ┌────────────┐
     │             │       └─────────────▶│  Redis     │  cache da listagem
     │ /ws         │ publica eventos      └────────────┘
     ▼             ▼ (depois do commit)
┌───────────┐  ┌──────────┐   consome    ┌──────────────────────────────────────┐
│ websocket │◀─│ RabbitMQ │─────────────▶│ worker (Symfony Messenger)           │
│ (Ratchet) │  └──────────┘              │  • e-mail de tarefa atribuída ─▶ Mailpit (SMTP)
└───────────┘   entrega por HTTP interno │  • status mudou ─▶ servidor WebSocket│
                                         │  • tarefa mudou ─▶ OpenSearch (índice)
                                         └──────────────────────────────────────┘
```

| Serviço (compose) | Papel |
|---|---|
| `nginx` | porta de entrada (`:8000`): repassa `/api` ao PHP-FPM e `/ws` ao WebSocket |
| `backend` | API Symfony (PHP-FPM); aplica as migrations ao subir |
| `worker` | consome a fila do RabbitMQ: e-mail, tempo real e índice de busca |
| `websocket` | servidor de WebSocket (Ratchet), com entrega interna por HTTP |
| `mysql_db` | MySQL 8, a fonte de verdade |
| `redis` | cache (sem persistência de propósito) |
| `rabbitmq` | fila de eventos |
| `opensearch` | busca textual |
| `mailpit` | caixa de e-mail de desenvolvimento (não envia nada para fora) |
| `redisinsight`, `opensearch-dashboards` | painéis de desenvolvimento |

## Tecnologias

| Camada | Tecnologia |
|---|---|
| **Frontend** | Angular 17.3, TypeScript 5.4, Angular Material 17, Reactive Forms, **Angular Signals** (estado), SASS, jKanban, Jasmine/Karma |
| **Backend** | PHP 8.1, Symfony 6.4 LTS, Doctrine ORM 3 e Migrations, DTOs e Application Services, PHPUnit |
| **Autenticação** | JWT (LexikJWTAuthenticationBundle), login por e-mail, logout sem estado |
| **Autorização** | RBAC próprio: `usuário → profile → roles`, no banco |
| **Banco** | MySQL 8.0 |
| **Cache** | Redis 7, com tags (invalidação por usuário) |
| **Mensageria** | RabbitMQ + Symfony Messenger (AMQP, JSON, retry e fila de falhas) |
| **E-mail** | Symfony Mailer + **Twig** (modelos HTML e texto), Mailpit no desenvolvimento |
| **Tempo real** | WebSocket com Ratchet (ReactPHP) |
| **Busca** | OpenSearch 2 (analisador em português) |
| **Infra** | Docker Compose, Nginx |

---

## Arquitetura do backend

DDD pragmático com Clean Architecture, em **monólito modular**. Três contextos:

| Contexto | Responsabilidade |
|---|---|
| `Task` | tarefas, status e transições, prioridade, datas, responsável, comentários, anexos e histórico |
| `User` | cadastro, credenciais, perfis (profiles) e roles |
| `Shared` | o que é transversal e sem regra de negócio: relógio, transação, cache, e-mail, mensageria, busca, upload |

Cada contexto tem quatro camadas, e a dependência aponta sempre para o domínio:

```text
Presentation ──▶ Application ──▶ Domain ◀── Infrastructure
controllers,     serviços de     entidades,    Doctrine, JWT, Redis,
requests,        caso de uso,    regras,       RabbitMQ, OpenSearch,
respostas        DTOs, portas    eventos       Twig, WebSocket
```

- **Domain** é PHP puro: sem Doctrine, Symfony ou HTTP. A regra de negócio mora nas entidades (ex.: transições de status, quem pode editar).
- **Application** orquestra o caso de uso e define **portas** (interfaces): `CacheInterface`, `EventPublisherInterface`, `EmailSenderInterface`, `RealtimePublisherInterface`, `TaskSearchIndexInterface`, `RoleCheckerInterface`, `FileStorageInterface`, entre outras.
- **Infrastructure** implementa as portas (**adaptadores**). Trocar Redis, RabbitMQ ou OpenSearch não altera nenhuma regra.
- **Persistência:** a entidade de domínio é pura; o Doctrine usa uma entidade própria (`*Entity`, com atributos `#[ORM]`), convertida por um `*Mapper` dentro do repositório.
- **Fronteira entre contextos:** `Task` referencia `User` só por **id**, sem associação Doctrine; quando precisa de dados de usuário, usa uma porta que o contexto `User` implementa.

### Fluxos principais

**Escrita e eventos.** O caso de uso grava no MySQL e publica um evento; a publicação só acontece **depois do commit**. A falha do broker não desfaz a operação (fica no log).

```text
PATCH status ─▶ grava ─▶ commit ─▶ TaskStatusChanged ─▶ RabbitMQ ─▶ worker ─▶ WebSocket ─▶ navegadores
criar/atribuir ─▶ grava ─▶ commit ─▶ TaskAssigned     ─▶ RabbitMQ ─▶ worker ─▶ e-mail (Twig) ─▶ SMTP
qualquer escrita ─▶ ........ commit ─▶ TaskChanged      ─▶ RabbitMQ ─▶ worker ─▶ índice do OpenSearch
```

**Leitura da listagem.** `GET /api/tasks` passa por uma cadeia de decorators do repositório: **cache (Redis) → busca (OpenSearch) → banco (MySQL)**. Sem texto de busca, usa o cache (invalidado por usuário a cada escrita, depois do commit). Com `q`, consulta o OpenSearch (que devolve só os ids em ordem de relevância) e carrega os dados do MySQL; se o OpenSearch falhar, volta para o `LIKE` do MySQL.

**Segurança de acesso às tarefas.** Duas camadas independentes:

1. **Posse:** só quem criou ou é o responsável enxerga a tarefa (quem não tem relação recebe `404`, sem revelar que existe); só o criador edita, atribui e exclui.
2. **RBAC:** `usuário → profile → roles` (`task.create`, `task.delete`, `task.cancel`, `user.manage`, `profile.manage`). O `admin` tem todas; o `executor` só `task.create`. Profiles e roles são editáveis pela API e valem **na hora** (lidos do banco, não do JWT). O sistema nunca fica sem um usuário ativo com `user.manage`.

### Modelo de dados

O esquema vem das migrations (`agu-php/backend/migrations/`), que são a fonte do modelo: `users`, `tasks`, `task_statuses` e `task_status_transitions` (catálogos), `task_history`, `task_comments`, `task_attachments`, mais `roles`, `profiles` e `profile_roles` (RBAC). Regras importantes ficam também no banco (CHECKs e triggers).

### Convenções da API

- Erros sempre em `{"error": {"code", "message", "details"}}`, com código estável (`TASK_NOT_FOUND`, `PERMISSION_DENIED`, `VALIDATION_FAILED`...).
- Listagem com paginação (`page`, `perPage` até 100), filtros (`status`, `priority`, `q`, `dueFrom`, `dueTo`, `scope`) e ordenação (`sort`, `order`; `relevance` na busca).
- Datas em ISO 8601, em UTC.

## Arquitetura do frontend

Angular 17 com componentes *standalone*, `OnPush`, rotas por feature (`loadChildren`) e `HttpClient` na infraestrutura. Organização **por feature** (`auth`, `tasks`, `users`), cada uma em quatro camadas, como no backend:

```text
src/app/
├── core/            configuração (URL da API), layout, sessão e guarda de rota
├── shared/ui/       componentes visuais genéricos (campos, tabela, Kanban, upload)
└── features/
    ├── auth/        login, cadastro, sessão
    ├── tasks/       painel (Kanban e tabela), formulário, comentários, histórico, busca, tempo real
    └── users/       lista de usuários e troca de perfil
        └── cada uma:  domain/ → application/ → infrastructure/ → presentation/
```

- **Domain e application** são TypeScript puro (casos de uso e interfaces de repositório); a **infraestrutura** implementa com `HttpClient` e `WebSocket`; a **presentation** usa stores com **Signals**.
- O cliente WebSocket autentica com o JWT na primeira mensagem, descarta mensagens repetidas ou atrasadas (por `id` e `occurredAt`) e reconecta com espera crescente, recarregando a lista depois de reconectar.

---

## Decisões e porquês

| Decisão | Motivo | Alternativa descartada |
|---|---|---|
| **Monólito modular** | um time pequeno e um domínio coeso; os contextos já estão separados, então dá para dividir depois | microsserviços |
| **DDD pragmático, domínio puro** | as regras (transições, posse, perfis) ficam testáveis sem banco nem framework | entidades do Doctrine usadas como domínio |
| **MySQL é a fonte de verdade; o modelo vem da migration** | uma única definição do esquema, com regras também no banco | modelo definido pelo frontend ou pelo mock |
| **Portas e adaptadores para cada integração** | Redis, RabbitMQ, OpenSearch e e-mail entram sem tocar nas regras, e dá para trocá-los | chamar os clientes direto nos serviços |
| **Eventos publicados depois do commit** | o consumidor nunca vê uma alteração que ainda não existe ou que foi desfeita | publicar dentro da transação |
| **Consumidores idempotentes** (relêem o estado atual; id de mensagem estável) | a fila entrega "ao menos uma vez": repetir ou reordenar não pode duplicar nem desfazer | confiar no conteúdo do evento |
| **Índice de busca derivado e refazível** | o OpenSearch pode ficar fora do ar sem derrubar a busca (cai no MySQL) e se reconstrói do banco (`app:search:reindex`) | usar o OpenSearch como armazenamento |
| **Cache só da listagem sem texto, com tags por usuário** | a invalidação é exata e imediata; busca com texto não é cacheada porque o índice é assíncrono | cache por TTL apenas |
| **JWT stateless, login por e-mail** | sem sessão no servidor; o frontend só guarda o token | sessão em servidor |
| **RBAC no banco (`usuário → profile → roles`)** | mudar quem pode o quê é uma operação de dados pela API, com efeito imediato | enum fixo no código (foi a primeira versão, substituída); `#[IsGranted]` nos controllers |
| **Perfil lido do banco, não do JWT** | promover ou rebaixar vale na hora, sem esperar o token expirar | roles dentro do token |
| **404 para recurso sem acesso** | não revela se a tarefa existe | 403 |
| **WebSocket (Ratchet) em vez de SSE** | pedido do projeto; o canal já tem autenticação e `ping`/`pong` | Mercure ou SSE |
| **Modelos de e-mail em Twig** | texto e layout fora do PHP, com escape automático | HTML montado no código |
| **Symfony 6.4 LTS, não o 6.0 listado** | a série 6.0 já não recebe correções; o 6.4 é a LTS da mesma linha | Symfony 6.0 |
| **Angular Signals, não NgRx** | o estado é local às features e o enunciado aceita "RxJS, NgRx ou Signals"; menos cerimônia | NgRx (removido das dependências por não ser usado) |
| **Docker Compose com migrations no `CMD` do Dockerfile** | subir o ambiente é um comando; sem script `.sh` | scripts de inicialização à parte |

---

## Como executar

Pré-requisitos: Docker com Compose e Node.js (para o frontend).

```bash
# 1) Backend e serviços (migrations, chaves JWT e índice de busca são criados ao subir)
cd agu-php
docker compose up -d --build

# 2) Frontend
cd ../agu-angular
npm ci
npm start                 # http://localhost:4200

# 3) Primeiro administrador (o cadastro sempre cria executores)
cd ../agu-php
docker compose exec -u www-data backend php bin/console app:user:set-profile voce@exemplo.com admin
```

Cadastre-se em http://localhost:4200/register, rode o comando do passo 3 com o seu e-mail e entre de novo.

| Endereço | O que é |
|---|---|
| http://localhost:4200 | aplicação |
| http://localhost:8000/api | API |
| ws://localhost:8000/ws | WebSocket |
| http://localhost:8025 | Mailpit (e-mails de teste) |
| http://localhost:15672 | RabbitMQ (`user` / `password`) |
| http://localhost:5601 | OpenSearch Dashboards |
| http://localhost:5540 | RedisInsight |

As senhas do ambiente (`user` / `password`, `dev-realtime-token`) são **só de desenvolvimento**.

**Testes:** o backend tem uma suíte PHPUnit pequena, de demonstração (16 testes: entidade, casos de uso em memória, repositório contra MySQL real e API de ponta a ponta; gravar, buscar um item, verificar o título e editar). Crie o banco `dbname_test` e rode a suíte, conforme [backdocs/02-execucao-e-testes.md](./backdocs/02-execucao-e-testes.md). O frontend usa Jasmine/Karma (`npm test`).

## Documentação

| Onde | O que tem |
|---|---|
| [`backdocs/`](./backdocs) | documentação numerada do backend: `00-progresso.md` (o que foi feito e onde parei), `01-arquitetura.md`, `02-execucao-e-testes.md` (como funciona cada integração, o passo a passo e os limites) |
| [`docs/`](./docs) | documentação numerada do frontend: `00-FLUXO-DE-TRABALHO.md` e um arquivo por entrega |
| [`agu-php/AGENTS.md`](./agu-php/AGENTS.md) e [`agu-angular/AGENTS.md`](./agu-angular/AGENTS.md) | regras de arquitetura e de trabalho de cada projeto |

## Requisitos do teste técnico

| Requisito | Situação |
|---|---|
| Autenticação JWT no backend PHP/Symfony | atendido |
| Adicionar, editar, remover e listar tarefas, em MySQL | atendido |
| Angular 17, Angular Material, formulários reativos | atendido |
| Gerenciamento de estado | **Signals** (o enunciado cita "RxJS ou NgRx ou Signals" na lista de tecnologias e "NgRx" nos requisitos da interface; a escolha é consciente) |
| REST entre frontend e backend | atendido |
| Opcionais: Redis, OpenSearch, RabbitMQ, WebSocket | todos implementados |
| Opcional: certificados X.509 | **não implementado** |

## Limitações e pendências conhecidas

- **Sem Outbox transacional:** se o RabbitMQ estiver fora do ar no instante da escrita, o evento é perdido (e-mail e atualização em tempo real não saem; o índice de busca se refaz com `app:search:reindex`). A entrega é "ao menos uma vez": um e-mail pode, raramente, sair em duplicidade.
- **WebSocket:** um único processo, e mensagens não são guardadas: quem estava desconectado recarrega a lista ao reconectar. Só há evento de mudança de status.
- **A busca** cobre título e descrição (não comentários nem anexos) e tem consistência eventual (cerca de 1 s).
- **RBAC:** o administrador enxerga apenas as tarefas que criou ou que lhe foram atribuídas (a regra de posse vale por cima das roles). O executor cria tarefas, pois nada foi dito em contrário. O frontend ainda decide por `role === 'admin'`, e um profile personalizado não passa na validação dele; o backend mantém o campo `role` por compatibilidade.
- **Testes:** a suíte do backend é pequena e didática (16 testes sobre criar, buscar, editar e verificar o título de tarefas); não cobre as integrações (e-mail, WebSocket, busca, RBAC). Os testes do frontend não são executados com frequência.
- **Segurança de desenvolvimento:** os segredos de exemplo (`APP_SECRET`, `JWT_PASSPHRASE` em `agu-php/backend/.env_example`) e as senhas do Compose devem ser trocados fora do desenvolvimento. Não há renovação de token (refresh) nem limite de tentativas no login.
- **PHP 8.1** já está fora de suporte; o plano de atualização está registrado em [backdocs/00-progresso.md](./backdocs/00-progresso.md).
