import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatSelectModule } from '@angular/material/select';

export interface UiSelectOption<T extends string> { readonly value: T; readonly label: string; }

@Component({
  selector: 'app-ui-select',
  standalone: true,
  imports: [ReactiveFormsModule, MatFormFieldModule, MatSelectModule],
  template: `
    <mat-form-field appearance="outline" subscriptSizing="dynamic">
      <mat-label>{{ label }}</mat-label>
      <mat-select [formControl]="control">
        @for (option of options; track option.value) { <mat-option [value]="option.value">{{ option.label }}</mat-option> }
      </mat-select>
      @if (control.hasError('required')) { <mat-error>Selecione uma opção.</mat-error> }
    </mat-form-field>
  `,
  styles: [':host,mat-form-field{display:block;width:100%}'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UiSelectComponent<T extends string> {
  @Input({ required: true }) control!: FormControl<T>;
  @Input({ required: true }) label!: string;
  @Input({ required: true }) options!: readonly UiSelectOption<T>[];
  @Input() submitted = false;
}
