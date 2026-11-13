import { Routes } from '@angular/router';
import { provideTasks } from './infrastructure/di/task.providers';

export const TASK_ROUTES: Routes = [
  {
    path: '',
    providers: provideTasks(),
    children: [
      { path: '', loadComponent: () => import('./presentation/pages/task-list/task-list.component').then(m => m.TaskListComponent) },
      { path: 'new', data: { openCreate: true }, loadComponent: () => import('./presentation/pages/task-list/task-list.component').then(m => m.TaskListComponent) },
    ],
  },
];
