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
  templateUrl: './date-field.component.html',
  styleUrl: './date-field.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UiDateFieldComponent {
  @Input({ required: true }) control!: FormControl<Date | null>;
  @Input({ required: true }) label!: string;
  @Input() hint = '';
}
