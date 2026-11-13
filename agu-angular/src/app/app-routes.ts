import { Routes } from '@angular/router';

import { AuthGuard } from './core/auth/auth.guard';
import { MainLayoutComponent } from './core/layout/main-layout/main-layout.component';
import {RegisterComponent} from "./components/register/register.component";

export const routes: Routes = [
  {
    path: 'login',
    loadChildren: () => import('./features/identity/identity.routes').then(m => m.IDENTITY_ROUTES),
  },
  { path: 'register', component: RegisterComponent },
  {
    path: '',
    component: MainLayoutComponent,
    children: [
      {
        path: 'tasks', canActivate: [AuthGuard],
        loadChildren: () => import('./features/tasks/tasks.routes').then(m => m.TASK_ROUTES),
      },
      { path: 'task', redirectTo: 'tasks/new', pathMatch: 'full' },
    ],
  },
  { path: '', redirectTo: '/tasks', pathMatch: 'full' }
];
