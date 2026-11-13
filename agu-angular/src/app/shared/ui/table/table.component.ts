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
  template: `
    <div class="table-scroll" role="region" [attr.aria-label]="caption" tabindex="0">
      <table mat-table [dataSource]="rows">
        <caption>{{ caption }}</caption>
        @for (column of columns; track column.key) {
          <ng-container [matColumnDef]="column.key">
            <th mat-header-cell *matHeaderCellDef scope="col">{{ column.label }}</th>
            <td mat-cell *matCellDef="let row">{{ column.value(row) }}</td>
          </ng-container>
        }
        @if (actionLabel) {
          <ng-container matColumnDef="ui-actions">
            <th mat-header-cell *matHeaderCellDef scope="col">Ações</th>
            <td mat-cell *matCellDef="let row"><button mat-button color="primary" (click)="rowAction.emit(row)">{{ actionLabel }}</button></td>
          </ng-container>
        }
        <tr mat-header-row *matHeaderRowDef="displayedColumns"></tr>
        <tr mat-row *matRowDef="let row; columns: displayedColumns"></tr>
        <tr *matNoDataRow><td [attr.colspan]="displayedColumns.length" class="no-data">{{ emptyMessage }}</td></tr>
      </table>
    </div>
  `,
  styles: [`
    :host{display:block;min-width:0}.table-scroll{overflow:auto;border:1px solid #e3e9e3;border-radius:12px;background:white}
    table{width:100%;min-width:580px}caption{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}
    th{background:#f8faf7!important;color:#718071;font-size:11px;text-transform:uppercase;letter-spacing:.7px}
    td{color:#344736;font-size:13px;max-width:360px;overflow-wrap:anywhere}button{letter-spacing:0}.no-data{padding:36px;text-align:center}
  `],
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
