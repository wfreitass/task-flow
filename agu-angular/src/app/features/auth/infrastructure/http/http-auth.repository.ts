import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { apiUrl } from '../../../../core/config/api.config';
import { InvalidCredentialsError } from '../../domain/errors/invalid-credentials.error';
import { AuthSession } from '../../domain/models/auth-session';
import { LoginCredentials } from '../../domain/models/login-credentials';
import { AuthRepository } from '../../domain/repositories/auth.repository';
import { LoginResponseDto } from '../dto/login-response.dto';
import { AuthSessionMapper } from '../mappers/auth-session.mapper';

@Injectable()
export class HttpAuthRepository implements AuthRepository {
  private readonly http = inject(HttpClient);

  async login(credentials: LoginCredentials): Promise<AuthSession> {
    try {
      const response = await firstValueFrom(
        this.http.post<LoginResponseDto>(apiUrl('login'), credentials),
      );

      return AuthSessionMapper.toDomain(response);
    } catch (error: unknown) {
      if (error instanceof HttpErrorResponse && error.status === 401) {
        throw new InvalidCredentialsError();
      }

      throw error;
    }
  }
}
