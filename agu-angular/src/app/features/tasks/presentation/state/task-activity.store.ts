import { Injectable, inject, signal } from '@angular/core';
import { TaskActivityUseCase } from '../../application/use-cases/task-activity.use-case';
import { TaskActivity } from '../../domain/repositories/task-activity.repository';

@Injectable()
export class TaskActivityStore {
  private readonly activity = inject(TaskActivityUseCase);
  readonly comments = signal<readonly TaskActivity[]>([]);
  readonly history = signal<readonly TaskActivity[]>([]);
  readonly commentsLoading = signal(false);
  readonly historyLoading = signal(false);
  readonly sending = signal(false);
  readonly commentsError = signal<string | null>(null);
  readonly historyError = signal<string | null>(null);
  readonly sendError = signal<string | null>(null);
  private commentsVersion = 0;
  private historyVersion = 0;

  async loadComments(taskId: string, silent = false): Promise<void> {
    const version = ++this.commentsVersion;
    if (!silent) this.commentsLoading.set(true);
    this.commentsError.set(null);
    try { const comments = await this.activity.comments(taskId); if (version === this.commentsVersion) this.comments.set(comments); }
    catch { if (version === this.commentsVersion) this.commentsError.set('Não foi possível carregar os comentários.'); }
    finally { if (version === this.commentsVersion) this.commentsLoading.set(false); }
  }
  async loadHistory(taskId: string, silent = false): Promise<void> {
    const version = ++this.historyVersion;
    if (!silent) this.historyLoading.set(true);
    this.historyError.set(null);
    try {
      const history = await this.activity.history(taskId);
      if (version === this.historyVersion) this.history.set([...history].sort((a, b) => b.createdAt.localeCompare(a.createdAt) || b.id - a.id));
    }
    catch { if (version === this.historyVersion) this.historyError.set('Não foi possível carregar o histórico.'); }
    finally { if (version === this.historyVersion) this.historyLoading.set(false); }
  }
  async addComment(taskId: string, content: string): Promise<boolean> {
    if (this.sending()) return false;
    this.sending.set(true); this.sendError.set(null);
    try {
      await this.activity.addComment(taskId, content.trim());
      await Promise.all([this.loadComments(taskId, true), this.loadHistory(taskId, true)]);
      return true;
    } catch { this.sendError.set('Não foi possível enviar o comentário. Tente novamente.'); return false; }
    finally { this.sending.set(false); }
  }
}
