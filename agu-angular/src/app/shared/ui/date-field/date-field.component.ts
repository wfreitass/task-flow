import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { MAT_DATE_LOCALE, MatNativeDateModule } from '@angular/material/core';
import { MatButtonModule } from '@angular/material/button';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';

@Component({
  selector: 'app-ui-date-field',
  standalone: true,
  imports: [ReactiveFormsModule, MatButtonModule, MatDatepickerModule, MatNativeDateModule, MatFormFieldModule, MatInputModule],
  providers: [{ provide: MAT_DATE_LOCALE, useValue: 'pt-BR' }],
  template: `
    <mat-form-field appearance="outline" subscriptSizing="dynamic">
      <mat-label>{{ label }}</mat-label>
      <input matInput [matDatepicker]="picker" [formControl]="control" readonly (click)="picker.open()">
      <mat-datepicker-toggle matIconSuffix [for]="picker" />
      <mat-datepicker #picker />
      @if (hint) { <mat-hint>{{ hint }}</mat-hint> }
    </mat-form-field>
    @if (control.value && !control.disabled) {
      <button mat-button class="clear-date" type="button" [attr.aria-label]="'Limpar ' + label" (click)="control.setValue(null)">Limpar data</button>
    }
  `,
  styles: [':host,mat-form-field{display:block;width:100%;min-width:0}.clear-date{margin-top:4px;font-size:12px}'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UiDateFieldComponent {
  @Input({ required: true }) control!: FormControl<Date | null>;
  @Input({ required: true }) label!: string;
  @Input() hint = '';
}
