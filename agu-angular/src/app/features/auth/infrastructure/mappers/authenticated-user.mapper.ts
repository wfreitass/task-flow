import { AuthenticatedUser } from '../../domain/models/authenticated-user';
import { CurrentUserResponseDto } from '../dto/current-user-response.dto';

export class AuthenticatedUserMapper {
  static toDomain(payload: unknown): AuthenticatedUser {
    if (typeof payload !== 'object' || payload === null || !('id' in payload) || !('username' in payload) || !('email' in payload)) {
      throw new Error('A API retornou um perfil inválido.');
    }

    const user = payload as CurrentUserResponseDto;
    if (
      typeof user.id !== 'number' || !Number.isInteger(user.id) || user.id < 1 ||
      typeof user.username !== 'string' || user.username.trim().length === 0 ||
      typeof user.email !== 'string' || user.email.trim().length === 0 ||
      !user.profile || typeof user.profile !== 'object' || !('id' in user.profile) || !('code' in user.profile) || !('name' in user.profile) ||
      typeof user.profile.id !== 'number' || !Number.isSafeInteger(user.profile.id) || user.profile.id < 1 ||
      typeof user.profile.code !== 'string' || typeof user.profile.name !== 'string' ||
      !Array.isArray(user.roles) || !user.roles.every((role: unknown) => typeof role === 'string')
    ) {
      throw new Error('A API retornou um perfil inválido.');
    }

    return { id: user.id, username: user.username, email: user.email, profile: { id: user.profile.id, code: user.profile.code, name: user.profile.name }, roles: [...user.roles] };
  }
}
