import { AuthSession } from '../models/auth-session';
import { LoginCredentials } from '../models/login-credentials';

export interface AuthRepository {
  login(credentials: LoginCredentials): Promise<AuthSession>;
}
