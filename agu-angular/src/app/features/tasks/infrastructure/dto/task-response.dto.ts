export interface TaskResponseDto {
  readonly id: number;
  readonly createdBy: number;
  readonly assignee: { readonly id: number; readonly username: string } | null;
  readonly title: string;
  readonly description: string | null;
  readonly priority: string;
  readonly status: string;
  readonly startAt: string | null;
  readonly dueAt: string | null;
  readonly completedAt: string | null;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface TaskPageResponseDto {
  readonly data: readonly TaskResponseDto[];
  readonly meta: { readonly page: number; readonly perPage: number; readonly total: number; readonly totalPages: number };
}

export interface TaskMutationRequestDto {
  readonly title: string;
  readonly description: string | null;
  readonly priority: string;
  readonly startAt: string | null;
  readonly dueAt: string | null;
  readonly assignedTo: number | null;
}

export interface TaskApiErrorDto {
  readonly error?: { readonly message?: string; readonly details?: Record<string, unknown> };
}
