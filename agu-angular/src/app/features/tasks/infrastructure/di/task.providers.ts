import { Provider, inject } from '@angular/core';
import { CreateTaskUseCase } from '../../application/use-cases/create-task.use-case';
import { DeleteTaskUseCase } from '../../application/use-cases/delete-task.use-case';
import { ListTasksUseCase } from '../../application/use-cases/list-tasks.use-case';
import { LoadTaskExamplesUseCase } from '../../application/use-cases/load-task-examples.use-case';
import { MoveTaskUseCase } from '../../application/use-cases/move-task.use-case';
import { UpdateTaskUseCase } from '../../application/use-cases/update-task.use-case';
import { MockTaskRepository } from '../mock/mock-task.repository';
import { TASK_EXAMPLES } from '../mock/task-examples';
import { TASK_REPOSITORY } from './task.tokens';

export function provideTasks(): Provider[] {
  return [
    { provide: TASK_REPOSITORY, useClass: MockTaskRepository },
    { provide: ListTasksUseCase, useFactory: () => new ListTasksUseCase(inject(TASK_REPOSITORY)) },
    { provide: CreateTaskUseCase, useFactory: () => new CreateTaskUseCase(inject(TASK_REPOSITORY)) },
    { provide: UpdateTaskUseCase, useFactory: () => new UpdateTaskUseCase(inject(TASK_REPOSITORY)) },
    { provide: DeleteTaskUseCase, useFactory: () => new DeleteTaskUseCase(inject(TASK_REPOSITORY)) },
    { provide: MoveTaskUseCase, useFactory: () => new MoveTaskUseCase(inject(TASK_REPOSITORY)) },
    { provide: LoadTaskExamplesUseCase, useFactory: () => new LoadTaskExamplesUseCase(inject(TASK_REPOSITORY), TASK_EXAMPLES) },
  ];
}
