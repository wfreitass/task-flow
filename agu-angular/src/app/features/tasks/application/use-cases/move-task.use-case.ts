import { Task } from '../../domain/entities/task.entity';
import { TaskRepository } from '../../domain/repositories/task.repository';
import { TaskStatus } from '../../domain/value-objects/task-status.vo';

export class MoveTaskUseCase {
  constructor(private readonly repository: TaskRepository) {}
  execute(id: string, status: TaskStatus, beforeId: string | null): Promise<Task[]> {
    return this.repository.move(id, status, beforeId);
  }
}
