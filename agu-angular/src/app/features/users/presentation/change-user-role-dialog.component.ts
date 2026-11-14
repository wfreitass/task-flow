import { ChangeDetectionStrategy, Component, OnInit, inject, signal } from '@angular/core';
import { FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { UiFormActionsComponent } from '../../../shared/ui/form-actions/form-actions.component';
import { UiSelectComponent } from '../../../shared/ui/select/select.component';
import { CurrentUserStore } from '../../auth/presentation/state/current-user.store';
import { ChangeUserRoleUseCase } from '../application/change-user-role.use-case';
import { User, UserProfile } from '../domain/user';

@Component({
  selector: 'app-change-user-role-dialog',
  standalone: true,
  imports: [MatDialogModule, MatButtonModule, ReactiveFormsModule, UiSelectComponent, UiFormActionsComponent],
  templateUrl: './change-user-role-dialog.component.html',
  styleUrl: './change-user-role-dialog.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ChangeUserRoleDialogComponent implements OnInit {
  readonly user = inject<User>(MAT_DIALOG_DATA);
  readonly reference = inject(MatDialogRef<ChangeUserRoleDialogComponent, UserProfile>);
  private readonly changeRole = inject(ChangeUserRoleUseCase);
  private readonly currentUser = inject(CurrentUserStore);
  readonly role = new FormControl('', { nonNullable: true, validators: [Validators.required] });
  readonly form = new FormGroup({ role: this.role });
  readonly profiles = signal<readonly UserProfile[]>([]);
  readonly loading = signal(true);
  readonly loadError = signal<string | null>(null);
  get options(): readonly { value: string; label: string }[] {
    return this.profiles().map(profile => ({ value: String(profile.id), label: profile.name }));
  }
  readonly saving = signal(false);
  readonly submitted = signal(false);
  readonly error = signal<string | null>(null);

  ngOnInit(): void { void this.load(); }

  async load(): Promise<void> {
    this.loading.set(true); this.loadError.set(null);
    try {
      const [current, available] = await this.changeRole.load(this.user.id);
      this.profiles.set(available.some(item => item.id === current.id) ? available : [current, ...available]);
      this.role.setValue(String(current.id));
    } catch {
      this.loadError.set('Não foi possível carregar o perfil atual. Tente novamente.');
    } finally { this.loading.set(false); }
  }

  async submit(): Promise<void> {
    if (this.saving() || this.loading() || this.loadError()) return;
    this.submitted.set(true);
    const profile = this.profiles().find(item => String(item.id) === this.role.value);
    if (!profile) { this.role.markAsTouched(); return; }
    this.saving.set(true); this.reference.disableClose = true; this.role.disable(); this.error.set(null);
    try {
      await this.changeRole.execute(this.user.id, profile.id);
      if (this.currentUser.user()?.id === this.user.id) await this.currentUser.load();
      this.reference.close(profile);
    } catch (error: unknown) {
      this.error.set(error instanceof Error ? error.message : 'Não foi possível alterar o perfil.');
    } finally { this.saving.set(false); this.reference.disableClose = false; this.role.enable(); }
  }
}
