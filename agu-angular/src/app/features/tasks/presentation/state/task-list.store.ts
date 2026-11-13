import { Injectable, computed, inject, signal } from '@angular/core';
import { CreateTaskUseCase } from '../../application/use-cases/create-task.use-case';
import { DeleteTaskUseCase } from '../../application/use-cases/delete-task.use-case';
import { ListTasksUseCase } from '../../application/use-cases/list-tasks.use-case';
import { LoadTaskExamplesUseCase } from '../../application/use-cases/load-task-examples.use-case';
import { MoveTaskUseCase } from '../../application/use-cases/move-task.use-case';
import { UpdateTaskUseCase } from '../../application/use-cases/update-task.use-case';
import { Task, TaskDetails, TaskValidationError } from '../../domain/entities/task.entity';
import { TaskStatus } from '../../domain/value-objects/task-status.vo';

@Injectable()
export class TaskListStore {
  private readonly listTasks = inject(ListTasksUseCase);
  private readonly createTask = inject(CreateTaskUseCase);
  private readonly updateTask = inject(UpdateTaskUseCase);
  private readonly deleteTask = inject(DeleteTaskUseCase);
  private readonly moveTask = inject(MoveTaskUseCase);
  private readonly loadExamples = inject(LoadTaskExamplesUseCase);
  private readonly _tasks = signal<readonly Task[]>([]);
  private readonly _loading = signal(true);
  private readonly _saving = signal(false);
  private readonly _error = signal<string | null>(null);
  private readonly _mutationError = signal<string | null>(null);
  private readonly _query = signal('');
  private readonly _status = signal<TaskStatus | 'all'>('all');

  readonly tasks = this._tasks.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly saving = this._saving.asReadonly();
  readonly error = this._error.asReadonly();
  readonly mutationError = this._mutationError.asReadonly();
  readonly total = computed(() => this._tasks().length);
  readonly pending = computed(() => this._tasks().filter(task => task.status === 'pending').length);
  readonly inProgress = computed(() => this._tasks().filter(task => task.status === 'in_progress').length);
  readonly completed = computed(() => this._tasks().filter(task => task.status === 'completed').length);
  readonly filteredTasks = computed(() => {
    const query = this._query().trim().toLocaleLowerCase('pt-BR');
    return this._tasks().filter(task =>
      (this._status() === 'all' || task.status === this._status()) &&
      `${task.title} ${task.description}`.toLocaleLowerCase('pt-BR').includes(query),
    );
  });

  setQuery(value: string): void { this._query.set(value); }
  setStatus(value: TaskStatus | 'all'): void { this._status.set(value); }
  clearMutationError(): void { this._mutationError.set(null); }

  async load(): Promise<void> {
    this._loading.set(true);
    this._error.set(null);
    try { this._tasks.set(await this.listTasks.execute()); }
    catch { this._error.set('Não foi possível carregar as tarefas deste navegador. Verifique o armazenamento e tente novamente.'); }
    finally { this._loading.set(false); }
  }

  async save(details: TaskDetails, id?: string): Promise<boolean> {
    return this.mutate(async () => {
      const task = id ? await this.updateTask.execute(id, details) : await this.createTask.execute(details);
      this._tasks.update(tasks => id ? tasks.map(item => item.id === id ? task : item) : [...tasks, task]);
    });
  }

  async remove(id: string): Promise<boolean> {
    return this.mutate(async () => {
      await this.deleteTask.execute(id);
      this._tasks.update(tasks => tasks.filter(task => task.id !== id));
    });
  }

  async move(id: string, status: TaskStatus, beforeId: string | null): Promise<boolean> {
    const moved = await this.mutate(async () => { this._tasks.set(await this.moveTask.execute(id, status, beforeId)); });
    if (!moved) this._tasks.update(tasks => [...tasks]);
    return moved;
  }

  async addExamples(): Promise<void> {
    await this.mutate(async () => { this._tasks.set(await this.loadExamples.execute()); });
  }

  private async mutate(action: () => Promise<void>): Promise<boolean> {
    if (this._saving()) return false;
    this._saving.set(true);
    this._mutationError.set(null);
    try { await action(); return true; }
    catch (error: unknown) {
      this._mutationError.set(error instanceof TaskValidationError ? error.message : 'Não foi possível salvar a alteração. Tente novamente.');
      return false;
    } finally { this._saving.set(false); }
  }
}
