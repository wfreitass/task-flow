import { InjectionToken } from '@angular/core';
import { TaskRepository } from '../../domain/repositories/task.repository';
import { TaskRealtimeRepository } from '../../domain/repositories/task-realtime.repository';
import { TaskActivityRepository } from '../../domain/repositories/task-activity.repository';

export const TASK_REPOSITORY = new InjectionToken<TaskRepository>('TaskRepository');
export const TASK_REALTIME_REPOSITORY = new InjectionToken<TaskRealtimeRepository>('TaskRealtimeRepository');
export const TASK_ACTIVITY_REPOSITORY = new InjectionToken<TaskActivityRepository>('TaskActivityRepository');
