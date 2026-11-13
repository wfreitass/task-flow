# Feature Tasks

Contexto de gerenciamento de tarefas, organizado em quatro camadas:

- `domain`: modelo de negócio, invariantes e contratos de repositório.
- `application`: casos de uso dependentes dos contratos do domínio.
- `infrastructure`: HTTP, DTOs externos, mappers e DI.
- `presentation`: páginas standalone, componentes, formulários e stores com Signals.

O primeiro fluxo previsto é a listagem, após a confirmação do contrato da API.
`tasks.routes.ts` será criado junto da implementação, com carregamento lazy e
providers que conectem contratos, implementações e casos de uso.

Esta etapa contém somente orientações. Não copiar campos, status ou endpoints
ilustrativos do `AGENTS.md` como se fossem contratos confirmados.

Consulte a [arquitetura](../../../../docs/ARQUITETURA.md) e o
[acompanhamento](../../../../docs/FLUXO-DE-TRABALHO.md).
