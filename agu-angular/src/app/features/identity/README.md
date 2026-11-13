# Feature Identity

Login de demonstração implementado por camadas:

- `domain`: modelos e contratos de autenticação/sessão; sem Angular ou browser.
- `application`: `LoginUseCase`, que autentica e persiste a sessão pelos contratos.
- `infrastructure`: mock de autenticação, adapter de armazenamento e tokens de DI.
- `presentation`: página Material, formulário reativo e store com Signals.

`identity.routes.ts` conecta implementações e caso de uso. O mock não usa HTTP;
credenciais de demonstração e limitações estão em [LOGIN.md](../../../../docs/LOGIN.md).
Cadastro e demais fluxos de identidade continuam pendentes.
