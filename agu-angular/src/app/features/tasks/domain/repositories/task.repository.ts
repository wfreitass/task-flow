import { Task, TaskDetails } from '../entities/task.entity';
import { TaskStatus } from '../value-objects/task-status.vo';

export interface TaskRepository {
  findAll(): Promise<Task[]>;
  create(details: TaskDetails): Promise<Task>;
  update(id: string, details: TaskDetails): Promise<Task>;
  remove(id: string): Promise<void>;
  move(id: string, status: TaskStatus, beforeId: string | null): Promise<Task[]>;
}
