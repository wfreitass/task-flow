import { Task, TaskDetails } from '../../domain/entities/task.entity';
import { TaskRepository } from '../../domain/repositories/task.repository';

export class LoadTaskExamplesUseCase {
  constructor(
    private readonly repository: TaskRepository,
    private readonly examples: readonly TaskDetails[],
  ) {}

  async execute(): Promise<Task[]> {
    const existing = await this.repository.findAll();
    if (existing.length) return existing;
    for (const details of this.examples) await this.repository.create(details);
    return this.repository.findAll();
  }
}
