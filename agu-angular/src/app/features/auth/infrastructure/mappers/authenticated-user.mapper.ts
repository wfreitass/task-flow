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
      typeof user.email !== 'string' || user.email.trim().length === 0
    ) {
      throw new Error('A API retornou um perfil inválido.');
    }

    return { id: user.id, username: user.username, email: user.email };
  }
}
