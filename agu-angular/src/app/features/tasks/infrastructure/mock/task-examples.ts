import { TaskDetails } from '../../domain/entities/task.entity';

export const TASK_EXAMPLES: readonly TaskDetails[] = [
  { title: 'Planejar as prioridades da semana', description: 'Separar as entregas mais importantes e organizar os próximos passos.', status: 'pending' },
  { title: 'Revisar o escopo do MVP', description: 'Conferir as funcionalidades que entram na primeira versão do projeto.', status: 'pending' },
  { title: 'Organizar os componentes de UI', description: 'Preparar campos de formulário e tabelas para reutilizar no projeto.', status: 'in_progress' },
  { title: 'Montar o painel de tarefas', description: 'Acompanhar o trabalho nas visualizações Kanban e tabela.', status: 'in_progress' },
  { title: 'Estruturar a arquitetura do projeto', description: 'Separar domínio, aplicação, infraestrutura e apresentação.', status: 'completed' },
  { title: 'Preparar a tela de login', description: 'Criar o acesso de demonstração com Angular Material e Signals.', status: 'completed' },
];
