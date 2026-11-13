import { Routes } from '@angular/router';

import { AuthGuard } from './core/auth/auth.guard';
import { MainLayoutComponent } from './core/layout/main-layout/main-layout.component';

export const routes: Routes = [
  {
    path: 'login',
    loadChildren: () => import('./features/auth/auth.routes').then(m => m.AUTH_ROUTES),
  },
  {
    path: 'register',
    loadChildren: () => import('./features/auth/register.routes').then(m => m.REGISTER_ROUTES),
  },
  {
    path: '',
    component: MainLayoutComponent,
    children: [
      {
        path: 'tasks', canActivate: [AuthGuard],
        loadChildren: () => import('./features/tasks/tasks.routes').then(m => m.TASK_ROUTES),
      },
      {
        path: 'users', canActivate: [AuthGuard],
        loadChildren: () => import('./features/users/users.routes').then(m => m.USER_ROUTES),
      },
      { path: 'task', redirectTo: 'tasks/new', pathMatch: 'full' },
    ],
  },
  { path: '', redirectTo: '/tasks', pathMatch: 'full' }
];
