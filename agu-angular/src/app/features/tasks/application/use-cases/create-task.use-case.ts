import { Task, TaskDetails } from '../../domain/entities/task.entity';
import { TaskRepository } from '../../domain/repositories/task.repository';

export class CreateTaskUseCase {
  constructor(private readonly repository: TaskRepository) {}
  execute(details: TaskDetails): Promise<Task> { return this.repository.create(details); }
}
