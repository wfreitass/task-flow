import { InjectionToken } from '@angular/core';
import { AuthRepository } from '../../domain/repositories/auth.repository';
import { SessionRepository } from '../../domain/repositories/session.repository';
import { CurrentUserRepository } from '../../domain/repositories/current-user.repository';

export const AUTH_REPOSITORY = new InjectionToken<AuthRepository>('AuthRepository');
export const SESSION_REPOSITORY = new InjectionToken<SessionRepository>('SessionRepository');
export const CURRENT_USER_REPOSITORY = new InjectionToken<CurrentUserRepository>('CurrentUserRepository');
