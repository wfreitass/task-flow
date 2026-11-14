import { DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input, OnInit, ViewChild, inject } from '@angular/core';
import { FormControl, FormGroup, FormGroupDirective, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { UiFormFieldComponent } from '../../../../../shared/ui/form-field/form-field.component';
import { TaskActivity } from '../../../domain/repositories/task-activity.repository';
import { TaskActivityStore } from '../../state/task-activity.store';

@Component({
  selector: 'app-task-activity',
  standalone: true,
  imports: [DatePipe, ReactiveFormsModule, MatButtonModule, UiFormFieldComponent],
  templateUrl: './task-activity.component.html',
  styleUrl: './task-activity.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TaskActivityComponent implements OnInit {
  @Input({ required: true }) taskId!: string;
  @Input({ required: true }) view!: 'comments' | 'history';
  readonly store = inject(TaskActivityStore);
  readonly comment = new FormControl('', { nonNullable: true, validators: [Validators.required, Validators.pattern(/\S/), Validators.maxLength(5000)] });
  readonly form = new FormGroup({ comment: this.comment });
  @ViewChild(FormGroupDirective) private formDirective?: FormGroupDirective;
  submitted = false;

  ngOnInit(): void {
    if (this.view === 'comments') void this.store.loadComments(this.taskId);
    else void this.store.loadHistory(this.taskId);
  }
  async send(): Promise<void> {
    if (this.store.sending()) return;
    this.submitted = true;
    if (this.comment.invalid) { this.comment.markAsTouched(); return; }
    const value = this.comment.value;
    if (await this.store.addComment(this.taskId, value)) {
      this.formDirective?.resetForm({ comment: '' });
      this.submitted = false;
    }
  }
  actionLabel(kind: string): string {
    return ({ created: 'Tarefa criada', updated: 'Tarefa atualizada', status_changed: 'Status alterado', assigned: 'Responsável alterado', deleted: 'Tarefa excluída', attachment_added: 'Anexo adicionado', comment_added: 'Comentário adicionado' } as Record<string, string>)[kind] ?? 'Atividade registrada';
  }
  changes(item: TaskActivity): readonly { label: string; before: string; after: string }[] {
    if (!['updated', 'status_changed', 'assigned'].includes(item.kind)) return [];
    const labels: Record<string, string> = { title: 'Título', description: 'Descrição', priority: 'Prioridade', status: 'Status', assigned_to: 'Responsável', start_date: 'Início', due_date: 'Vencimento', completed_at: 'Conclusão' };
    const keys = new Set([...Object.keys(item.oldValue ?? {}), ...Object.keys(item.newValue ?? {})]);
    return [...keys].filter(key => labels[key]).map(key => ({
      label: labels[key],
      before: key === 'assigned_to' ? this.assigneeName(item.oldValue?.[key], item.oldAssigneeName) : this.formatValue(item.oldValue?.[key]),
      after: key === 'assigned_to' ? this.assigneeName(item.newValue?.[key], item.newAssigneeName) : this.formatValue(item.newValue?.[key]),
    }));
  }
  private assigneeName(value: unknown, name: string | null): string {
    return value === null || value === undefined ? 'Sem responsável' : name || 'Usuário indisponível';
  }
  private formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') return 'Não informado';
    const labels: Record<string, string> = { backlog: 'Backlog', todo: 'A fazer', in_progress: 'Em andamento', blocked: 'Bloqueada', done: 'Concluída', canceled: 'Cancelada', low: 'Baixa', medium: 'Média', high: 'Alta', urgent: 'Urgente' };
    return labels[String(value)] ?? String(value);
  }
}
