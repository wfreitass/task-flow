import { RegisterAccountDetails } from '../../domain/models/register-account-details';
import { RegisterAccountRequestDto } from '../dto/register-account-request.dto';

export class RegisterAccountRequestMapper {
  static toDto(details: RegisterAccountDetails): RegisterAccountRequestDto {
    return {
      username: details.username,
      email: details.email,
      password: details.password,
    };
  }
}
