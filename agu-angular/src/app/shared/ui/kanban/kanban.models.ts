export interface UiKanbanItem {
  readonly id: string;
  readonly title: string;
  readonly description: string;
}

export interface UiKanbanColumn {
  readonly id: string;
  readonly title: string;
  readonly tone: 'neutral' | 'amber' | 'green';
  readonly items: readonly UiKanbanItem[];
}

export interface UiKanbanMove {
  readonly itemId: string;
  readonly columnId: string;
  readonly beforeId: string | null;
}
