import { Injectable, inject } from '@angular/core';
import { SessionTokenStorage } from '../../../../core/auth/session-token.storage';
import { AuthSession } from '../../domain/models/auth-session';
import { SessionRepository } from '../../domain/repositories/session.repository';

@Injectable()
export class BrowserSessionRepository implements SessionRepository {
  private readonly storage = inject(SessionTokenStorage);

  save(session: AuthSession): void {
    this.storage.setToken(session.token);
  }
}
