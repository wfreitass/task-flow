import { AuthenticatedUser } from '../models/authenticated-user';

export interface CurrentUserRepository {
  getCurrent(): Promise<AuthenticatedUser>;
}
