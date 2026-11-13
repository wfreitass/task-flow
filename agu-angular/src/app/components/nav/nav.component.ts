import { ChangeDetectionStrategy, Component, OnInit, inject } from '@angular/core';
import { AuthService } from '../../services/auth.service';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { UiIconComponent } from '../../shared/ui/icon/icon.component';
import { CurrentUserStore } from '../../features/auth/presentation/state/current-user.store';

@Component({
  selector: 'app-nav',
  templateUrl: './nav.component.html',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, MatButtonModule, UiIconComponent],
  styleUrls: ['./nav.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NavComponent implements OnInit {
  private readonly authService = inject(AuthService);
  readonly currentUser = inject(CurrentUserStore);

  ngOnInit(): void {
    void this.currentUser.load();
  }

  logout() {
    this.currentUser.clear();
    this.authService.logout();
  }
}
