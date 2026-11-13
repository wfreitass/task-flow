import { InvalidCredentialsError } from '../../domain/errors/invalid-credentials.error';
import { AuthRepository } from '../../domain/repositories/auth.repository';
import { SessionRepository } from '../../domain/repositories/session.repository';
import { LoginUseCase } from './login.use-case';

describe('LoginUseCase', () => {
  const credentials = { username: 'demo', password: 'demo123' };
  let auth: jasmine.SpyObj<AuthRepository>;
  let sessions: jasmine.SpyObj<SessionRepository>;
  let useCase: LoginUseCase;

  beforeEach(() => {
    auth = jasmine.createSpyObj<AuthRepository>('AuthRepository', ['login']);
    sessions = jasmine.createSpyObj<SessionRepository>('SessionRepository', ['save']);
    useCase = new LoginUseCase(auth, sessions);
  });

  it('autentica antes de persistir a sessão, sem salvar a senha', async () => {
    const session = { token: 'demo-session' };
    auth.login.and.resolveTo(session);

    await useCase.execute(credentials);

    expect(auth.login).toHaveBeenCalledOnceWith(credentials);
    expect(sessions.save).toHaveBeenCalledOnceWith(session);
  });

  it('não salva sessão quando as credenciais são recusadas', async () => {
    const error = new InvalidCredentialsError();
    auth.login.and.rejectWith(error);

    await expectAsync(useCase.execute(credentials)).toBeRejectedWith(error);

    expect(sessions.save).not.toHaveBeenCalled();
  });

  it('propaga falha de persistência para impedir falso sucesso no login', async () => {
    auth.login.and.resolveTo({ token: 'demo-session' });
    sessions.save.and.throwError('Storage unavailable');

    await expectAsync(useCase.execute(credentials)).toBeRejectedWithError('Storage unavailable');
  });
});
