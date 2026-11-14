import { SessionRepository } from '../../domain/repositories/session.repository';

export class LogoutUseCase {
  constructor(private readonly sessionRepository: SessionRepository) {}

  execute(): void { this.sessionRepository.clear(); }
}
