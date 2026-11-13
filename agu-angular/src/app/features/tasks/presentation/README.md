# Presentation — Tasks

Páginas standalone, componentes Material, formulários reativos e stores com Signals.
O painel está em `pages/task-list`; formulário, diálogo, resumo e estado vazio
ficam em `components`; TaskListStore fica em `state`.

Páginas chamam ações do store; o store chama casos de uso. Nenhum dos dois acessa
HTTP ou instancia repositórios. Regras de negócio permanecem no domínio.

Manter signals mutáveis privados, expor `asReadonly()` e usar `computed()` para
estado derivado da interface. Tratar carregamento e erros e escolher o escopo de
DI do store conforme seu ciclo de vida. Testar estados e interações observáveis.

Consulte a [arquitetura](../../../../../docs/01-ARQUITETURA.md).
