export interface User {
  readonly id: number;
  readonly username: string;
}

export interface UserRepository {
  findAll(): Promise<readonly User[]>;
}
