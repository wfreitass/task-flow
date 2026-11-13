import { TaskRepository } from '../../domain/repositories/task.repository';

export class DeleteTaskUseCase {
  constructor(private readonly repository: TaskRepository) {}
  execute(id: string): Promise<void> { return this.repository.remove(id); }
}
