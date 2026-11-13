import { Routes } from '@angular/router';

export const USER_ROUTES: Routes = [{
  path: '',
  loadComponent: () => import('./presentation/user-list.component').then(m => m.UserListComponent),
}];
