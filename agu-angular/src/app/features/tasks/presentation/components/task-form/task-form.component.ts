import { ChangeDetectionStrategy, Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { UiDateFieldComponent } from '../../../../../shared/ui/date-field/date-field.component';
import { UiFileDropzoneComponent } from '../../../../../shared/ui/file-dropzone/file-dropzone.component';
import { UiFormActionsComponent } from '../../../../../shared/ui/form-actions/form-actions.component';
import { UiFormFieldComponent } from '../../../../../shared/ui/form-field/form-field.component';
import { UiSelectComponent } from '../../../../../shared/ui/select/select.component';
import { Task, TaskDetails } from '../../../domain/entities/task.entity';
import { TaskStatus } from '../../../domain/value-objects/task-status.vo';
import { TASK_STATUS_OPTIONS } from '../../task-presentation';
import { ListUsersUseCase } from '../../../../users/application/list-users.use-case';
import { User } from '../../../../users/domain/user';

@Component({
  selector: 'app-task-form',
  standalone: true,
  imports: [ReactiveFormsModule, UiFormFieldComponent, UiDateFieldComponent, UiFileDropzoneComponent, UiSelectComponent, UiFormActionsComponent],
  template: `
    <form [formGroup]="form" (ngSubmit)="submit()" [attr.aria-busy]="busy" novalidate>
      <app-ui-form-field label="Título da tarefa" name="title" [control]="form.controls.title" placeholder="O que precisa ser feito?" [maxLength]="255" requiredMessage="Informe um título para a tarefa." [submitted]="submitAttempted()" />
      <app-ui-form-field label="Descrição" name="description" [control]="form.controls.description" [multiline]="true" placeholder="Adicione contexto, ideias ou próximos passos (opcional)" [submitted]="submitAttempted()" />
      <div class="date-fields">
        <app-ui-date-field label="Data de início" [control]="form.controls.startAt" hint="Opcional" />
        <app-ui-date-field label="Data de vencimento" [control]="form.controls.dueAt" hint="Opcional" />
      </div>
      @if (dateRangeError()) { <p class="field-error" role="alert">A data de vencimento deve ser igual ou posterior à data de início.</p> }
      <app-ui-select label="Prioridade" [control]="form.controls.priority" [options]="priorityOptions" [submitted]="submitAttempted()" />
      <app-ui-select label="Responsável" [control]="form.controls.assigneeId" [options]="assigneeOptions" />
      @if (usersError()) { <p class="field-error" role="alert">Não foi possível carregar os usuários. <button type="button" (click)="loadUsers()">Tentar novamente</button></p> }
      @if (!task) { <app-ui-file-dropzone [disabled]="busy" (filesChange)="filesChanged.emit($event)" /> }
      @if (task) { <app-ui-select label="Status" [control]="form.controls.status" [options]="statusOptions" [submitted]="submitAttempted()" /> }
      @if (task && !canManage) { <p class="field-error">Você pode alterar o status desta tarefa. Os demais campos são editados pelo criador.</p> }
      @if (error) { <p class="form-error" role="alert">{{ error }}</p> }
      <app-ui-form-actions [busy]="busy" [submitLabel]="task ? 'Salvar alterações' : 'Criar tarefa'" (cancelled)="cancelled.emit()" />
    </form>
  `,
  styles: ['form{display:grid;gap:18px;padding-top:4px;min-width:0}.date-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.date-fields>*{min-width:0}.form-error,.field-error{padding:12px;margin:0;border-radius:8px;color:#9b3030;background:#fff3f1;font-size:12px;line-height:1.7}@media(max-width:480px){.date-fields{grid-template-columns:minmax(0,1fr);gap:12px}}'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TaskFormComponent implements OnChanges, OnInit {
  @Input() task: Task | null = null;
  @Input() busy = false;
  @Input() canManage = true;
  @Input() error: string | null = null;
  @Output() readonly submitted = new EventEmitter<TaskDetails>();
  @Output() readonly cancelled = new EventEmitter<void>();
  @Output() readonly filesChanged = new EventEmitter<readonly File[]>();

  private readonly builder = inject(FormBuilder);
  private readonly listUsers = inject(ListUsersUseCase);
  readonly users = signal<readonly User[]>([]);
  readonly usersError = signal(false);
  private readonly _submitted = signal(false);
  readonly submitAttempted = this._submitted.asReadonly();
  readonly statusOptions = TASK_STATUS_OPTIONS;
  readonly priorityOptions = [
    { value: 'low', label: 'Baixa' }, { value: 'medium', label: 'Média' },
    { value: 'high', label: 'Alta' }, { value: 'urgent', label: 'Urgente' },
  ] as const;
  readonly dateRangeError = signal(false);
  get assigneeOptions(): readonly { value: string; label: string }[] {
    const users = this.users();
    const current = this.task?.assignee;
    const items = current && !users.some(user => user.id === current.id) ? [current, ...users] : users;
    return [{ value: '', label: 'Sem responsável' }, ...items.map(user => ({ value: String(user.id), label: user.username }))];
  }
  readonly form = this.builder.nonNullable.group({
    title: ['', [Validators.required, Validators.pattern(/\S/), Validators.maxLength(255)]],
    description: [''],
    status: this.builder.nonNullable.control<TaskStatus>('backlog', Validators.required),
    priority: this.builder.nonNullable.control<'low' | 'medium' | 'high' | 'urgent'>('medium', Validators.required),
    startAt: this.builder.control<Date | null>(null),
    dueAt: this.builder.control<Date | null>(null),
    assigneeId: [''],
  });

  ngOnInit(): void { void this.loadUsers(); }

  async loadUsers(): Promise<void> {
    this.usersError.set(false);
    try { this.users.set(await this.listUsers.execute()); }
    catch { this.usersError.set(true); }
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['task']) {
      this.form.reset(this.task ? { title: this.task.title, description: this.task.description, status: this.task.status, priority: this.task.priority, startAt: this.parseDate(this.task.startAt), dueAt: this.parseDate(this.task.dueAt), assigneeId: this.task.assignee ? String(this.task.assignee.id) : '' } : { title: '', description: '', status: 'backlog', priority: 'medium', startAt: null, dueAt: null, assigneeId: '' });
      this._submitted.set(false);
      this.dateRangeError.set(false);
    }
    if (this.busy || !this.canManage) this.form.disable({ emitEvent: false });
    else this.form.enable({ emitEvent: false });
    if (!this.busy && !this.canManage && this.task) this.form.controls.status.enable({ emitEvent: false });
  }

  submit(): void {
    if (this.busy) return;
    this._submitted.set(true);
    const { startAt, dueAt } = this.form.getRawValue();
    this.dateRangeError.set(Boolean(startAt && dueAt && dueAt < startAt));
    if (this.dateRangeError()) return;
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    const values = this.form.getRawValue();
    const assignee = values.assigneeId ? (this.users().find(user => String(user.id) === values.assigneeId) ?? (String(this.task?.assignee?.id) === values.assigneeId ? this.task?.assignee : null)) : null;
    this.submitted.emit({ ...values, assignee: assignee ?? null, startAt: this.formatDate(values.startAt), dueAt: this.formatDate(values.dueAt) });
  }

  private parseDate(value: string): Date | null {
    if (!value) return null;
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
  }

  private formatDate(value: Date | null): string {
    if (!value) return '';
    return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
  }
}
