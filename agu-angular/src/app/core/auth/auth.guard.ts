import { Injectable, inject } from '@angular/core';
import { CanActivate, Router, UrlTree } from '@angular/router';
import { SessionTokenStorage } from './session-token.storage';

@Injectable({ providedIn: 'root' })
export class AuthGuard implements CanActivate {
  private readonly storage = inject(SessionTokenStorage);
  private readonly router = inject(Router);

  canActivate(): boolean | UrlTree {
    return this.storage.getToken() ? true : this.router.createUrlTree(['/login']);
  }
}
