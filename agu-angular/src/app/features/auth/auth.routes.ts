import { inject } from '@angular/core';
import { Routes } from '@angular/router';
import { LoginUseCase } from './application/use-cases/login.use-case';
import { AUTH_REPOSITORY, SESSION_REPOSITORY } from './infrastructure/di/auth.tokens';
import { HttpAuthRepository } from './infrastructure/http/http-auth.repository';
import { BrowserSessionRepository } from './infrastructure/storage/browser-session.repository';

export const AUTH_ROUTES: Routes = [
  {
    path: '',
    providers: [
      { provide: AUTH_REPOSITORY, useClass: HttpAuthRepository },
      { provide: SESSION_REPOSITORY, useClass: BrowserSessionRepository },
      {
        provide: LoginUseCase,
        useFactory: () => new LoginUseCase(inject(AUTH_REPOSITORY), inject(SESSION_REPOSITORY)),
      },
    ],
    loadComponent: () => import('./presentation/pages/login/login.component').then(m => m.LoginComponent),
  },
];
