import { InjectionToken } from '@angular/core';
import { AuthRepository } from '../../domain/repositories/auth.repository';
import { SessionRepository } from '../../domain/repositories/session.repository';

export const AUTH_REPOSITORY = new InjectionToken<AuthRepository>('AuthRepository');
export const SESSION_REPOSITORY = new InjectionToken<SessionRepository>('SessionRepository');
