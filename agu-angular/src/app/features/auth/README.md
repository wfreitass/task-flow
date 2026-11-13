# Feature Auth

Login integrado à API Symfony e organizado por camadas:

- `domain`: modelos e contratos de autenticação/sessão, sem Angular ou browser.
- `application`: casos de uso de login, perfil autenticado e criação de conta.
- `infrastructure`: repositories HTTP, DTOs/mappers, adapter de armazenamento e DI.
- `presentation`: páginas Material e stores com Signals, incluindo o perfil do menu.

`auth.routes.ts` liga `AUTH_REPOSITORY` ao repositório HTTP. O fluxo e a
configuração da API estão em [05 — Integração do login](../../../../docs/05-INTEGRACAO-LOGIN-API.md).
O token JWT fica no armazenamento já usado pelo guard. A autenticação das rotas
de tarefas via interceptor é uma etapa futura, pois o painel ainda usa mocks.

O login reutiliza `UiFormFieldComponent` e `UiFormActionsComponent` de
`shared/ui`; detalhes visuais estão em [04 — Login com componentes de UI](../../../../docs/04-LOGIN-COMPONENTES-UI.md).
O cadastro segue o contrato Symfony documentado em
[08 — Criar conta](../../../../docs/08-CRIAR-CONTA.md).
