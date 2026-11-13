# Application — Tasks

Casos de uso em TypeScript puro, dependentes do domínio e de interfaces de
repositório recebidas no construtor. Não importar Angular, HTTP, infraestrutura,
componentes ou estado de interface.

Criar `use-cases/` por operação implementada e `dto/` quando houver entradas
tipadas necessárias. DTO de entrada de caso de uso não representa o transporte HTTP.
Testar a orquestração com fakes dos contratos, sem API real ou `TestBed`.

Consulte a [arquitetura](../../../../../docs/ARQUITETURA.md).
