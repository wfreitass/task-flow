export interface TaskActivity {
  readonly id: number;
  readonly actorId: number;
  readonly actorName: string | null;
  readonly oldAssigneeName: string | null;
  readonly newAssigneeName: string | null;
  readonly kind: string;
  readonly content: string | null;
  readonly oldValue: Readonly<Record<string, unknown>> | null;
  readonly newValue: Readonly<Record<string, unknown>> | null;
  readonly createdAt: string;
}

export interface TaskActivityRepository {
  listComments(taskId: string): Promise<readonly TaskActivity[]>;
  listHistory(taskId: string): Promise<readonly TaskActivity[]>;
  addComment(taskId: string, content: string): Promise<void>;
}
