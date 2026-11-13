import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { apiUrl } from '../../../../core/config/api.config';
import { AccountAlreadyExistsError } from '../../domain/errors/account-already-exists.error';
import { RegistrationRejectedError } from '../../domain/errors/registration-rejected.error';
import { RegisterAccountDetails } from '../../domain/models/register-account-details';
import { RegisteredAccount } from '../../domain/models/registered-account';
import { RegistrationRepository } from '../../domain/repositories/registration.repository';
import { RegisteredAccountResponseDto } from '../dto/registered-account-response.dto';
import { RegisterAccountRequestMapper } from '../mappers/register-account-request.mapper';
import { RegisteredAccountMapper } from '../mappers/registered-account.mapper';

@Injectable()
export class HttpRegistrationRepository implements RegistrationRepository {
  private readonly http = inject(HttpClient);

  async register(details: RegisterAccountDetails): Promise<RegisteredAccount> {
    try {
      const response = await firstValueFrom(
        this.http.post<RegisteredAccountResponseDto>(apiUrl('register'), RegisterAccountRequestMapper.toDto(details)),
      );

      return RegisteredAccountMapper.toDomain(response);
    } catch (error: unknown) {
      if (error instanceof HttpErrorResponse && error.status === 409) {
        const code = this.conflictCode(error.error);
        if (code === 'USERNAME_ALREADY_TAKEN') {
          throw new AccountAlreadyExistsError('username');
        }
        if (code === 'EMAIL_ALREADY_REGISTERED') {
          throw new AccountAlreadyExistsError('email');
        }
      }

      if (error instanceof HttpErrorResponse && error.status === 422) {
        throw new RegistrationRejectedError();
      }

      throw error;
    }
  }

  private conflictCode(payload: unknown): string | null {
    if (typeof payload !== 'object' || payload === null || !('error' in payload)) {
      return null;
    }

    const error = payload.error;
    if (typeof error !== 'object' || error === null || !('code' in error)) {
      return null;
    }

    return typeof error.code === 'string' ? error.code : null;
  }
}
