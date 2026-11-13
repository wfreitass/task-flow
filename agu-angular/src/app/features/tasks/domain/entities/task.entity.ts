import { isTaskStatus, TaskStatus } from '../value-objects/task-status.vo';

export interface TaskDetails {
  readonly title: string;
  readonly description: string;
  readonly status: TaskStatus;
}

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
  ) {}

  static create(id: string, details: TaskDetails): Task {
    const title = details.title.trim();
    if (!title) throw new TaskValidationError('Informe o título da tarefa.');
    if (title.length > 255) throw new TaskValidationError('O título deve ter no máximo 255 caracteres.');
    if (!isTaskStatus(details.status)) throw new TaskValidationError('Selecione um status válido.');
    return new Task(id, title, details.description.trim(), details.status);
  }

  withDetails(details: TaskDetails): Task {
    return Task.create(this.id, details);
  }

  withStatus(status: TaskStatus): Task {
    return this.withDetails({ ...this, status });
  }
}
