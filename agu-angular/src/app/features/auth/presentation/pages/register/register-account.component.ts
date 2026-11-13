import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, ValidationErrors, ValidatorFn, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { RouterLink } from '@angular/router';
import { UiFormActionsComponent } from '../../../../../shared/ui/form-actions/form-actions.component';
import { UiFormFieldComponent } from '../../../../../shared/ui/form-field/form-field.component';
import { LoginBrandComponent } from '../../components/login-brand/login-brand.component';
import { RegisterAccountStore } from '../../state/register-account.store';

const matchingPasswords: ValidatorFn = (control: AbstractControl): ValidationErrors | null => {
  const password = control.get('password')?.value;
  const confirmation = control.get('confirmPassword')?.value;
  return !confirmation || password === confirmation ? null : { passwordMismatch: true };
};

@Component({
  selector: 'app-register-account',
  standalone: true,
  imports: [ReactiveFormsModule, MatButtonModule, RouterLink, UiFormFieldComponent, UiFormActionsComponent, LoginBrandComponent],
  providers: [RegisterAccountStore],
  templateUrl: './register-account.component.html',
  styleUrl: './register-account.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RegisterAccountComponent {
  readonly store = inject(RegisterAccountStore);
  private readonly formBuilder = inject(FormBuilder);
  private readonly _submitAttempted = signal(false);

  readonly submitAttempted = this._submitAttempted.asReadonly();
  readonly form = this.formBuilder.nonNullable.group({
    username: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(50)]],
    email: ['', [Validators.required, Validators.email, Validators.maxLength(180)]],
    password: ['', [Validators.required, Validators.minLength(8), Validators.maxLength(72)]],
    confirmPassword: ['', [Validators.required, Validators.maxLength(72)]],
  }, { validators: matchingPasswords });

  async register(): Promise<void> {
    this._submitAttempted.set(true);
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const { username, email, password } = this.form.getRawValue();
    await this.store.register({ username: username.trim(), email: email.trim(), password });
  }
}
