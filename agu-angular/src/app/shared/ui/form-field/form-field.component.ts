import { ChangeDetectionStrategy, Component, Input, signal } from '@angular/core';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';

let nextFieldId = 0;

@Component({
  selector: 'app-ui-form-field',
  standalone: true,
  imports: [ReactiveFormsModule, MatButtonModule, MatFormFieldModule, MatInputModule],
  templateUrl: './form-field.component.html',
  styleUrl: './form-field.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UiFormFieldComponent {
  @Input({ required: true }) control!: FormControl<string>;
  @Input({ required: true }) label!: string;
  @Input() name = '';
  @Input() inputId = '';
  @Input() placeholder = '';
  @Input() type: 'text' | 'email' | 'password' | 'search' | 'date' = 'text';
  @Input() multiline = false;
  @Input() rows = 4;
  @Input() readonly = false;
  @Input() minLength: number | null = null;
  @Input() maxLength: number | null = null;
  @Input() hint = '';
  @Input() autocomplete = 'off';
  @Input() autocapitalize: string | null = null;
  @Input() spellcheck: boolean | null = null;
  @Input() subscriptSizing: 'dynamic' | 'fixed' = 'dynamic';
  @Input() requiredMessage = 'Preencha este campo.';
  @Input() emailMessage = 'Informe um e-mail válido.';
  @Input() minLengthMessage = 'O valor informado é muito curto.';
  @Input() patternMessage = '';
  // A mudança deste input atualiza controles OnPush após o submit do formulário pai.
  @Input() submitted = false;

  readonly defaultId = `ui-form-field-${nextFieldId++}`;
  private readonly _passwordHidden = signal(true);
  readonly passwordHidden = this._passwordHidden.asReadonly();

  togglePasswordVisibility(): void {
    this._passwordHidden.update(hidden => !hidden);
  }
}
