# Feature Tasks

Contexto de gerenciamento de tarefas, organizado em quatro camadas:

- `domain`: modelo de negócio, invariantes e contratos de repositório.
- `application`: casos de uso dependentes dos contratos do domínio.
- `infrastructure`: HTTP, DTOs externos, mappers e DI.
- `presentation`: páginas standalone, componentes, formulários e stores com Signals.

O painel já usa listagem, criação, edição, exclusão e movimentação mockadas.
`tasks.routes.ts` fornece lazy loading e a composição dos casos de uso pelo
token `TASK_REPOSITORY`, ligado a `MockTaskRepository` na infraestrutura.

A demonstração usa localStorage e as colunas aprovadas A fazer, Em andamento e
Concluídas. Não copiar esse snapshot como se fosse um contrato confirmado da API.
Os componentes genéricos de UI ficam em `shared/ui`; o estado vazio é da feature.

Detalhes: [03 — Painel de tarefas](../../../../docs/03-PAINEL-DE-TAREFAS.md).

Consulte a [arquitetura](../../../../docs/01-ARQUITETURA.md) e o
[acompanhamento](../../../../docs/00-FLUXO-DE-TRABALHO.md).
