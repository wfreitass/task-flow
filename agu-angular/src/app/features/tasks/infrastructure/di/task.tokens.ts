import { InjectionToken } from '@angular/core';
import { TaskRepository } from '../../domain/repositories/task.repository';

export const TASK_REPOSITORY = new InjectionToken<TaskRepository>('TaskRepository');
