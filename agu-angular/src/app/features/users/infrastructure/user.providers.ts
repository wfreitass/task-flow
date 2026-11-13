import { Provider, inject } from '@angular/core';
import { ListUsersUseCase } from '../application/list-users.use-case';
import { HttpUserRepository, USER_REPOSITORY } from './http-user.repository';

export function provideUsers(): Provider[] {
  return [
    { provide: USER_REPOSITORY, useClass: HttpUserRepository },
    { provide: ListUsersUseCase, useFactory: () => new ListUsersUseCase(inject(USER_REPOSITORY)) },
  ];
}
