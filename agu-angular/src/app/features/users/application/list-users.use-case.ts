import { User, UserRepository } from '../domain/user';

export class ListUsersUseCase {
  constructor(private readonly users: UserRepository) {}
  execute(): Promise<readonly User[]> { return this.users.findAll(); }
}
