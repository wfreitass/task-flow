import { isTaskStatus, TaskStatus } from '../value-objects/task-status.vo';

export interface TaskAssignee { readonly id: number; readonly username: string; }

export interface TaskDetails {
  readonly title: string;
  readonly description: string;
  readonly status: TaskStatus;
  readonly priority?: TaskPriority;
  readonly startAt?: string;
  readonly dueAt?: string;
  readonly assignee?: TaskAssignee | null;
  readonly createdBy?: number;
}

export const TASK_PRIORITIES = ['low', 'medium', 'high', 'urgent'] as const;
export type TaskPriority = typeof TASK_PRIORITIES[number];

export class TaskValidationError extends Error {
  constructor(message: string) {
    super(message);
    this.name = 'TaskValidationError';
  }
}

export class Task {
  private constructor(
    public readonly id: string,
    public readonly title: string,
    public readonly description: string,
    public readonly status: TaskStatus,
    public readonly priority: TaskPriority,
    public readonly startAt: string,
    public readonly dueAt: string,
    public readonly assignee: TaskAssignee | null,
    public readonly createdBy: number,
  ) {}

  static create(id: string, details: TaskDetails): Task {
    const title = details.title.trim();
    if (!title) throw new TaskValidationError('Informe o título da tarefa.');
    if (title.length > 255) throw new TaskValidationError('O título deve ter no máximo 255 caracteres.');
    if (!isTaskStatus(details.status)) throw new TaskValidationError('Selecione um status válido.');
    const priority = details.priority ?? 'medium';
    const startAt = details.startAt ?? '';
    const dueAt = details.dueAt ?? '';
    if (!TASK_PRIORITIES.includes(priority)) throw new TaskValidationError('Selecione uma prioridade válida.');
    if (startAt && dueAt && dueAt < startAt) {
      throw new TaskValidationError('A data de vencimento deve ser igual ou posterior à data de início.');
    }
    return new Task(id, title, details.description.trim(), details.status, priority, startAt, dueAt, details.assignee ?? null, details.createdBy ?? 0);
  }

  withDetails(details: TaskDetails): Task {
    return Task.create(this.id, details);
  }

  withStatus(status: TaskStatus): Task {
    return this.withDetails({ ...this, status });
  }
}
