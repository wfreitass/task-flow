import { Injectable, inject, signal } from '@angular/core';
import { RegisterAccountUseCase } from '../../application/use-cases/register-account.use-case';
import { AccountAlreadyExistsError } from '../../domain/errors/account-already-exists.error';
import { RegistrationRejectedError } from '../../domain/errors/registration-rejected.error';
import { RegisterAccountDetails } from '../../domain/models/register-account-details';

@Injectable()
export class RegisterAccountStore {
  private readonly registerAccount = inject(RegisterAccountUseCase);
  private readonly _loading = signal(false);
  private readonly _error = signal<string | null>(null);
  private readonly _registered = signal(false);

  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();
  readonly registered = this._registered.asReadonly();

  clearError(): void {
    this._error.set(null);
  }

  async register(details: RegisterAccountDetails): Promise<boolean> {
    if (this._loading() || this._registered()) {
      return false;
    }

    this._loading.set(true);
    this._error.set(null);

    try {
      await this.registerAccount.execute(details);
      this._registered.set(true);
      return true;
    } catch (error: unknown) {
      this._error.set(this.messageFor(error));
      return false;
    } finally {
      this._loading.set(false);
    }
  }

  private messageFor(error: unknown): string {
    if (error instanceof AccountAlreadyExistsError) {
      return error.field === 'username'
        ? 'Este nome de usuário já está em uso.'
        : 'Este e-mail já está cadastrado.';
    }

    if (error instanceof RegistrationRejectedError) {
      return 'Confira os dados informados: usuário de 3 a 50 caracteres, e-mail válido e senha de 8 a 72 caracteres.';
    }

    return 'Não foi possível criar sua conta. Verifique sua conexão e tente novamente.';
  }
}
