import { ChangeDetectionStrategy, Component, DestroyRef, Input, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { Router, RouterLink } from '@angular/router';
import { UiFormActionsComponent } from '../../../../../shared/ui/form-actions/form-actions.component';
import { UiFormFieldComponent } from '../../../../../shared/ui/form-field/form-field.component';
import { LoginCredentials } from '../../../domain/models/login-credentials';
import { LoginStore } from '../../state/login.store';
import { LoginBrandComponent } from '../../components/login-brand/login-brand.component';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    UiFormFieldComponent,
    UiFormActionsComponent,
    RouterLink,
    LoginBrandComponent,
  ],
  providers: [LoginStore],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LoginComponent {
  @Input({ required: true }) demoCredentials!: LoginCredentials;

  readonly store = inject(LoginStore);
  private readonly formBuilder = inject(FormBuilder);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);
  private destroyed = false;
  private readonly _submitAttempted = signal(false);

  readonly submitAttempted = this._submitAttempted.asReadonly();
  readonly form = this.formBuilder.nonNullable.group({
    username: ['', Validators.required],
    password: ['', Validators.required],
  });

  constructor() {
    this.destroyRef.onDestroy(() => { this.destroyed = true; });
  }

  fillDemoCredentials(): void {
    this.form.setValue(this.demoCredentials);
    this._submitAttempted.set(false);
    this.store.clearError();
  }

  async login(): Promise<void> {
    this._submitAttempted.set(true);
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const authenticated = await this.store.login(this.form.getRawValue());

    if (authenticated && !this.destroyed) {
      await this.router.navigateByUrl('/tasks');
    }
  }
}
