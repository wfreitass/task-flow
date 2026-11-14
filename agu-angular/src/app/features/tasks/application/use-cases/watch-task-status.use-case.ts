import { TaskRealtimeRepository, TaskStatusEvent } from '../../domain/repositories/task-realtime.repository';

export class WatchTaskStatusUseCase {
  constructor(private readonly realtime: TaskRealtimeRepository) {}
  execute(onEvent: (event: TaskStatusEvent) => void, onConnected: () => void): () => void {
    return this.realtime.connect(onEvent, onConnected);
  }
}
