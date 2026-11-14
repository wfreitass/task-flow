import { Injectable, NgZone, inject } from '@angular/core';
import { Router } from '@angular/router';
import { SessionTokenStorage } from '../../../../core/auth/session-token.storage';
import { realtimeUrl } from '../../../../core/config/api.config';
import { TaskRealtimeRepository, TaskStatusEvent } from '../../domain/repositories/task-realtime.repository';
import { isTaskStatus } from '../../domain/value-objects/task-status.vo';

@Injectable()
export class WebsocketTaskRealtimeRepository implements TaskRealtimeRepository {
  private readonly session = inject(SessionTokenStorage);
  private readonly router = inject(Router);
  private readonly zone = inject(NgZone);

  connect(onEvent: (event: TaskStatusEvent) => void, onConnected: () => void): () => void {
    let closed = false;
    let socket: WebSocket | null = null;
    let retry: ReturnType<typeof setTimeout> | undefined;
    let delay = 1000;
    const open = (): void => {
      const token = this.session.getToken();
      if (closed || !token) return;
      try { socket = new WebSocket(realtimeUrl()); }
      catch { schedule(); return; }
      const current = socket;
      current.onopen = () => current.send(JSON.stringify({ type: 'auth', token }));
      current.onmessage = message => {
        let payload: unknown;
        try { payload = JSON.parse(String(message.data)); } catch { return; }
        if (!payload || typeof payload !== 'object' || !('type' in payload)) return;
        if (payload.type === 'auth.ok') { delay = 1000; this.zone.run(onConnected); return; }
        if (payload.type !== 'task.status_changed' || !('id' in payload) || typeof payload.id !== 'string' ||
            !('occurredAt' in payload) || typeof payload.occurredAt !== 'string' || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/.test(payload.occurredAt) ||
            !('data' in payload) || !payload.data || typeof payload.data !== 'object' ||
            !('taskId' in payload.data) || !Number.isSafeInteger(payload.data.taskId) || Number(payload.data.taskId) <= 0 ||
            !('status' in payload.data) || !isTaskStatus(payload.data.status)) return;
        const event: TaskStatusEvent = { id: payload.id, occurredAt: payload.occurredAt, taskId: String(payload.data.taskId), status: payload.data.status };
        this.zone.run(() => onEvent(event));
      };
      current.onerror = () => current.close();
      current.onclose = event => {
        if (closed) return;
        if (event.code === 4401 && this.session.getToken() === token) {
          this.zone.run(() => { this.session.clear(); void this.router.navigateByUrl('/login', { replaceUrl: true }); });
          return;
        }
        schedule();
      };
    };
    const schedule = (): void => {
      if (closed || retry) return;
      retry = setTimeout(() => { retry = undefined; open(); }, delay);
      delay = Math.min(delay * 2, 30000);
    };
    open();
    return () => { closed = true; if (retry) clearTimeout(retry); socket?.close(); };
  }
}
