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
  template: `
    <mat-form-field appearance="outline" [subscriptSizing]="subscriptSizing">
      <mat-label>{{ label }}</mat-label>
      @if (multiline) {
        <textarea matInput [id]="inputId || defaultId" [formControl]="control" [rows]="rows" [placeholder]="placeholder" [readonly]="readonly" [attr.minlength]="minLength" [attr.maxlength]="maxLength" [attr.name]="name" [attr.spellcheck]="spellcheck" [attr.autocapitalize]="autocapitalize"></textarea>
      } @else {
        <input matInput [id]="inputId || defaultId" [formControl]="control" [type]="type === 'password' && !passwordHidden() ? 'text' : type" [placeholder]="placeholder" [readonly]="readonly" [attr.minlength]="minLength" [attr.maxlength]="maxLength" [attr.name]="name" [attr.autocomplete]="autocomplete" [attr.spellcheck]="spellcheck" [attr.autocapitalize]="autocapitalize">
      }
      @if (type === 'password' && !multiline) {
        <button mat-icon-button matSuffix type="button" [attr.aria-label]="passwordHidden() ? 'Mostrar senha' : 'Ocultar senha'" [attr.aria-pressed]="!passwordHidden()" [attr.aria-controls]="inputId || defaultId" [disabled]="readonly || control.disabled" (click)="togglePasswordVisibility()">
          <svg class="eye-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"/>
            @if (!passwordHidden()) { <path d="m4 3 16 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/> }
          </svg>
        </button>
      }
      @if (hint) { <mat-hint>{{ hint }}</mat-hint> }
      @if (control.hasError('required')) {
        <mat-error>{{ requiredMessage }}</mat-error>
      } @else if (control.hasError('email')) {
        <mat-error>{{ emailMessage }}</mat-error>
      } @else if (control.hasError('minlength')) {
        <mat-error>{{ minLengthMessage }}</mat-error>
      } @else if (control.hasError('pattern')) {
        <mat-error>{{ patternMessage || requiredMessage }}</mat-error>
      } @else if (control.hasError('maxlength')) {
        <mat-error>Use no máximo {{ maxLength }} caracteres.</mat-error>
      }
    </mat-form-field>
  `,
  styles: [':host,mat-form-field{display:block;width:100%}mat-form-field{font-size:inherit}textarea{resize:vertical;min-height:90px}.eye-icon{display:block;width:20px;height:20px;margin:auto;color:#78847b}'],
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
