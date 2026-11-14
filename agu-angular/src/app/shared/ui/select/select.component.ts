import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatSelectModule } from '@angular/material/select';

export interface UiSelectOption<T extends string> { readonly value: T; readonly label: string; }

@Component({
  selector: 'app-ui-select',
  standalone: true,
  imports: [ReactiveFormsModule, MatFormFieldModule, MatSelectModule],
  templateUrl: './select.component.html',
  styleUrl: './select.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UiSelectComponent<T extends string> {
  @Input({ required: true }) control!: FormControl<T>;
  @Input({ required: true }) label!: string;
  @Input({ required: true }) options!: readonly UiSelectOption<T>[];
  @Input() submitted = false;
}
