import { TaskStatus } from '../value-objects/task-status.vo';

export interface TaskStatusEvent {
  readonly id: string;
  readonly taskId: string;
  readonly status: TaskStatus;
  readonly occurredAt: string;
}

export interface TaskRealtimeRepository {
  connect(onEvent: (event: TaskStatusEvent) => void, onConnected: () => void): () => void;
}
