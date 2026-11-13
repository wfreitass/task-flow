import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { SessionTokenStorage } from '../../../../core/auth/session-token.storage';
import { apiUrl } from '../../../../core/config/api.config';
import { AuthenticatedUser } from '../../domain/models/authenticated-user';
import { CurrentUserRepository } from '../../domain/repositories/current-user.repository';
import { CurrentUserResponseDto } from '../dto/current-user-response.dto';
import { AuthenticatedUserMapper } from '../mappers/authenticated-user.mapper';

@Injectable()
export class HttpCurrentUserRepository implements CurrentUserRepository {
  private readonly http = inject(HttpClient);
  private readonly sessionStorage = inject(SessionTokenStorage);

  async getCurrent(): Promise<AuthenticatedUser> {
    const token = this.sessionStorage.getToken();
    if (!token) {
      throw new Error('Não existe uma sessão autenticada.');
    }

    const response = await firstValueFrom(
      this.http.get<CurrentUserResponseDto>(apiUrl('me'), {
        headers: new HttpHeaders({ Authorization: `Bearer ${token}` }),
      }),
    );

    return AuthenticatedUserMapper.toDomain(response);
  }
}
