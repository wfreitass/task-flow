# Presentation — Tasks

Páginas standalone, componentes Material, formulários reativos e stores com Signals.
Criar `pages/`, `components/` e `state/` conforme a necessidade de cada fluxo.

Páginas chamam ações do store; o store chama casos de uso. Nenhum dos dois acessa
HTTP ou instancia repositórios. Regras de negócio permanecem no domínio.

Manter signals mutáveis privados, expor `asReadonly()` e usar `computed()` para
estado derivado da interface. Tratar carregamento e erros e escolher o escopo de
DI do store conforme seu ciclo de vida. Testar estados e interações observáveis.

Consulte a [arquitetura](../../../../../docs/ARQUITETURA.md).
