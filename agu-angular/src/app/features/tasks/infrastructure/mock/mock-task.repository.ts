import { DOCUMENT } from '@angular/common';
import { Injectable, inject } from '@angular/core';
import { Task, TaskDetails } from '../../domain/entities/task.entity';
import { TaskRepository } from '../../domain/repositories/task.repository';
import { TaskStatus } from '../../domain/value-objects/task-status.vo';
import { MockTaskMapper } from '../mappers/mock-task.mapper';

@Injectable()
export class MockTaskRepository implements TaskRepository {
  private readonly browser = inject(DOCUMENT).defaultView;
  private readonly storageKey = 'task-flow.demo.tasks.v1';

  async findAll(): Promise<Task[]> { return this.read(); }

  async create(details: TaskDetails): Promise<Task> {
    const task = Task.create(this.window.crypto.randomUUID(), details);
    this.write([...this.read(), task]);
    return task;
  }

  async update(id: string, details: TaskDetails): Promise<Task> {
    const tasks = this.read();
    const task = this.find(tasks, id).withDetails(details);
    this.write(tasks.map(item => item.id === id ? task : item));
    return task;
  }

  async remove(id: string): Promise<void> {
    const tasks = this.read();
    this.find(tasks, id);
    this.write(tasks.filter(task => task.id !== id));
  }

  async move(id: string, status: TaskStatus, beforeId: string | null): Promise<Task[]> {
    const tasks = this.read();
    const task = this.find(tasks, id).withStatus(status);
    const others = tasks.filter(item => item.id !== id);
    const beforeIndex = beforeId ? others.findIndex(item => item.id === beforeId && item.status === status) : -1;
    const target = beforeIndex >= 0 ? beforeIndex : others.length;
    others.splice(target, 0, task);
    this.write(others);
    return others;
  }

  private find(tasks: readonly Task[], id: string): Task {
    const task = tasks.find(item => item.id === id);
    if (!task) throw new Error('Task not found');
    return task;
  }

  private read(): Task[] {
    const raw = this.window.localStorage.getItem(this.storageKey);
    if (raw === null) return [];
    const values: unknown = JSON.parse(raw);
    if (!Array.isArray(values)) throw new Error('Invalid local tasks');
    const tasks = values.map((value: unknown) => MockTaskMapper.toDomain(value));
    if (new Set(tasks.map(task => task.id)).size !== tasks.length) throw new Error('Duplicate local task IDs');
    return tasks;
  }

  private write(tasks: readonly Task[]): void {
    this.window.localStorage.setItem(this.storageKey, JSON.stringify(tasks.map(MockTaskMapper.toSnapshot)));
  }

  private get window(): Window {
    if (!this.browser) throw new Error('Browser unavailable');
    return this.browser;
  }
}
