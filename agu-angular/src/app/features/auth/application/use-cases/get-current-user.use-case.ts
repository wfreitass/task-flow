import { AuthenticatedUser } from '../../domain/models/authenticated-user';
import { CurrentUserRepository } from '../../domain/repositories/current-user.repository';

export class GetCurrentUserUseCase {
  constructor(private readonly repository: CurrentUserRepository) {}

  execute(): Promise<AuthenticatedUser> {
    return this.repository.getCurrent();
  }
}
