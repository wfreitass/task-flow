# Infrastructure — Tasks

Implementa os contratos do domínio e concentra o transporte externo da feature.
Subpastas implementadas:

- `mock/`: MockTaskRepository com persistência local e tarefas de exemplo.
- `mappers/`: MockTaskMapper, snapshot do navegador ↔ domínio.
- `di/`: TASK_REPOSITORY e providers dos casos de uso.

A composição fornece casos de uso por factory, mantendo a aplicação independente
do Angular. Não há chamadas HTTP nesta implementação. A futura integração deverá
confirmar DTOs e endpoints, criar HttpTaskRepository e trocar o provider.
Testes adiados nesta etapa por solicitação do usuário.

Consulte a [arquitetura](../../../../../docs/01-ARQUITETURA.md).
