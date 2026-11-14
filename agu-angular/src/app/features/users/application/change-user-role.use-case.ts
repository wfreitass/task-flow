import { UserProfile, UserRepository } from '../domain/user';

export class ChangeUserRoleUseCase {
  constructor(private readonly users: UserRepository) {}
  load(id: number): Promise<[UserProfile, readonly UserProfile[]]> {
    return Promise.all([this.users.getProfile(id), this.users.listProfiles()]);
  }
  execute(id: number, profileId: number): Promise<void> { return this.users.changeProfile(id, profileId); }
}
