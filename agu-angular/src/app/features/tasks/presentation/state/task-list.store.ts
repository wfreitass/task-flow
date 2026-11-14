import { DestroyRef, Injectable, computed, inject, signal } from '@angular/core';
import { CreateTaskUseCase } from '../../application/use-cases/create-task.use-case';
import { DeleteTaskUseCase } from '../../application/use-cases/delete-task.use-case';
import { ListTasksUseCase } from '../../application/use-cases/list-tasks.use-case';
import { MoveTaskUseCase } from '../../application/use-cases/move-task.use-case';
import { UpdateTaskUseCase } from '../../application/use-cases/update-task.use-case';
import { WatchTaskStatusUseCase } from '../../application/use-cases/watch-task-status.use-case';
import { Task, TaskDetails, TaskValidationError } from '../../domain/entities/task.entity';
import { TaskStatus } from '../../domain/value-objects/task-status.vo';
import { TaskAttachmentInput } from '../../domain/repositories/task.repository';
import { TaskStatusEvent } from '../../domain/repositories/task-realtime.repository';
import { TaskBoardStatus, taskMatchesBoardStatus } from '../task-presentation';

@Injectable()
export class TaskListStore {
  private readonly listTasks = inject(ListTasksUseCase);
  private readonly createTask = inject(CreateTaskUseCase);
  private readonly updateTask = inject(UpdateTaskUseCase);
  private readonly deleteTask = inject(DeleteTaskUseCase);
  private readonly moveTask = inject(MoveTaskUseCase);
  private readonly watchStatus = inject(WatchTaskStatusUseCase);
  private readonly destroyRef = inject(DestroyRef);
  private readonly _tasks = signal<readonly Task[]>([]);
  private readonly _loading = signal(true);
  private readonly _saving = signal(false);
  private readonly _error = signal<string | null>(null);
  private readonly _mutationError = signal<string | null>(null);
  private readonly _query = signal('');
  private readonly _status = signal<TaskBoardStatus | 'all'>('all');
  private readonly _searchResults = signal<readonly Task[]>([]);
  readonly searchLoading = signal(false);
  readonly searchError = signal<string | null>(null);
  private searchTimer?: ReturnType<typeof setTimeout>;
  private searchVersion = 0;
  private loadVersion = 0;
  private eventSequence = 0;
  private connections = 0;
  private readonly latestEvents = new Map<string, { event: TaskStatusEvent; sequence: number }>();

  readonly tasks = this._tasks.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly saving = this._saving.asReadonly();
  readonly error = this._error.asReadonly();
  readonly mutationError = this._mutationError.asReadonly();
  readonly total = computed(() => this._tasks().length);
  readonly pending = computed(() => this._tasks().filter(task => taskMatchesBoardStatus(task, 'pending')).length);
  readonly inProgress = computed(() => this._tasks().filter(task => taskMatchesBoardStatus(task, 'in_progress')).length);
  readonly completed = computed(() => this._tasks().filter(task => taskMatchesBoardStatus(task, 'completed')).length);
  readonly filteredTasks = computed(() => {
    const status = this._status();
    const tasks = this._query().trim() ? this._searchResults() : this._tasks();
    return tasks.filter(task => status === 'all' || taskMatchesBoardStatus(task, status));
  });

  constructor() {
    const stop = this.watchStatus.execute(event => this.onStatus(event), () => this.onConnected());
    this.destroyRef.onDestroy(() => { stop(); if (this.searchTimer) clearTimeout(this.searchTimer); this.searchVersion++; this.loadVersion++; });
  }

  setQuery(value: string): void {
    this._query.set(value);
    if (this.searchTimer) clearTimeout(this.searchTimer);
    this.searchVersion++;
    this.searchError.set(null);
    if (!value.trim()) { this.searchLoading.set(false); this._searchResults.set([]); return; }
    this.searchLoading.set(true);
    this.searchTimer = setTimeout(() => { this.searchTimer = undefined; void this.search(); }, 350);
  }
  setStatus(value: TaskBoardStatus | 'all'): void { this._status.set(value); }
  clearMutationError(): void { this._mutationError.set(null); }

