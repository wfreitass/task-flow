import { RegisteredAccount } from '../../domain/models/registered-account';
import { RegisteredAccountResponseDto } from '../dto/registered-account-response.dto';

export class RegisteredAccountMapper {
  static toDomain(payload: unknown): RegisteredAccount {
    if (typeof payload !== 'object' || payload === null || !('id' in payload) || !('username' in payload) || !('email' in payload)) {
      throw new Error('A API retornou uma conta inválida.');
    }

    const response = payload as RegisteredAccountResponseDto;
    if (
      typeof response.id !== 'number' || !Number.isInteger(response.id) || response.id < 1 ||
      typeof response.username !== 'string' || typeof response.email !== 'string'
    ) {
      throw new Error('A API retornou uma conta inválida.');
    }

    return { id: response.id, username: response.username, email: response.email };
  }
}
