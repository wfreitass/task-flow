import { RegisterAccountDetails } from '../models/register-account-details';
import { RegisteredAccount } from '../models/registered-account';

export interface RegistrationRepository {
  register(details: RegisterAccountDetails): Promise<RegisteredAccount>;
}
