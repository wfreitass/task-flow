import { InjectionToken } from '@angular/core';
import { RegistrationRepository } from '../../domain/repositories/registration.repository';

export const REGISTRATION_REPOSITORY = new InjectionToken<RegistrationRepository>('RegistrationRepository');
