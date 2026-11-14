import { TaskActivity, TaskActivityRepository } from '../../domain/repositories/task-activity.repository';

export class TaskActivityUseCase {
  constructor(private readonly repository: TaskActivityRepository) {}
  comments(taskId: string): Promise<readonly TaskActivity[]> { return this.repository.listComments(taskId); }
  history(taskId: string): Promise<readonly TaskActivity[]> { return this.repository.listHistory(taskId); }
  addComment(taskId: string, content: string): Promise<void> { return this.repository.addComment(taskId, content); }
}
