import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatTableModule } from '@angular/material/table';

export interface UiTableColumn<T> {
  readonly key: string;
  readonly label: string;
  readonly value: (row: T) => string;
}

@Component({
  selector: 'app-ui-table',
  standalone: true,
  imports: [MatTableModule, MatButtonModule],
  templateUrl: './table.component.html',
  styleUrl: './table.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UiTableComponent<T> {
  @Input({ required: true }) rows!: readonly T[];
  @Input({ required: true }) columns!: readonly UiTableColumn<T>[];
  @Input() caption = 'Dados';
  @Input() actionLabel = '';
  @Input() emptyMessage = 'Nenhum registro encontrado.';
  @Output() readonly rowAction = new EventEmitter<T>();

  get displayedColumns(): string[] {
    return [...this.columns.map(column => column.key), ...(this.actionLabel ? ['ui-actions'] : [])];
  }
}
