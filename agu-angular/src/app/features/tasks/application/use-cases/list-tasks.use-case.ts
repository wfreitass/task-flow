import { Task } from '../../domain/entities/task.entity';
import { TaskRepository } from '../../domain/repositories/task.repository';

export class ListTasksUseCase {
  constructor(private readonly repository: TaskRepository) {}
  execute(query?: string): Promise<Task[]> { return this.repository.findAll(query); }
}
