import { Routes } from '@angular/router';

import { AuthGuard } from './core/auth/auth.guard';
import { MainLayoutComponent } from './core/layout/main-layout/main-layout.component';
import {RegisterComponent} from "./components/register/register.component";
import {TaskListComponent} from "./components/task-list/task-list.component";
import {TaskComponent} from "./components/task/task.component";

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
      { path: 'tasks', component: TaskListComponent, canActivate: [AuthGuard] },
      { path: 'task', component: TaskComponent, canActivate: [AuthGuard] },
    ],
  },
  { path: '', redirectTo: '/tasks', pathMatch: 'full' }
];
