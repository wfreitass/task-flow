import { TestBed } from '@angular/core/testing';
import { LoginUseCase } from '../../application/use-cases/login.use-case';
import { InvalidCredentialsError } from '../../domain/errors/invalid-credentials.error';
import { LoginStore } from './login.store';

describe('LoginStore', () => {
  const credentials = { username: 'demo', password: 'demo123' };
  let useCase: jasmine.SpyObj<LoginUseCase>;
  let store: LoginStore;

  beforeEach(() => {
    useCase = jasmine.createSpyObj<LoginUseCase>('LoginUseCase', ['execute']);
    TestBed.configureTestingModule({
      providers: [LoginStore, { provide: LoginUseCase, useValue: useCase }],
    });
    store = TestBed.inject(LoginStore);
  });

  it('mantém loading até concluir e bloqueia um segundo envio simultâneo', async () => {
    let finish!: () => void;
    useCase.execute.and.returnValue(new Promise<void>(resolve => { finish = resolve; }));

    const first = store.login(credentials);
    expect(store.loading()).toBeTrue();
    expect(await store.login(credentials)).toBeFalse();
    expect(useCase.execute).toHaveBeenCalledTimes(1);

    finish();
    expect(await first).toBeTrue();
    expect(store.loading()).toBeFalse();
    expect(store.error()).toBeNull();
  });

  it('informa credenciais inválidas e permite tentar novamente com sucesso', async () => {
    useCase.execute.and.rejectWith(new InvalidCredentialsError());
    expect(await store.login(credentials)).toBeFalse();
    expect(store.loading()).toBeFalse();
    expect(store.error()).toContain('Usuário ou senha incorretos');

    useCase.execute.and.resolveTo();
    expect(await store.login(credentials)).toBeTrue();
    expect(store.error()).toBeNull();
  });

  it('trata erros inesperados sem expor detalhes técnicos', async () => {
    useCase.execute.and.rejectWith(new Error('sensitive implementation detail'));

    expect(await store.login(credentials)).toBeFalse();
    expect(store.error()).toContain('Não foi possível entrar');
    expect(store.error()).not.toContain('sensitive implementation detail');
    expect(store.loading()).toBeFalse();
  });

  it('limpa a mensagem ao editar os dados para uma nova tentativa', async () => {
    useCase.execute.and.rejectWith(new InvalidCredentialsError());
    await store.login(credentials);

    store.clearError();

    expect(store.error()).toBeNull();
  });
});
