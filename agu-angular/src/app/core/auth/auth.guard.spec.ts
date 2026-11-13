import { TestBed } from '@angular/core/testing';
import { Router, UrlTree, provideRouter } from '@angular/router';
import { AuthGuard } from './auth.guard';
import { SessionTokenStorage } from './session-token.storage';

describe('AuthGuard', () => {
  let storage: jasmine.SpyObj<SessionTokenStorage>;

  beforeEach(() => {
    storage = jasmine.createSpyObj<SessionTokenStorage>('SessionTokenStorage', ['getToken']);
    TestBed.configureTestingModule({
      providers: [provideRouter([]), { provide: SessionTokenStorage, useValue: storage }],
    });
  });

  it('retorna redirecionamento para login sem sessão', () => {
    storage.getToken.and.returnValue(null);
    const result = TestBed.inject(AuthGuard).canActivate();

    expect(TestBed.inject(Router).serializeUrl(result as UrlTree)).toBe('/login');
  });

  it('permite navegar com a sessão salva', () => {
    storage.getToken.and.returnValue('task-flow-demo-session');

    expect(TestBed.inject(AuthGuard).canActivate()).toBeTrue();
  });
});
