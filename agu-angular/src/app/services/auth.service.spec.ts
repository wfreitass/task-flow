import { TestBed } from '@angular/core/testing';

import { AuthService } from './auth.service';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { Router, provideRouter } from '@angular/router';
import { SessionTokenStorage } from '../core/auth/session-token.storage';

describe('AuthService', () => {
  let service: AuthService;
  let storage: jasmine.SpyObj<SessionTokenStorage>;

  beforeEach(() => {
    storage = jasmine.createSpyObj<SessionTokenStorage>('SessionTokenStorage', ['getToken', 'clear']);
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([]), { provide: SessionTokenStorage, useValue: storage }],
    });
    service = TestBed.inject(AuthService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('remove a mesma sessão usada pelo novo login e retorna à tela de acesso', () => {
    const navigate = spyOn(TestBed.inject(Router), 'navigate').and.resolveTo(true);

    service.logout();

    expect(storage.clear).toHaveBeenCalledTimes(1);
    expect(navigate).toHaveBeenCalledOnceWith(['/login']);
  });
});
