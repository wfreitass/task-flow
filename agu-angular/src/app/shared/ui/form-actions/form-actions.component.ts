import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';

@Component({
  selector: 'app-ui-form-actions',
  standalone: true,
  imports: [MatButtonModule, MatProgressSpinnerModule],
  templateUrl: './form-actions.component.html',
  styleUrl: './form-actions.component.scss',
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
