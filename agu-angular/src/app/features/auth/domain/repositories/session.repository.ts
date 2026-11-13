import { AuthSession } from '../models/auth-session';

export interface SessionRepository {
  save(session: AuthSession): void;
}
