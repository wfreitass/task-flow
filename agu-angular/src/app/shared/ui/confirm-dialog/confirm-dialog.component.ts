import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialogModule } from '@angular/material/dialog';

export interface UiConfirmDialogData { readonly title: string; readonly message: string; readonly confirmLabel: string; }

@Component({
  selector: 'app-ui-confirm-dialog',
  standalone: true,
  imports: [MatDialogModule, MatButtonModule],
  template: `
    <h2 mat-dialog-title>{{ data.title }}</h2>
    <mat-dialog-content><p>{{ data.message }}</p></mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button [mat-dialog-close]="false">Cancelar</button>
      <button mat-flat-button color="warn" [mat-dialog-close]="true">{{ data.confirmLabel }}</button>
    </mat-dialog-actions>
  `,
  styles: ['p{line-height:1.7;color:#68756a}mat-dialog-actions{padding:16px 24px 24px;gap:8px}button{letter-spacing:0;border-radius:8px}'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UiConfirmDialogComponent {
  readonly data = inject<UiConfirmDialogData>(MAT_DIALOG_DATA);
}
