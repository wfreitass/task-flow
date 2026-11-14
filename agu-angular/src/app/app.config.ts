import { ApplicationConfig, inject } from '@angular/core';
import { provideRouter, withComponentInputBinding } from '@angular/router';
import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { sessionExpirationInterceptor } from './core/auth/session-expiration.interceptor';

import { routes } from './app-routes';
import { GetCurrentUserUseCase } from './features/auth/application/use-cases/get-current-user.use-case';
import { LogoutUseCase } from './features/auth/application/use-cases/logout.use-case';
import { CURRENT_USER_REPOSITORY, SESSION_REPOSITORY } from './features/auth/infrastructure/di/auth.tokens';
import { HttpCurrentUserRepository } from './features/auth/infrastructure/http/http-current-user.repository';
import { BrowserSessionRepository } from './features/auth/infrastructure/storage/browser-session.repository';
import { CurrentUserStore } from './features/auth/presentation/state/current-user.store';
import { provideUsers } from './features/users/infrastructure/user.providers';
import { provideTaskActivity } from './features/tasks/infrastructure/di/task-activity.providers';

export const appConfig: ApplicationConfig = {
  providers: [
    provideHttpClient(withInterceptors([sessionExpirationInterceptor])),
    provideRouter(routes, withComponentInputBinding()),
    provideAnimationsAsync(),
    ...provideUsers(),
    ...provideTaskActivity(),
    { provide: CURRENT_USER_REPOSITORY, useClass: HttpCurrentUserRepository },
    { provide: SESSION_REPOSITORY, useClass: BrowserSessionRepository },
    { provide: LogoutUseCase, useFactory: () => new LogoutUseCase(inject(SESSION_REPOSITORY)) },
    {
      provide: GetCurrentUserUseCase,
      useFactory: () => new GetCurrentUserUseCase(inject(CURRENT_USER_REPOSITORY)),
    },
    CurrentUserStore,
  ],
};
