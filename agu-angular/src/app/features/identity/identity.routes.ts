import { inject } from '@angular/core';
import { Routes } from '@angular/router';
import { LoginUseCase } from './application/use-cases/login.use-case';
import { AUTH_REPOSITORY, SESSION_REPOSITORY } from './infrastructure/di/auth.tokens';
import { DEMO_CREDENTIALS } from './infrastructure/mock/demo-credentials';
import { MockAuthRepository } from './infrastructure/mock/mock-auth.repository';
import { BrowserSessionRepository } from './infrastructure/storage/browser-session.repository';

export const IDENTITY_ROUTES: Routes = [
  {
    path: '',
    data: { demoCredentials: DEMO_CREDENTIALS },
    providers: [
      { provide: AUTH_REPOSITORY, useClass: MockAuthRepository },
      { provide: SESSION_REPOSITORY, useClass: BrowserSessionRepository },
      {
        provide: LoginUseCase,
        useFactory: () => new LoginUseCase(inject(AUTH_REPOSITORY), inject(SESSION_REPOSITORY)),
      },
    ],
    loadComponent: () => import('./presentation/pages/login/login.component').then(m => m.LoginComponent),
  },
];
