import { AuthSession } from '../../domain/models/auth-session';
import { LoginResponseDto } from '../dto/login-response.dto';

export class AuthSessionMapper {
  static toDomain(payload: unknown): AuthSession {
    if (typeof payload !== 'object' || payload === null || !('token' in payload)) {
      throw new Error('A API retornou uma sessão inválida.');
    }

    const token = (payload as LoginResponseDto).token;
    if (typeof token !== 'string' || token.trim().length === 0) {
      throw new Error('A API retornou uma sessão inválida.');
    }

    return { token };
  }
}
