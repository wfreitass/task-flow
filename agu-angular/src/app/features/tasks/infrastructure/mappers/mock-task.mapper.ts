import { Task } from '../../domain/entities/task.entity';
import { isTaskStatus } from '../../domain/value-objects/task-status.vo';

// Formato exclusivo do armazenamento da demonstração; não é um contrato da API.
export interface MockTaskSnapshot {
  readonly id: string;
  readonly title: string;
  readonly description: string;
  readonly status: string;
}

export class MockTaskMapper {
  static toDomain(value: unknown): Task {
    if (
      typeof value !== 'object' || value === null ||
      !('id' in value) || typeof value.id !== 'string' || !value.id ||
      !('title' in value) || typeof value.title !== 'string' ||
      !('description' in value) || typeof value.description !== 'string' ||
      !('status' in value) || !isTaskStatus(value.status)
    ) throw new Error('Invalid local task snapshot');

    return Task.create(value.id, { title: value.title, description: value.description, status: value.status });
  }

  static toSnapshot(task: Task): MockTaskSnapshot {
    return { id: task.id, title: task.title, description: task.description, status: task.status };
  }
}