  async load(): Promise<void> {
    const version = ++this.loadVersion;
    const started = this.eventSequence;
    this._loading.set(true);
    this._error.set(null);
    try {
      const tasks = await this.listTasks.execute();
      if (version !== this.loadVersion) return;
      this._tasks.set(this.applyEventsAfter(tasks, started));
      if (this._query().trim()) void this.search();
    }
    catch (error: unknown) { if (version === this.loadVersion) this._error.set(error instanceof Error ? error.message : 'Não foi possível carregar as tarefas.'); }
    finally { if (version === this.loadVersion) this._loading.set(false); }
  }

  async search(): Promise<void> {
    const query = this._query().trim();
    if (!query) return;
    const version = ++this.searchVersion;
    const started = this.eventSequence;
    this.searchLoading.set(true);
    this.searchError.set(null);
    try {
      const tasks = await this.listTasks.execute(query);
      if (version === this.searchVersion) this._searchResults.set(this.applyEventsAfter(tasks, started));
    } catch {
      if (version === this.searchVersion) this.searchError.set('Não foi possível buscar as tarefas. Tente novamente.');
    } finally { if (version === this.searchVersion) this.searchLoading.set(false); }
  }

  /**
   * Chamado a cada conexão autenticada do WebSocket. Na primeira, a carga inicial da página (`ngOnInit`) já busca a
   * lista: recarregar de novo duplicaria a requisição. Nas reconexões o servidor não reenvia o que se perdeu
   * enquanto estava desconectado, então a lista é recarregada.
   */
  private onConnected(): void {
    if (this.connections++ === 0) return;
    void this.load();
  }

  private onStatus(event: TaskStatusEvent): void {
    const previous = this.latestEvents.get(event.taskId);
    if (previous && event.occurredAt <= previous.event.occurredAt) return;
    const knownTask = this._tasks().find(task => task.id === event.taskId);
    if (knownTask?.updatedAt && Date.parse(event.occurredAt) < Date.parse(knownTask.updatedAt)) return;
    this.latestEvents.set(event.taskId, { event, sequence: ++this.eventSequence });
    const update = (tasks: readonly Task[]): readonly Task[] => tasks.map(task => task.id === event.taskId ? task.withStatus(event.status) : task);
    const known = !!knownTask;
    this._tasks.update(update);
    this._searchResults.update(update);
    if (!known) void this.load();
  }

  private applyEventsAfter(tasks: readonly Task[], sequence: number): readonly Task[] {
    return tasks.map(task => {
      const latest = this.latestEvents.get(task.id);
      return latest && latest.sequence > sequence ? task.withStatus(latest.event.status) : task;
    });
  }

  async save(details: TaskDetails, id?: string, attachments: readonly TaskAttachmentInput[] = []): Promise<boolean> {
    const saved = await this.mutate(async () => {
      const task = id ? await this.updateTask.execute(id, details) : await this.createTask.execute(details, attachments);
      this._tasks.update(tasks => id ? tasks.map(item => item.id === id ? task : item) : [...tasks, task]);
    });
    if (!saved && id) await this.load();
    if (saved && this._query().trim()) void this.search();
    return saved;
  }

  async remove(id: string): Promise<boolean> {
    const removed = await this.mutate(async () => {
      await this.deleteTask.execute(id);
      this._tasks.update(tasks => tasks.filter(task => task.id !== id));
    });
    if (removed && this._query().trim()) void this.search();
    return removed;
  }

  async move(id: string, status: TaskStatus, beforeId: string | null): Promise<boolean> {
    const moved = await this.mutate(async () => { this._tasks.set(await this.moveTask.execute(id, status, beforeId)); });
    if (!moved) this._tasks.update(tasks => [...tasks]);
    if (moved && this._query().trim()) void this.search();
    return moved;
  }

  private async mutate(action: () => Promise<void>): Promise<boolean> {
    if (this._saving()) return false;
    this._saving.set(true);
    this._mutationError.set(null);
    try { await action(); return true; }
    catch (error: unknown) {
      this._mutationError.set(error instanceof TaskValidationError || error instanceof Error ? error.message : 'Não foi possível salvar a alteração. Tente novamente.');
      return false;
    } finally { this._saving.set(false); }
  }
}
