import { LoginCredentials } from '../../domain/models/login-credentials';
import { AuthRepository } from '../../domain/repositories/auth.repository';
import { SessionRepository } from '../../domain/repositories/session.repository';

export class LoginUseCase {
  constructor(
    private readonly authRepository: AuthRepository,
    private readonly sessionRepository: SessionRepository,
  ) {}

  async execute(credentials: LoginCredentials): Promise<void> {
    const session = await this.authRepository.login(credentials);
    this.sessionRepository.save(session);
  }
}
