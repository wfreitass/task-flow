import { Injectable, computed, inject, signal } from '@angular/core';
import { GetCurrentUserUseCase } from '../../application/use-cases/get-current-user.use-case';
import { AuthenticatedUser } from '../../domain/models/authenticated-user';

@Injectable()
export class CurrentUserStore {
  private readonly getCurrentUser = inject(GetCurrentUserUseCase);
  private readonly _user = signal<AuthenticatedUser | null>(null);
  private readonly _loading = signal(false);

  readonly user = this._user.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly avatarInitials = computed(() => this.user()?.username.trim().slice(0, 2).toUpperCase() || 'TF');

  async load(): Promise<void> {
    if (this._loading()) {
      return;
    }

    this._loading.set(true);
    try {
      this._user.set(await this.getCurrentUser.execute());
    } catch {
      this._user.set(null);
    } finally {
      this._loading.set(false);
    }
  }

  clear(): void {
    this._user.set(null);
  }
}
