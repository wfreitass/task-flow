import { RegisterAccountDetails } from '../../domain/models/register-account-details';
import { RegisteredAccount } from '../../domain/models/registered-account';
import { RegistrationRepository } from '../../domain/repositories/registration.repository';

export class RegisterAccountUseCase {
  constructor(private readonly repository: RegistrationRepository) {}

  execute(details: RegisterAccountDetails): Promise<RegisteredAccount> {
    return this.repository.register(details);
  }
}
