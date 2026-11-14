import { ChangeDetectionStrategy, Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { MatDialog } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { UiTableColumn, UiTableComponent } from '../../../shared/ui/table/table.component';
import { User, UserProfile } from '../domain/user';
import { UserListStore } from './user-list.store';
import { CurrentUserStore } from '../../auth/presentation/state/current-user.store';
import { ChangeUserRoleDialogComponent } from './change-user-role-dialog.component';

@Component({
  selector: 'app-user-list',
  standalone: true,
  imports: [MatButtonModule, MatProgressSpinnerModule, UiTableComponent],
  providers: [UserListStore],
  templateUrl: './user-list.component.html',
  styleUrl: './user-list.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UserListComponent implements OnInit {
  readonly store = inject(UserListStore);
  readonly currentUser = inject(CurrentUserStore);
  readonly success = signal('');
  private readonly dialog = inject(MatDialog);
  private readonly destroyRef = inject(DestroyRef);
  readonly columns: readonly UiTableColumn<User>[] = [
    { key: 'username', label: 'Nome de usuário', value: user => user.username },
    { key: 'id', label: 'Código', value: user => String(user.id) },
  ];
  ngOnInit(): void { void this.store.load(); }
  changeRole(user: User): void {
    if (!this.currentUser.canManageUsers()) return;
    this.success.set('');
    this.dialog.open<ChangeUserRoleDialogComponent, User, UserProfile>(ChangeUserRoleDialogComponent, {
      data: user, width: '460px', maxWidth: 'calc(100vw - 32px)',
    }).afterClosed().pipe(takeUntilDestroyed(this.destroyRef)).subscribe(profile => {
      if (profile) this.success.set(`Perfil de ${user.username} alterado para ${profile.name}.`);
    });
  }
}
