import { ChangeDetectionStrategy, Component, effect, inject } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialog, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { UiConfirmDialogComponent, UiConfirmDialogData } from '../../../../../shared/ui/confirm-dialog/confirm-dialog.component';
import { UiIconComponent } from '../../../../../shared/ui/icon/icon.component';
import { Task, TaskDetails } from '../../../domain/entities/task.entity';
import { TaskListStore } from '../../state/task-list.store';
import { TaskFormComponent } from '../task-form/task-form.component';

@Component({
  selector: 'app-task-form-dialog',
  standalone: true,
  imports: [MatDialogModule, MatButtonModule, UiIconComponent, TaskFormComponent],
  template: `
    <header>
      <div><span>SEU TRABALHO, EM MOVIMENTO</span><h2 mat-dialog-title>{{ task ? 'Detalhes da tarefa' : 'Nova tarefa' }}</h2></div>
      <button mat-icon-button aria-label="Fechar formulário" [disabled]="store.saving()" (click)="reference.close()"><app-ui-icon name="close" /></button>
    </header>
    <mat-dialog-content>
      <p class="intro">{{ task ? 'Atualize os detalhes e acompanhe o próximo passo.' : 'Dê um nome à sua ideia. O próximo passo vem depois.' }}</p>
      <app-task-form [task]="task" [busy]="store.saving()" [error]="store.mutationError()" (submitted)="save($event)" (cancelled)="reference.close()" />
      @if (task) { <button mat-button color="warn" class="delete-button" [disabled]="store.saving()" (click)="confirmDelete()"><app-ui-icon name="trash" /> Excluir tarefa</button> }
    </mat-dialog-content>
  `,
  styles: [`
    header{display:flex;align-items:center;justify-content:space-between;padding:24px 24px 0}header span{color:#8d9c85;font-size:9px;letter-spacing:1.4px}
    h2{padding:7px 0 0!important;margin:0;color:#304b33;font-size:23px!important;letter-spacing:-.5px}.intro{margin:4px 0 26px;color:#84917c;font-size:12px;line-height:1.8}
    mat-dialog-content{max-height:78dvh!important;padding:16px 24px 24px!important}.delete-button{margin-top:20px;font-size:11px;letter-spacing:0}app-ui-icon{width:17px;height:17px}
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TaskFormDialogComponent {
  readonly task = inject<Task | null>(MAT_DIALOG_DATA);
  readonly store = inject(TaskListStore);
  readonly reference = inject(MatDialogRef<TaskFormDialogComponent>);
  private readonly dialog = inject(MatDialog);
  private readonly untilDestroyed = takeUntilDestroyed();

  constructor() {
    effect(() => { this.reference.disableClose = this.store.saving(); });
  }

  async save(details: TaskDetails): Promise<void> {
    if (await this.store.save(details, this.task?.id)) this.reference.close();
  }

  confirmDelete(): void {
    const task = this.task;
    if (!task) return;
    this.dialog.open<UiConfirmDialogComponent, UiConfirmDialogData, boolean>(UiConfirmDialogComponent, {
      width: '420px', maxWidth: 'calc(100vw - 32px)',
      data: { title: 'Excluir tarefa?', message: `A tarefa “${task.title}” será removida deste navegador.`, confirmLabel: 'Excluir tarefa' },
    }).afterClosed().pipe(this.untilDestroyed).subscribe(confirmed => {
      if (confirmed) void this.store.remove(task.id).then(removed => { if (removed) this.reference.close(); });
    });
  }
}
