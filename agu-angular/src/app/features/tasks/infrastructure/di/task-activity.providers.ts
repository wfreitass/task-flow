import { Provider, inject } from '@angular/core';
import { TaskActivityUseCase } from '../../application/use-cases/task-activity.use-case';
import { HttpTaskActivityRepository } from '../http/http-task-activity.repository';
import { TASK_ACTIVITY_REPOSITORY } from './task.tokens';

export function provideTaskActivity(): Provider[] {
  return [
    { provide: TASK_ACTIVITY_REPOSITORY, useClass: HttpTaskActivityRepository },
    { provide: TaskActivityUseCase, useFactory: () => new TaskActivityUseCase(inject(TASK_ACTIVITY_REPOSITORY)) },
  ];
}
