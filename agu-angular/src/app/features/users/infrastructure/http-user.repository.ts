import { HttpClient, HttpErrorResponse, HttpHeaders } from '@angular/common/http';
import { Injectable, InjectionToken, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { SessionTokenStorage } from '../../../core/auth/session-token.storage';
import { apiUrl } from '../../../core/config/api.config';
import { User, UserProfile, UserRepository } from '../domain/user';

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

  async getProfile(id: number): Promise<UserProfile> {
    this.validateId(id);
    const response = await firstValueFrom(this.http.get<UserProfile>(apiUrl(`users/${id}/profile`), { headers: this.headers() }));
    return this.toProfile(response);
  }

  async listProfiles(): Promise<readonly UserProfile[]> {
    const response = await firstValueFrom(this.http.get<{ data: UserProfile[] }>(apiUrl('profiles'), { headers: this.headers() }));
    return response.data.map(profile => this.toProfile(profile));
  }

  async changeProfile(id: number, profileId: number): Promise<void> {
    this.validateId(id);
    this.validateId(profileId);
    try {
      await firstValueFrom(this.http.patch(apiUrl(`users/${id}/profile`), { profileId }, { headers: this.headers() }));
    } catch (error: unknown) {
      if (error instanceof HttpErrorResponse) {
        const code = error.error?.error?.code;
        if (code === 'LAST_ADMIN') throw new Error('Não é possível alterar o perfil do último usuário que gerencia usuários.');
        if (error.status === 403) throw new Error('Você não tem permissão para alterar perfis. Atualize a página para carregar seu perfil atual.');
        if (error.status === 404) throw new Error('Usuário ou perfil não encontrado. Atualize a lista e tente novamente.');
      }
      throw new Error('Não foi possível alterar o perfil. Tente novamente.');
    }
  }

  private validateId(id: number): void {
    if (!Number.isSafeInteger(id) || id < 1) throw new Error('Usuário inválido.');
  }

  private headers(): HttpHeaders {
    const token = this.session.getToken();
    if (!token) throw new Error('Sua sessão expirou. Entre novamente.');
    return new HttpHeaders({ Authorization: `Bearer ${token}` });
  }

  private toProfile(profile: UserProfile): UserProfile {
    if (!profile || !Number.isSafeInteger(profile.id) || profile.id < 1 || typeof profile.code !== 'string' || typeof profile.name !== 'string') throw new Error('A API retornou um perfil inválido.');
    return { id: profile.id, code: profile.code, name: profile.name };
  }
}
