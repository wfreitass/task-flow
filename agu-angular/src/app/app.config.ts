import { ApplicationConfig, inject } from '@angular/core';
import { provideRouter, withComponentInputBinding } from '@angular/router';
import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { sessionExpirationInterceptor } from './core/auth/session-expiration.interceptor';

import { routes } from './app-routes';
import { GetCurrentUserUseCase } from './features/auth/application/use-cases/get-current-user.use-case';
import { CURRENT_USER_REPOSITORY } from './features/auth/infrastructure/di/auth.tokens';
import { HttpCurrentUserRepository } from './features/auth/infrastructure/http/http-current-user.repository';
import { CurrentUserStore } from './features/auth/presentation/state/current-user.store';
import { provideUsers } from './features/users/infrastructure/user.providers';

export const appConfig: ApplicationConfig = {
  providers: [
    provideHttpClient(withInterceptors([sessionExpirationInterceptor])),
    provideRouter(routes, withComponentInputBinding()),
    provideAnimationsAsync(),
    ...provideUsers(),
    { provide: CURRENT_USER_REPOSITORY, useClass: HttpCurrentUserRepository },
    {
      provide: GetCurrentUserUseCase,
      useFactory: () => new GetCurrentUserUseCase(inject(CURRENT_USER_REPOSITORY)),
    },
    CurrentUserStore,
  ],
};
