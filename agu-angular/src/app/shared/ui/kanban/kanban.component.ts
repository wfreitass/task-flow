import { DOCUMENT } from '@angular/common';
import { AfterViewInit, ChangeDetectionStrategy, Component, ElementRef, EventEmitter, Input, NgZone, OnChanges, OnDestroy, Output, ViewChild, ViewEncapsulation, inject, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { JkanbanLoader } from './jkanban-loader.service';
import { JkanbanConstructor, JkanbanInstance } from './jkanban.types';
import { UiKanbanColumn, UiKanbanItem, UiKanbanMove } from './kanban.models';

let nextKanbanId = 0;

@Component({
  selector: 'app-ui-kanban',
  standalone: true,
  imports: [MatButtonModule],
  template: `
    @if (error()) {
      <div class="ui-kanban-error" role="alert">Não foi possível abrir o quadro. <button mat-button (click)="initialize()">Tentar novamente</button></div>
    }
    <div class="ui-kanban" [attr.aria-busy]="disabled" role="region" aria-label="Quadro Kanban" tabindex="0">
      <div #canvas [id]="hostId" (keydown)="onKeydown($event)"></div>
    </div>
    <p class="ui-kanban-help">Arraste pelo símbolo ⠿ para mover. Para usar o teclado, abra o cartão e altere seu status no formulário.</p>
  `,
  styleUrl: './kanban.component.scss',
  encapsulation: ViewEncapsulation.None,
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UiKanbanComponent implements AfterViewInit, OnChanges, OnDestroy {
  @Input({ required: true }) columns!: readonly UiKanbanColumn[];
  @Input() disabled = false;
  @Output() readonly itemSelected = new EventEmitter<string>();
  @Output() readonly itemMoved = new EventEmitter<UiKanbanMove>();
  @ViewChild('canvas', { static: true }) private canvas!: ElementRef<HTMLDivElement>;

  readonly hostId = `ui-kanban-${nextKanbanId++}`;
  private readonly _error = signal(false);
  readonly error = this._error.asReadonly();
  private readonly loader = inject(JkanbanLoader);
  private readonly document = inject(DOCUMENT);
  private readonly zone = inject(NgZone);
  private constructorFn: JkanbanConstructor | null = null;
  private instance: JkanbanInstance | null = null;
  private destroyed = false;

  ngAfterViewInit(): void { void this.initialize(); }
  ngOnChanges(): void { if (this.constructorFn) this.render(); }
  ngOnDestroy(): void { this.destroyed = true; this.dispose(); }

  async initialize(): Promise<void> {
    this._error.set(false);
    try {
      const constructorFn = await this.loader.load();
      if (this.destroyed) return;
      this.constructorFn = constructorFn;
      this.render();
    } catch { if (!this.destroyed) this._error.set(true); }
  }

  onKeydown(event: KeyboardEvent): void {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    const target = event.target as HTMLElement;
    const id = target.closest<HTMLElement>('.kanban-item')?.dataset['eid'];
    if (id && !this.disabled) { event.preventDefault(); this.itemSelected.emit(id); }
  }

  private render(): void {
    this.dispose();
    const constructorFn = this.constructorFn;
    if (!constructorFn || this.destroyed) return;
    const ids = new Set<string>();
    for (const column of this.columns) {
      for (const id of [column.id, ...column.items.map(item => item.id)]) {
        if (!/^[a-zA-Z0-9_-]+$/.test(id) || ids.has(id)) { this._error.set(true); return; }
        ids.add(id);
      }
    }

    this.zone.runOutsideAngular(() => {
      this.instance = new constructorFn({
        element: `#${this.hostId}`,
        gutter: '0px', widthBoard: '300px', responsive: '0', dragBoards: false, dragItems: !this.disabled,
        itemHandleOptions: {
          enabled: !this.disabled,
          handleClass: 'ui-kanban-handle',
          customHandler: '<span class="ui-kanban-handle" aria-hidden="true">⠿</span>%s',
        },
        boards: this.columns.map(column => ({
          id: column.id,
          title: this.headingHtml(column),
          class: `ui-kanban-tone-${column.tone}`,
          item: column.items.map(item => ({ id: item.id, title: this.itemHtml(item), class: ['ui-kanban-card'] })),
        })),
        click: element => {
          const id = element.dataset['eid'];
          if (id && !this.disabled) this.zone.run(() => this.itemSelected.emit(id));
        },
        dropEl: (element, target, _source, sibling) => {
          const itemId = element.dataset['eid'];
          const columnId = target.parentElement?.dataset['id'];
          if (itemId && columnId) this.zone.run(() => this.itemMoved.emit({ itemId, columnId, beforeId: sibling?.dataset['eid'] ?? null }));
        },
      });
      this.canvas.nativeElement.querySelectorAll<HTMLElement>('.kanban-item').forEach(element => {
        element.setAttribute('role', 'button');
        element.setAttribute('tabindex', this.disabled ? '-1' : '0');
        element.setAttribute('aria-label', `Abrir: ${element.querySelector('h3')?.textContent ?? ''}`);
      });
    });
  }

  private headingHtml(column: UiKanbanColumn): string {
    const title = this.document.createElement('span');
    title.className = 'ui-kanban-column-title';
    title.textContent = column.title;
    const count = this.document.createElement('span');
    count.className = 'ui-kanban-count';
    count.textContent = String(column.items.length);
    return title.outerHTML + count.outerHTML;
  }

  private itemHtml(item: UiKanbanItem): string {
    const content = this.document.createElement('div');
    const title = this.document.createElement('h3');
    title.textContent = item.title;
    content.append(title);
    if (item.description) {
      const description = this.document.createElement('p');
      description.textContent = item.description;
      content.append(description);
    }
    const footer = this.document.createElement('span');
    footer.className = 'ui-kanban-card-footer';
    footer.textContent = 'Abrir detalhes ↗';
    content.append(footer);
    return content.outerHTML;
  }

  private dispose(): void {
    if (this.instance?.drake) this.instance.drake.destroy();
    if (this.instance?.drakeBoard) this.instance.drakeBoard.destroy();
    this.instance = null;
    this.canvas?.nativeElement.replaceChildren();
  }
}
