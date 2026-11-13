import { Injectable } from '@angular/core';
import { InvalidCredentialsError } from '../../domain/errors/invalid-credentials.error';
import { AuthSession } from '../../domain/models/auth-session';
import { LoginCredentials } from '../../domain/models/login-credentials';
import { AuthRepository } from '../../domain/repositories/auth.repository';
import { DEMO_CREDENTIALS } from './demo-credentials';

@Injectable()
export class MockAuthRepository implements AuthRepository {
  async login(credentials: LoginCredentials): Promise<AuthSession> {
    await new Promise<void>(resolve => setTimeout(resolve, 600));

    if (
      credentials.username !== DEMO_CREDENTIALS.username ||
      credentials.password !== DEMO_CREDENTIALS.password
    ) {
      throw new InvalidCredentialsError();
    }

    // Identificador opaco de demonstração: não é um JWT nem autoriza acesso à API.
    return { token: 'task-flow-demo-session' };
  }
}
