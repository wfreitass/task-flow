export interface User {
  readonly id: number;
  readonly username: string;
}

export interface UserProfile {
  readonly id: number;
  readonly code: string;
  readonly name: string;
}

export interface UserRepository {
  findAll(): Promise<readonly User[]>;
  getProfile(id: number): Promise<UserProfile>;
  listProfiles(): Promise<readonly UserProfile[]>;
  changeProfile(id: number, profileId: number): Promise<void>;
}
