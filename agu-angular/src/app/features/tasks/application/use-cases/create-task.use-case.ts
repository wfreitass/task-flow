import { Task, TaskDetails } from '../../domain/entities/task.entity';
import { TaskAttachmentInput, TaskRepository } from '../../domain/repositories/task.repository';

export class CreateTaskUseCase {
  constructor(private readonly repository: TaskRepository) {}
  execute(details: TaskDetails, attachments: readonly TaskAttachmentInput[] = []): Promise<Task> { return this.repository.create(details, attachments); }
}
