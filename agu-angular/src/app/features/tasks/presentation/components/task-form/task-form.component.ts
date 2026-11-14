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
  templateUrl: './task-form.component.html',
  styleUrl: './task-form.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TaskFormComponent implements OnChanges, OnInit {
  @Input() task: Task | null = null;
  @Input() busy = false;
  @Input() canManage = true;
  @Input() canCancel = false;
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
  get statusOptions(): typeof TASK_STATUS_OPTIONS {
    return this.canCancel || this.task?.status === 'canceled' ? TASK_STATUS_OPTIONS : TASK_STATUS_OPTIONS.filter(option => option.value !== 'canceled');
  }
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
