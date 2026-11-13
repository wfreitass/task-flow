export interface JkanbanBoard {
  id: string;
  title: string;
  class: string;
  item: { id: string; title: string; class: string[] }[];
}

export interface JkanbanInstance {
  drake: { destroy(): void } | '';
  drakeBoard: { destroy(): void } | '';
}

export interface JkanbanOptions {
  element: string;
  boards: JkanbanBoard[];
  gutter: string;
  widthBoard: string;
  responsive: string;
  dragBoards: boolean;
  dragItems: boolean;
  itemHandleOptions: { enabled: boolean; handleClass: string; customHandler: string };
  click: (element: HTMLElement) => void;
  dropEl: (element: HTMLElement, target: HTMLElement, source: HTMLElement, sibling: HTMLElement | null) => void;
}

export type JkanbanConstructor = new (options: JkanbanOptions) => JkanbanInstance;
