# Infrastructure — Tasks

Implementa os contratos do domínio e concentra o transporte externo da feature.
As subpastas serão criadas junto do fluxo que as utilizar:

- `http/`: repositories com `HttpClient` e endpoints confirmados.
- `dto/`: request/response conforme o contrato real da API.
- `mappers/`: conversão entre transporte e domínio, sem regras de negócio.
- `di/`: `InjectionToken` dos contratos e providers quando necessários.

A composição fornece casos de uso por factory, mantendo a aplicação independente
do Angular. Centralizar a URL-base em `core/config` ao implementar a integração.
Testar mappers e transporte com backend HTTP de teste, sem acessar a API real.

Consulte a [arquitetura](../../../../../docs/ARQUITETURA.md).
