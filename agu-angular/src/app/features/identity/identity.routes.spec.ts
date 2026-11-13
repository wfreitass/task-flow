import { Component } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { Router, provideRouter, withComponentInputBinding } from '@angular/router';
import { RouterTestingHarness } from '@angular/router/testing';
import { AuthGuard } from '../../core/auth/auth.guard';
import { SessionTokenStorage } from '../../core/auth/session-token.storage';
import { IDENTITY_ROUTES } from './identity.routes';
import { LoginComponent } from './presentation/pages/login/login.component';

@Component({ standalone: true, template: '<h1>Tarefas</h1>' })
class ProtectedPageComponent {}

describe('Rota de login mockada', () => {
  let storage: jasmine.SpyObj<SessionTokenStorage>;

  beforeEach(() => {
    let token: string | null = null;
    storage = jasmine.createSpyObj<SessionTokenStorage>('SessionTokenStorage', ['getToken', 'setToken']);
    storage.getToken.and.callFake(() => token);
    storage.setToken.and.callFake(value => { token = value; });
    TestBed.configureTestingModule({
      imports: [NoopAnimationsModule],
      providers: [
        provideRouter([
          { path: 'login', loadChildren: () => Promise.resolve(IDENTITY_ROUTES) },
          { path: 'tasks', component: ProtectedPageComponent, canActivate: [AuthGuard] },
        ], withComponentInputBinding()),
        { provide: SessionTokenStorage, useValue: storage },
      ],
    });
  });

  it('compõe a feature, autentica sem HttpClient e abre a rota protegida', async () => {
    const harness = await RouterTestingHarness.create();
    const login = await harness.navigateByUrl('/login', LoginComponent);
    expect(login.demoCredentials).toEqual({ username: 'demo', password: 'demo123' });

    login.fillDemoCredentials();
    await login.login();

    expect(storage.setToken).toHaveBeenCalledOnceWith('task-flow-demo-session');
    expect(TestBed.inject(Router).url).toBe('/tasks');
  });

  it('protege o acesso direto a tarefas antes do login', async () => {
    const harness = await RouterTestingHarness.create();

    await harness.navigateByUrl('/tasks', LoginComponent);

    expect(TestBed.inject(Router).url).toBe('/login');
    expect(storage.setToken).not.toHaveBeenCalled();
  });
});
