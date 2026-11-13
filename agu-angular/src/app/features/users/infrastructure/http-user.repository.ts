import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable, InjectionToken, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { SessionTokenStorage } from '../../../core/auth/session-token.storage';
import { apiUrl } from '../../../core/config/api.config';
import { User, UserRepository } from '../domain/user';

interface UserResponseDto { readonly id: number; readonly username: string; }
interface UserListResponseDto { readonly data: readonly UserResponseDto[]; }

export const USER_REPOSITORY = new InjectionToken<UserRepository>('UserRepository');

@Injectable()
export class HttpUserRepository implements UserRepository {
  private readonly http = inject(HttpClient);
  private readonly session = inject(SessionTokenStorage);

  async findAll(): Promise<readonly User[]> {
    const token = this.session.getToken();
    if (!token) throw new Error('Sua sessão expirou. Entre novamente.');
    const response = await firstValueFrom(this.http.get<UserListResponseDto>(apiUrl('users/list'), {
      headers: new HttpHeaders({ Authorization: `Bearer ${token}` }),
    }));
    return response.data.map(({ id, username }) => ({ id, username }));
  }
}
