import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialogModule } from '@angular/material/dialog';

export interface UiConfirmDialogData { readonly title: string; readonly message: string; readonly confirmLabel: string; }

@Component({
  selector: 'app-ui-confirm-dialog',
  standalone: true,
  imports: [MatDialogModule, MatButtonModule],
  templateUrl: './confirm-dialog.component.html',
  styleUrl: './confirm-dialog.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UiConfirmDialogComponent {
  readonly data = inject<UiConfirmDialogData>(MAT_DIALOG_DATA);
}
