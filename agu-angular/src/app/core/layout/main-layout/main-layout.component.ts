import { ChangeDetectionStrategy, Component, OnInit, inject } from '@angular/core';
import { Router, RouterOutlet } from '@angular/router';
import { NavComponent, NavLink } from '../../../shared/ui/nav/nav.component';
import { CurrentUserStore } from '../../../features/auth/presentation/state/current-user.store';
import { LogoutUseCase } from '../../../features/auth/application/use-cases/logout.use-case';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [NavComponent, RouterOutlet],
  templateUrl: './main-layout.component.html',
  styleUrl: './main-layout.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class MainLayoutComponent implements OnInit {
  readonly currentUser = inject(CurrentUserStore);
  private readonly logoutUseCase = inject(LogoutUseCase);
  private readonly router = inject(Router);
  readonly links: readonly NavLink[] = [
    { path: '/tasks', label: 'Minhas tarefas', icon: 'board' },
    { path: '/users', label: 'Usuários', icon: 'users' },
  ];

  ngOnInit(): void { void this.currentUser.load(); }

  logout(): void {
    this.currentUser.clear();
    try { this.logoutUseCase.execute(); }
    finally { void this.router.navigateByUrl('/login'); }
  }
}
