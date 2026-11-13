import { Task, TaskDetails } from '../../domain/entities/task.entity';
import { isTaskStatus, TaskStatus } from '../../domain/value-objects/task-status.vo';
import { TaskMutationRequestDto, TaskResponseDto } from '../dto/task-response.dto';

export class TaskApiMapper {
  static toDomain(dto: TaskResponseDto): Task {
    if (!Number.isInteger(dto.id) || !isTaskStatus(dto.status)) throw new Error('A API retornou uma tarefa com identificadores ou status inválidos.');
    return Task.create(String(dto.id), {
      title: dto.title,
      description: dto.description ?? '',
      priority: dto.priority as Task['priority'],
      status: dto.status,
      createdBy: dto.createdBy,
      assignee: dto.assignee,
      startAt: TaskApiMapper.toInputDate(dto.startAt),
      dueAt: TaskApiMapper.toInputDate(dto.dueAt),
    });
  }

  static toMutation(details: TaskDetails): TaskMutationRequestDto {
    return {
      title: details.title,
      description: details.description.trim() || null,
      priority: details.priority ?? 'medium',
      startAt: TaskApiMapper.toApiDate(details.startAt),
      dueAt: TaskApiMapper.toApiDate(details.dueAt),
      assignedTo: details.assignee?.id ?? null,
    };
  }

  static toStatus(status: TaskStatus): { readonly status: TaskStatus } { return { status }; }

  private static toInputDate(value: string | null): string {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) throw new Error('A API retornou uma data inválida para a tarefa.');
    return date.toISOString().slice(0, 10);
  }

  private static toApiDate(value?: string): string | null {
    return value ? `${value}T00:00:00+00:00` : null;
  }
}
