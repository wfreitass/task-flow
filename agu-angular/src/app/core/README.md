# Core

Infraestrutura transversal e composição global: configuração de API, autenticação
de rotas, interceptors, layouts e tratamento de erros inesperados.

`auth/` contém o guard e o armazenamento de sessão, e `layout/main-layout/` mantém
a navegação autenticada separada do login. Criar `config/` ou `errors/` quando uma implementação os exigir.
Entidades, casos de uso e repositórios de tarefas pertencem a `features/tasks`.

Consulte a [arquitetura](../../../docs/01-ARQUITETURA.md) antes de migrar o código atual.
