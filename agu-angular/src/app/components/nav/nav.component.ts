import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { AuthService } from '../../services/auth.service';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { UiIconComponent } from '../../shared/ui/icon/icon.component';

@Component({
  selector: 'app-nav',
  templateUrl: './nav.component.html',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, MatButtonModule, UiIconComponent],
  styleUrls: ['./nav.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NavComponent {
  private readonly authService = inject(AuthService);

  logout() {
    this.authService.logout();
  }
}
