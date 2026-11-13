export const TASK_STATUSES = ['pending', 'in_progress', 'completed'] as const;
export type TaskStatus = typeof TASK_STATUSES[number];

export function isTaskStatus(value: unknown): value is TaskStatus {
  return typeof value === 'string' && TASK_STATUSES.some(status => status === value);
}
