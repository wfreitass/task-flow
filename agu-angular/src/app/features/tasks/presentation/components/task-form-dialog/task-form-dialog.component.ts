import { ChangeDetectionStrategy, Component, computed, effect, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialog, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { UiConfirmDialogComponent, UiConfirmDialogData } from '../../../../../shared/ui/confirm-dialog/confirm-dialog.component';
import { UiIconComponent } from '../../../../../shared/ui/icon/icon.component';
import { Task, TaskDetails } from '../../../domain/entities/task.entity';
import { TaskListStore } from '../../state/task-list.store';
import { TaskFormComponent } from '../task-form/task-form.component';
import { CurrentUserStore } from '../../../../auth/presentation/state/current-user.store';

@Component({
  selector: 'app-task-form-dialog',
  standalone: true,
  imports: [MatDialogModule, MatButtonModule, UiIconComponent, TaskFormComponent],
  template: `
    <header>
      <div><span>SEU TRABALHO, EM MOVIMENTO</span><h2 mat-dialog-title>{{ task ? 'Detalhes da tarefa' : 'Nova tarefa' }}</h2></div>
      <button mat-icon-button class="close-button" aria-label="Fechar formulário" [disabled]="store.saving() || reading()" (click)="reference.close()"><app-ui-icon name="close" /></button>
    </header>
    <mat-dialog-content>
      <p class="intro">{{ task ? 'Atualize os detalhes e acompanhe o próximo passo.' : 'Dê um nome à sua ideia. O próximo passo vem depois.' }}</p>
      <app-task-form [task]="task" [canManage]="canManage()" [busy]="store.saving() || reading()" [error]="readError() || store.mutationError()" (filesChanged)="files = $event" (submitted)="save($event)" (cancelled)="reference.close()" />
      @if (task && canManage()) { <button mat-button color="warn" class="delete-button" [disabled]="store.saving()" (click)="confirmDelete()"><app-ui-icon name="trash" /> Excluir tarefa</button> }
    </mat-dialog-content>
  `,
  styles: [`
    header{display:flex;align-items:center;justify-content:space-between;padding:24px 24px 0}header span{color:#8d9c85;font-size:9px;letter-spacing:1.4px}
    h2{padding:7px 0 0!important;margin:0;color:#304b33;font-size:23px!important;letter-spacing:-.5px}.intro{margin:4px 0 26px;color:#84917c;font-size:12px;line-height:1.8}
    mat-dialog-content{max-height:78dvh!important;padding:16px 24px 24px!important;scrollbar-gutter:stable both-edges}.delete-button{margin-top:20px;font-size:11px;letter-spacing:0}app-ui-icon{width:17px;height:17px}
    .close-button{display:inline-flex;align-items:center;justify-content:center}.close-button app-ui-icon{display:block}
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TaskFormDialogComponent {
  readonly task = inject<Task | null>(MAT_DIALOG_DATA);
  readonly store = inject(TaskListStore);
  readonly reference = inject(MatDialogRef<TaskFormDialogComponent>);
  private readonly currentUser = inject(CurrentUserStore);
  readonly canManage = computed(() => !this.task || this.currentUser.user()?.id === this.task.createdBy);
  private readonly dialog = inject(MatDialog);
  private readonly untilDestroyed = takeUntilDestroyed();
  files: readonly File[] = [];
  readonly reading = signal(false);
  readonly readError = signal<string | null>(null);

  constructor() {
    effect(() => { this.reference.disableClose = this.store.saving() || this.reading(); });
  }

  async save(details: TaskDetails): Promise<void> {
    if (this.reading() || this.store.saving()) return;
    this.reading.set(true);
    this.readError.set(null);
    try {
      if (this.task && !this.canManage()) {
        if (details.status === this.task.status || await this.store.move(this.task.id, details.status, null)) this.reference.close();
        return;
      }
      const attachments = await Promise.all(this.files.map(async file => ({ name: file.name, type: file.type, bytes: new Uint8Array(await file.arrayBuffer()) })));
      if (await this.store.save(details, this.task?.id, attachments)) this.reference.close();
    } catch {
      this.readError.set('Não foi possível ler os arquivos selecionados. Tente novamente.');
    } finally { this.reading.set(false); }
  }

  confirmDelete(): void {
    const task = this.task;
    if (!task) return;
    this.dialog.open<UiConfirmDialogComponent, UiConfirmDialogData, boolean>(UiConfirmDialogComponent, {
      width: '420px', maxWidth: 'calc(100vw - 32px)',
      data: { title: 'Excluir tarefa?', message: `A tarefa “${task.title}” será excluída.`, confirmLabel: 'Excluir tarefa' },
    }).afterClosed().pipe(this.untilDestroyed).subscribe(confirmed => {
      if (confirmed) void this.store.remove(task.id).then(removed => { if (removed) this.reference.close(); });
    });
  }
}
