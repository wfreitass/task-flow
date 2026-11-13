import { Provider, inject } from '@angular/core';
import { CreateTaskUseCase } from '../../application/use-cases/create-task.use-case';
import { DeleteTaskUseCase } from '../../application/use-cases/delete-task.use-case';
import { ListTasksUseCase } from '../../application/use-cases/list-tasks.use-case';
import { MoveTaskUseCase } from '../../application/use-cases/move-task.use-case';
import { UpdateTaskUseCase } from '../../application/use-cases/update-task.use-case';
import { HttpTaskRepository } from '../http/http-task.repository';
import { TASK_REPOSITORY } from './task.tokens';

export function provideTasks(): Provider[] {
  return [
    { provide: TASK_REPOSITORY, useClass: HttpTaskRepository },
    { provide: ListTasksUseCase, useFactory: () => new ListTasksUseCase(inject(TASK_REPOSITORY)) },
    { provide: CreateTaskUseCase, useFactory: () => new CreateTaskUseCase(inject(TASK_REPOSITORY)) },
    { provide: UpdateTaskUseCase, useFactory: () => new UpdateTaskUseCase(inject(TASK_REPOSITORY)) },
    { provide: DeleteTaskUseCase, useFactory: () => new DeleteTaskUseCase(inject(TASK_REPOSITORY)) },
    { provide: MoveTaskUseCase, useFactory: () => new MoveTaskUseCase(inject(TASK_REPOSITORY)) },
  ];
}
