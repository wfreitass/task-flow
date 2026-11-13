import { inject } from '@angular/core';
import { Routes } from '@angular/router';
import { RegisterAccountUseCase } from './application/use-cases/register-account.use-case';
import { REGISTRATION_REPOSITORY } from './infrastructure/di/registration.tokens';
import { HttpRegistrationRepository } from './infrastructure/http/http-registration.repository';

export const REGISTER_ROUTES: Routes = [
  {
    path: '',
    providers: [
      { provide: REGISTRATION_REPOSITORY, useClass: HttpRegistrationRepository },
      {
        provide: RegisterAccountUseCase,
        useFactory: () => new RegisterAccountUseCase(inject(REGISTRATION_REPOSITORY)),
      },
    ],
    loadComponent: () => import('./presentation/pages/register/register-account.component').then(m => m.RegisterAccountComponent),
  },
];
