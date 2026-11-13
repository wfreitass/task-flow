import { Task, TaskDetails } from '../../domain/entities/task.entity';
import { TaskRepository } from '../../domain/repositories/task.repository';

export class UpdateTaskUseCase {
  constructor(private readonly repository: TaskRepository) {}
  execute(id: string, details: TaskDetails): Promise<Task> { return this.repository.update(id, details); }
}
