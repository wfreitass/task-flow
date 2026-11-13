import { Injectable, inject, signal } from '@angular/core';
import { LoginUseCase } from '../../application/use-cases/login.use-case';
import { InvalidCredentialsError } from '../../domain/errors/invalid-credentials.error';
import { LoginCredentials } from '../../domain/models/login-credentials';

@Injectable()
export class LoginStore {
  private readonly loginUseCase = inject(LoginUseCase);
  private readonly _loading = signal(false);
  private readonly _error = signal<string | null>(null);

  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();

  clearError(): void {
    this._error.set(null);
  }

  async login(credentials: LoginCredentials): Promise<boolean> {
    if (this._loading()) {
      return false;
    }

    this._loading.set(true);
    this._error.set(null);

    try {
      await this.loginUseCase.execute(credentials);
      return true;
    } catch (error: unknown) {
      this._error.set(
        error instanceof InvalidCredentialsError
          ? 'E-mail ou senha incorretos. Confira os dados e tente novamente.'
          : 'Não foi possível entrar. Tente novamente e verifique se o navegador permite salvar a sessão.',
      );
      return false;
    } finally {
      this._loading.set(false);
    }
  }
}
