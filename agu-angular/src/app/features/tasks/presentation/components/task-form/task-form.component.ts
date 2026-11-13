import { ChangeDetectionStrategy, Component, EventEmitter, Input, OnChanges, Output, SimpleChanges, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { UiFormActionsComponent } from '../../../../../shared/ui/form-actions/form-actions.component';
import { UiFormFieldComponent } from '../../../../../shared/ui/form-field/form-field.component';
import { UiSelectComponent } from '../../../../../shared/ui/select/select.component';
import { Task, TaskDetails } from '../../../domain/entities/task.entity';
import { TaskStatus } from '../../../domain/value-objects/task-status.vo';
import { TASK_STATUS_OPTIONS } from '../../task-presentation';

@Component({
  selector: 'app-task-form',
  standalone: true,
  imports: [ReactiveFormsModule, UiFormFieldComponent, UiSelectComponent, UiFormActionsComponent],
  template: `
    <form [formGroup]="form" (ngSubmit)="submit()" [attr.aria-busy]="busy" novalidate>
      <app-ui-form-field label="Título da tarefa" name="title" [control]="form.controls.title" placeholder="O que precisa ser feito?" [maxLength]="255" requiredMessage="Informe um título para a tarefa." [submitted]="submitAttempted()" />
      <app-ui-form-field label="Descrição" name="description" [control]="form.controls.description" [multiline]="true" placeholder="Adicione contexto, ideias ou próximos passos (opcional)" [submitted]="submitAttempted()" />
      <app-ui-select label="Status" [control]="form.controls.status" [options]="statusOptions" [submitted]="submitAttempted()" />
      @if (error) { <p class="form-error" role="alert">{{ error }}</p> }
      <app-ui-form-actions [busy]="busy" [submitLabel]="task ? 'Salvar alterações' : 'Criar tarefa'" (cancelled)="cancelled.emit()" />
    </form>
  `,
  styles: ['form{display:grid;gap:23px;padding-top:4px}.form-error{padding:12px;margin:0;border-radius:8px;color:#9b3030;background:#fff3f1;font-size:12px;line-height:1.7}'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TaskFormComponent implements OnChanges {
  @Input() task: Task | null = null;
  @Input() busy = false;
  @Input() error: string | null = null;
  @Output() readonly submitted = new EventEmitter<TaskDetails>();
  @Output() readonly cancelled = new EventEmitter<void>();

  private readonly builder = inject(FormBuilder);
  private readonly _submitted = signal(false);
  readonly submitAttempted = this._submitted.asReadonly();
  readonly statusOptions = TASK_STATUS_OPTIONS;
  readonly form = this.builder.nonNullable.group({
    title: ['', [Validators.required, Validators.pattern(/\S/), Validators.maxLength(255)]],
    description: [''],
    status: this.builder.nonNullable.control<TaskStatus>('pending', Validators.required),
  });

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['task']) {
      this.form.reset(this.task ? { title: this.task.title, description: this.task.description, status: this.task.status } : { title: '', description: '', status: 'pending' });
      this._submitted.set(false);
    }
    if (this.busy) this.form.disable({ emitEvent: false });
    else this.form.enable({ emitEvent: false });
  }

  submit(): void {
    if (this.busy) return;
    this._submitted.set(true);
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.submitted.emit(this.form.getRawValue());
  }
}
