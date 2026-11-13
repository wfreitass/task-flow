export const TASK_STATUSES = ['backlog', 'todo', 'in_progress', 'blocked', 'done', 'canceled'] as const;
export type TaskStatus = typeof TASK_STATUSES[number];

export function isTaskStatus(value: unknown): value is TaskStatus {
  return typeof value === 'string' && TASK_STATUSES.some(status => status === value);
}
