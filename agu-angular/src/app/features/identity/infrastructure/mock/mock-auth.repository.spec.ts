import { fakeAsync, flushMicrotasks, tick } from '@angular/core/testing';
import { InvalidCredentialsError } from '../../domain/errors/invalid-credentials.error';
import { AuthSession } from '../../domain/models/auth-session';
import { DEMO_CREDENTIALS } from './demo-credentials';
import { MockAuthRepository } from './mock-auth.repository';

describe('MockAuthRepository', () => {
  it('simula latência antes de autenticar a conta de demonstração', fakeAsync(() => {
    const repository = new MockAuthRepository();
    let session: AuthSession | undefined;

    void repository.login(DEMO_CREDENTIALS).then(result => { session = result; });
    tick(599);
    expect(session).toBeUndefined();

    tick(1);
    flushMicrotasks();
    expect(session?.token).toBe('task-flow-demo-session');
  }));

  for (const credentials of [
    { username: 'outro', password: DEMO_CREDENTIALS.password },
    { username: DEMO_CREDENTIALS.username, password: 'incorreta' },
  ]) {
    it(`recusa credenciais inválidas (${credentials.username})`, fakeAsync(() => {
      const repository = new MockAuthRepository();
      let error: unknown;

      void repository.login(credentials).catch(reason => { error = reason; });
      tick(600);
      flushMicrotasks();

      expect(error).toEqual(jasmine.any(InvalidCredentialsError));
    }));
  }
});
