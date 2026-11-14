import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { SessionTokenStorage } from '../../../../core/auth/session-token.storage';
import { apiUrl } from '../../../../core/config/api.config';
import { TaskActivity, TaskActivityRepository } from '../../domain/repositories/task-activity.repository';

interface ActivityDto {
  id: number; actorId: number; actorName: string | null; oldAssigneeName: string | null; newAssigneeName: string | null; kind: string; content: string | null;
  oldValue: Record<string, unknown> | null; newValue: Record<string, unknown> | null; createdAt: string;
}

@Injectable()
export class HttpTaskActivityRepository implements TaskActivityRepository {
  private readonly http = inject(HttpClient);
  private readonly session = inject(SessionTokenStorage);

  async listComments(taskId: string): Promise<readonly TaskActivity[]> { return this.list(taskId, 'comments'); }
  async listHistory(taskId: string): Promise<readonly TaskActivity[]> { return this.list(taskId, 'history'); }
  async addComment(taskId: string, content: string): Promise<void> {
    await firstValueFrom(this.http.post(apiUrl(`tasks/${this.id(taskId)}/comments`), { content }, { headers: this.headers() }));
  }
  private async list(taskId: string, path: 'comments' | 'history'): Promise<readonly TaskActivity[]> {
    const response = await firstValueFrom(this.http.get<{ data: ActivityDto[] }>(apiUrl(`tasks/${this.id(taskId)}/${path}`), { headers: this.headers() }));
    return response.data.map(item => ({ ...item }));
  }
  private id(value: string): number {
    const id = Number(value);
    if (!Number.isSafeInteger(id) || id < 1) throw new Error('Identificador de tarefa inválido.');
    return id;
  }
  private headers(): HttpHeaders {
    const token = this.session.getToken();
    if (!token) throw new Error('Sua sessão expirou. Entre novamente.');
    return new HttpHeaders({ Authorization: `Bearer ${token}` });
  }
}
