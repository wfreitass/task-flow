import { Task } from '../../domain/entities/task.entity';
import { TaskRepository } from '../../domain/repositories/task.repository';

export class ListTasksUseCase {
  constructor(private readonly repository: TaskRepository) {}
  execute(): Promise<Task[]> { return this.repository.findAll(); }
}
