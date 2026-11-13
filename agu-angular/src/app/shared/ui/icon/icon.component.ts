import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

export type UiIconName = 'check' | 'board' | 'table' | 'plus' | 'search' | 'logout' | 'close' | 'edit' | 'trash' | 'arrow-left';
const ICON_PATHS: Record<UiIconName, string> = {
  check: 'm5 12 4 4L19 6',
  board: 'M4 4h16v16H4zM9 4v16M15 4v16M6.5 8h0M11.5 11h0M17.5 7h0',
  table: 'M4 4h16v16H4zM4 9h16M4 14h16M10 9v11',
  plus: 'M12 5v14M5 12h14',
  search: 'M10.5 17a6.5 6.5 0 1 0 0-13 6.5 6.5 0 0 0 0 13Zm5-1.5L21 21',
  logout: 'M14 4H5v16h9M10 12h12m-4-4 4 4-4 4',
  close: 'm6 6 12 12M6 18 18 6',
  edit: 'm14 5 5 5M4 20l5-1L20 8a3.5 3.5 0 0 0-5-5L4 14v6Z',
  trash: 'M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7',
  'arrow-left': 'M20 12H4m6-6-6 6 6 6',
};

@Component({
  selector: 'app-ui-icon',
  standalone: true,
  template: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path [attr.d]="path" /></svg>',
  styles: [':host{display:inline-flex;flex-shrink:0;width:20px;height:20px;vertical-align:middle}svg{width:100%;height:100%}'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UiIconComponent {
  @Input({ required: true }) name!: UiIconName;
  get path(): string { return ICON_PATHS[this.name]; }
}
