import { HttpClient, HttpErrorResponse, HttpHeaders, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { SessionTokenStorage } from '../../../../core/auth/session-token.storage';
import { apiUrl } from '../../../../core/config/api.config';
import { Task, TaskDetails } from '../../domain/entities/task.entity';
import { TaskAttachmentInput, TaskRepository } from '../../domain/repositories/task.repository';
import { TaskStatus } from '../../domain/value-objects/task-status.vo';
import { TaskApiErrorDto, TaskPageResponseDto, TaskResponseDto } from '../dto/task-response.dto';
import { TaskApiMapper } from '../mappers/task-api.mapper';

@Injectable()
export class HttpTaskRepository implements TaskRepository {
  private readonly http = inject(HttpClient);
  private readonly session = inject(SessionTokenStorage);

  async findAll(): Promise<Task[]> {
    const tasks: Task[] = [];
    let page = 1;
    let totalPages = 1;
    do {
      const params = new HttpParams().set('page', page).set('perPage', 100).set('sort', 'createdAt').set('order', 'desc');
      const response = await this.request<TaskPageResponseDto>(this.http.get<TaskPageResponseDto>(apiUrl('tasks'), { headers: this.headers(), params }));
      tasks.push(...response.data.map(TaskApiMapper.toDomain));
      totalPages = response.meta.totalPages;
      page += 1;
    } while (page <= totalPages);
    return tasks;
  }

  async create(details: TaskDetails, attachments: readonly TaskAttachmentInput[] = []): Promise<Task> {
    const payload = TaskApiMapper.toMutation(details);
    let body: typeof payload | FormData = payload;
    if (attachments.length) {
      const form = new FormData();
      for (const [key, value] of Object.entries(payload)) if (value !== null) form.append(key, String(value));
      for (const attachment of attachments) form.append('attachments[]', new Blob([attachment.bytes], { type: attachment.type }), attachment.name);
      body = form;
    }
    const created = await this.request<TaskResponseDto>(this.http.post<TaskResponseDto>(apiUrl('tasks'), body, { headers: this.headers() }));
    return TaskApiMapper.toDomain(created);
  }

  async update(id: string, details: TaskDetails): Promise<Task> {
    const numericId = this.parseId(id);
    const updated = await this.request<TaskResponseDto>(this.http.patch<TaskResponseDto>(apiUrl(`tasks/${numericId}`), TaskApiMapper.toMutation(details), { headers: this.headers() }));
    let task = TaskApiMapper.toDomain(updated);
    if (task.status !== details.status) {
      try { task = await this.changeStatus(id, details.status); }
      catch (error: unknown) {
        throw new Error(`Os campos foram salvos, mas o status não foi alterado. ${error instanceof Error ? error.message : ''}`);
      }
    }
    return task;
  }

  async remove(id: string): Promise<void> {
    await this.request<void>(this.http.delete<void>(apiUrl(`tasks/${this.parseId(id)}`), { headers: this.headers() }));
  }

  async move(id: string, status: TaskStatus, _beforeId: string | null): Promise<Task[]> {
    await this.changeStatus(id, status);
    return this.findAll();
  }

  private async changeStatus(id: string, status: TaskStatus): Promise<Task> {
    const response = await this.request<TaskResponseDto>(this.http.patch<TaskResponseDto>(
      apiUrl(`tasks/${this.parseId(id)}/status`), TaskApiMapper.toStatus(status), { headers: this.headers() },
    ));
    return TaskApiMapper.toDomain(response);
  }

  private headers(): HttpHeaders {
    const token = this.session.getToken();
    if (!token) throw new Error('Sua sessão expirou. Entre novamente para acessar as tarefas.');
    return new HttpHeaders({ Authorization: `Bearer ${token}` });
  }

  private parseId(id: string): number {
    const value = Number(id);
    if (!Number.isSafeInteger(value) || value <= 0) throw new Error('Identificador de tarefa inválido.');
    return value;
  }

  private async request<T>(request: import('rxjs').Observable<T>): Promise<T> {
    try { return await firstValueFrom(request); }
    catch (error: unknown) { throw this.toError(error); }
  }

  private toError(error: unknown): Error {
    if (error instanceof HttpErrorResponse) {
      const body = error.error as TaskApiErrorDto | null;
      const message = body?.error?.message;
      const details = body?.error?.details;
      if (details) {
        const messages = Object.values(details).filter((value): value is string => typeof value === 'string');
        if (messages.length) return new Error(messages.join(' '));
      }
      if (message) return new Error(message);
      if (error.status === 0) return new Error('Não foi possível conectar à API de tarefas. Confira se o backend está disponível.');
      if (error.status === 401) return new Error('Sua sessão expirou. Entre novamente para acessar as tarefas.');
      return new Error('A API não conseguiu concluir a operação. Tente novamente.');
    }
    return error instanceof Error ? error : new Error('Ocorreu um erro inesperado ao acessar as tarefas.');
  }
}
