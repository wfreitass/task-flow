import { ChangeDetectionStrategy, Component, DestroyRef, Input, OnInit, ViewContainerRef, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormControl } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Router } from '@angular/router';
import { UiFormFieldComponent } from '../../../../../shared/ui/form-field/form-field.component';
import { UiIconComponent } from '../../../../../shared/ui/icon/icon.component';
import { UiKanbanComponent } from '../../../../../shared/ui/kanban/kanban.component';
import { UiKanbanColumn, UiKanbanMove } from '../../../../../shared/ui/kanban/kanban.models';
import { UiSelectComponent } from '../../../../../shared/ui/select/select.component';
import { UiTableColumn, UiTableComponent } from '../../../../../shared/ui/table/table.component';
import { Task } from '../../../domain/entities/task.entity';
import { TaskStatus } from '../../../domain/value-objects/task-status.vo';
import { TaskFormDialogComponent } from '../../components/task-form-dialog/task-form-dialog.component';
import { TaskSummaryComponent } from '../../components/task-summary/task-summary.component';
import { TasksEmptyStateComponent } from '../../components/tasks-empty-state/tasks-empty-state.component';
import { TaskListStore } from '../../state/task-list.store';
import { TASK_BOARD_OPTIONS, TASK_STATUS_LABELS, TaskBoardStatus, taskMatchesBoardStatus, toApiStatus } from '../../task-presentation';

@Component({
  selector: 'app-task-list',
  standalone: true,
  imports: [MatButtonModule, MatProgressSpinnerModule, UiFormFieldComponent, UiSelectComponent, UiIconComponent, UiKanbanComponent, UiTableComponent, TaskSummaryComponent, TasksEmptyStateComponent],
  providers: [TaskListStore],
  templateUrl: './task-list.component.html',
  styleUrl: './task-list.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TaskListComponent implements OnInit {
  @Input() openCreate = false;
  readonly store = inject(TaskListStore);
  private readonly dialog = inject(MatDialog);
  private readonly container = inject(ViewContainerRef);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);
  private destroyed = false;
  readonly view = signal<'kanban' | 'table'>('kanban');
  readonly formOpen = signal(false);
  readonly search = new FormControl('', { nonNullable: true });
  readonly status = new FormControl<TaskBoardStatus | 'all'>('all', { nonNullable: true });
  readonly filterOptions = [{ value: 'all' as const, label: 'Todos os status' }, ...TASK_BOARD_OPTIONS];
  readonly tableColumns: readonly UiTableColumn<Task>[] = [
    { key: 'title', label: 'Tarefa', value: task => task.title },
    { key: 'description', label: 'Descrição', value: task => task.description || 'Sem descrição' },
    { key: 'assignee', label: 'Responsável', value: task => task.assignee?.username ?? 'Sem responsável' },
    { key: 'status', label: 'Status', value: task => TASK_STATUS_LABELS[task.status] },
  ];
  readonly columns = computed<readonly UiKanbanColumn[]>(() => TASK_BOARD_OPTIONS.map(option => ({
    id: option.value, title: option.label, tone: option.tone,
    items: this.store.filteredTasks().filter(task => taskMatchesBoardStatus(task, option.value)),
  })));

  constructor() {
    this.destroyRef.onDestroy(() => { this.destroyed = true; });
    this.search.valueChanges.pipe(takeUntilDestroyed()).subscribe(query => this.store.setQuery(query));
    this.status.valueChanges.pipe(takeUntilDestroyed()).subscribe(status => this.store.setStatus(status));
  }

  async ngOnInit(): Promise<void> {
    await this.store.load();
    if (this.openCreate && !this.destroyed && !this.store.error()) this.openTask();
  }

  clearFilters(): void { this.search.setValue(''); this.status.setValue('all'); }

  selectTask(id: string): void {
    const task = this.store.tasks().find(item => item.id === id);
    if (task) this.openTask(task);
  }

  openTask(task: Task | null = null): void {
    if (this.formOpen() || this.store.saving() || this.store.loading() || this.store.error()) return;
    this.store.clearMutationError();
    this.formOpen.set(true);
    this.dialog.open<TaskFormDialogComponent, Task | null>(TaskFormDialogComponent, {
      data: task, width: '540px', maxWidth: 'calc(100vw - 32px)',
      viewContainerRef: this.container, autoFocus: 'first-tabbable',
    }).afterClosed().pipe(takeUntilDestroyed(this.destroyRef)).subscribe(() => {
      this.formOpen.set(false);
      this.store.clearMutationError();
      if (this.openCreate) void this.router.navigateByUrl('/tasks', { replaceUrl: true });
    });
  }

  moveTask(event: UiKanbanMove): void {
    if (TASK_BOARD_OPTIONS.some(option => option.value === event.columnId)) {
      const task = this.store.tasks().find(item => item.id === event.itemId);
      if (task && taskMatchesBoardStatus(task, event.columnId as TaskBoardStatus)) {
        void this.store.load();
        return;
      }
      void this.store.move(event.itemId, toApiStatus(event.columnId as TaskBoardStatus), event.beforeId);
    }
  }
}
