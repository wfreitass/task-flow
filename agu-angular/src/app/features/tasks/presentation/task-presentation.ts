import { TaskStatus } from '../domain/value-objects/task-status.vo';

export const TASK_STATUS_OPTIONS: readonly { value: TaskStatus; label: string; tone: 'neutral' | 'amber' | 'green' }[] = [
  { value: 'pending', label: 'A fazer', tone: 'neutral' },
  { value: 'in_progress', label: 'Em andamento', tone: 'amber' },
  { value: 'completed', label: 'Concluídas', tone: 'green' },
];

export const TASK_STATUS_LABELS: Record<TaskStatus, string> = {
  pending: 'A fazer', in_progress: 'Em andamento', completed: 'Concluídas',
};
