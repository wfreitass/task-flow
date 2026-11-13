import { Task } from '../domain/entities/task.entity';
import { TaskStatus } from '../domain/value-objects/task-status.vo';

export type TaskBoardStatus = 'pending' | 'in_progress' | 'completed';

export const TASK_BOARD_OPTIONS: readonly { value: TaskBoardStatus; label: string; tone: 'neutral' | 'amber' | 'green' }[] = [
  { value: 'pending', label: 'A fazer', tone: 'neutral' },
  { value: 'in_progress', label: 'Em andamento', tone: 'amber' },
  { value: 'completed', label: 'Concluídas', tone: 'green' },
];

export const TASK_STATUS_OPTIONS: readonly { value: TaskStatus; label: string }[] = [
  { value: 'backlog', label: 'Backlog' }, { value: 'todo', label: 'A fazer' },
  { value: 'in_progress', label: 'Em andamento' }, { value: 'blocked', label: 'Bloqueada' },
  { value: 'done', label: 'Concluída' }, { value: 'canceled', label: 'Cancelada' },
];

export const TASK_STATUS_LABELS: Record<TaskStatus, string> = {
  backlog: 'Backlog', todo: 'A fazer', in_progress: 'Em andamento',
  blocked: 'Bloqueada', done: 'Concluída', canceled: 'Cancelada',
};

export function toBoardStatus(status: TaskStatus): TaskBoardStatus {
  if (status === 'backlog' || status === 'todo') return 'pending';
  if (status === 'in_progress' || status === 'blocked') return 'in_progress';
  return 'completed';
}

export function toApiStatus(boardStatus: TaskBoardStatus): TaskStatus {
  return boardStatus === 'pending' ? 'todo' : boardStatus === 'completed' ? 'done' : 'in_progress';
}

export function taskMatchesBoardStatus(task: Task, status: TaskBoardStatus): boolean {
  return toBoardStatus(task.status) === status;
}
