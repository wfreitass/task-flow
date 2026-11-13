import { TASK_PRIORITIES, Task } from '../../domain/entities/task.entity';
import { isTaskStatus } from '../../domain/value-objects/task-status.vo';

// Formato exclusivo do armazenamento da demonstração; não é um contrato da API.
export interface MockTaskSnapshot {
  readonly id: string;
  readonly title: string;
  readonly description: string;
  readonly status: string;
  readonly priority: string;
  readonly startAt: string;
  readonly dueAt: string;
}

export class MockTaskMapper {
  static toDomain(value: unknown): Task {
    if (
      typeof value !== 'object' || value === null ||
      !('id' in value) || typeof value.id !== 'string' || !value.id ||
      !('title' in value) || typeof value.title !== 'string' ||
      !('description' in value) || typeof value.description !== 'string' ||
      !('status' in value) || typeof value.status !== 'string'
    ) throw new Error('Invalid local task snapshot');

    const status = value.status === 'pending' ? 'todo' : value.status === 'completed' ? 'done' : value.status;
    if (!isTaskStatus(status)) throw new Error('Invalid local task snapshot');

    const priority = 'priority' in value && typeof value.priority === 'string' && TASK_PRIORITIES.includes(value.priority as typeof TASK_PRIORITIES[number])
      ? value.priority as typeof TASK_PRIORITIES[number] : 'medium';
    const startAt = 'startAt' in value && typeof value.startAt === 'string' ? value.startAt : '';
    const dueAt = 'dueAt' in value && typeof value.dueAt === 'string' ? value.dueAt : '';

    return Task.create(value.id, { title: value.title, description: value.description, status, priority, startAt, dueAt });
  }

  static toSnapshot(task: Task): MockTaskSnapshot {
    return { id: task.id, title: task.title, description: task.description, status: task.status, priority: task.priority, startAt: task.startAt, dueAt: task.dueAt };
  }
}
