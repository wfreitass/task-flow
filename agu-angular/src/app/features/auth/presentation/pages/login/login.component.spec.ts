import { ComponentFixture, TestBed } from '@angular/core/testing';
import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { Router, provideRouter } from '@angular/router';
import { LoginUseCase } from '../../../application/use-cases/login.use-case';
import { InvalidCredentialsError } from '../../../domain/errors/invalid-credentials.error';
import { LoginComponent } from './login.component';

describe('LoginComponent', () => {
  const credentials = { username: 'demo', password: 'demo123' };
  let fixture: ComponentFixture<LoginComponent>;
  let component: LoginComponent;
  let useCase: jasmine.SpyObj<LoginUseCase>;
  let navigate: jasmine.Spy;

  function element<T extends Element>(selector: string): T {
    return (fixture.nativeElement as HTMLElement).querySelector<T>(selector)!;
  }

  beforeEach(async () => {
    useCase = jasmine.createSpyObj<LoginUseCase>('LoginUseCase', ['execute']);
    await TestBed.configureTestingModule({
      imports: [LoginComponent, NoopAnimationsModule],
      providers: [provideRouter([]), { provide: LoginUseCase, useValue: useCase }],
    }).compileComponents();
    navigate = spyOn(TestBed.inject(Router), 'navigateByUrl').and.resolveTo(true);
    fixture = TestBed.createComponent(LoginComponent);
    fixture.componentRef.setInput('demoCredentials', credentials);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('mostra validação dos campos obrigatórios sem autenticar', async () => {
    element<HTMLButtonElement>('button[type="submit"]').click();
    fixture.detectChanges();
    await fixture.whenStable();

    expect(fixture.nativeElement.textContent).toContain('Informe seu usuário.');
    expect(fixture.nativeElement.textContent).toContain('Informe sua senha.');
    expect(useCase.execute).not.toHaveBeenCalled();
    expect(navigate).not.toHaveBeenCalled();
  });

  it('preenche os dados de demonstração sem enviar automaticamente', () => {
    element<HTMLButtonElement>('.demo-access button').click();
    fixture.detectChanges();

    expect(element<HTMLInputElement>('#username').value).toBe(credentials.username);
    expect(element<HTMLInputElement>('#password').value).toBe(credentials.password);
    expect(element<HTMLInputElement>('#password').type).toBe('password');
    expect(useCase.execute).not.toHaveBeenCalled();
  });

  it('alterna a visibilidade da senha sem submeter o formulário', () => {
    element<HTMLButtonElement>('button[aria-label="Mostrar senha"]').click();
    fixture.detectChanges();

    expect(element<HTMLInputElement>('#password').type).toBe('text');
    expect(element<HTMLButtonElement>('button[aria-label="Ocultar senha"]').getAttribute('aria-pressed')).toBe('true');
    expect(useCase.execute).not.toHaveBeenCalled();
  });

  it('navega para tarefas apenas depois de autenticar', async () => {
    useCase.execute.and.resolveTo();
    component.fillDemoCredentials();

    await component.login();

    expect(useCase.execute).toHaveBeenCalledOnceWith(credentials);
    expect(navigate).toHaveBeenCalledOnceWith('/tasks');
  });

  it('anuncia erro de autenticação e mantém o usuário na tela', async () => {
    useCase.execute.and.rejectWith(new InvalidCredentialsError());
    component.fillDemoCredentials();

    await component.login();
    fixture.detectChanges();

    expect(element('[role="alert"]').textContent).toContain('Usuário ou senha incorretos');
    expect(navigate).not.toHaveBeenCalled();
  });

  it('desabilita novo envio e preenchimento enquanto aguarda a resposta', async () => {
    let finish!: () => void;
    useCase.execute.and.returnValue(new Promise<void>(resolve => { finish = resolve; }));
    component.fillDemoCredentials();

    const submission = component.login();
    fixture.detectChanges();
    expect(element<HTMLButtonElement>('button[type="submit"]').disabled).toBeTrue();
    expect(element<HTMLButtonElement>('.demo-access button').disabled).toBeTrue();
    expect(element<HTMLInputElement>('#username').readOnly).toBeTrue();
    expect(fixture.nativeElement.textContent).toContain('Entrando...');

    finish();
    await submission;
  });

  it('não redireciona se a página foi destruída enquanto autenticava', async () => {
    let finish!: () => void;
    useCase.execute.and.returnValue(new Promise<void>(resolve => { finish = resolve; }));
    component.fillDemoCredentials();

    const submission = component.login();
    fixture.destroy();
    finish();
    await submission;

    expect(navigate).not.toHaveBeenCalled();
  });
});
