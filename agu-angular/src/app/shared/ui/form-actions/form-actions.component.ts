import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';

@Component({
  selector: 'app-ui-form-actions',
  standalone: true,
  imports: [MatButtonModule, MatProgressSpinnerModule],
  template: `
    <div class="actions" [class.full-width]="fullWidth">
      @if (showCancel) {
        <button mat-button type="button" [disabled]="busy" (click)="cancelled.emit()">{{ cancelLabel }}</button>
      }
      <button mat-flat-button color="primary" type="submit" [disabled]="busy">
        @if (busy) { <mat-spinner diameter="16" aria-hidden="true" /> }
        {{ busy ? busyLabel : submitLabel }}
        @if (!busy && submitSuffix) { <span class="submit-suffix" aria-hidden="true">{{ submitSuffix }}</span> }
      </button>
    </div>
  `,
  styles: ['.actions{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap}button{min-height:42px;border-radius:8px;letter-spacing:0}.full-width{display:block}.full-width button{width:100%;min-height:49px;font-size:13px}.submit-suffix{display:inline-block;margin-left:14px}mat-spinner{display:inline-block;margin-right:8px;vertical-align:middle}'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UiFormActionsComponent {
  @Input() busy = false;
  @Input() submitLabel = 'Salvar';
  @Input() busyLabel = 'Salvando...';
  @Input() submitSuffix = '';
  @Input() cancelLabel = 'Cancelar';
  @Input() showCancel = true;
  @Input() fullWidth = false;
  @Output() readonly cancelled = new EventEmitter<void>();
}
