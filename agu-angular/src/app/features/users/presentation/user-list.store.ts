import { Injectable, inject, signal } from '@angular/core';
import { ListUsersUseCase } from '../application/list-users.use-case';
import { User } from '../domain/user';

@Injectable()
export class UserListStore {
  private readonly listUsers = inject(ListUsersUseCase);
  readonly users = signal<readonly User[]>([]);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  async load(): Promise<void> {
    this.loading.set(true);
    this.error.set(null);
    try { this.users.set(await this.listUsers.execute()); }
    catch { this.error.set('Não foi possível carregar os usuários. Tente novamente.'); }
    finally { this.loading.set(false); }
  }
}
