import { Component } from '@angular/core';
import { AuthService } from '../../services/auth.service';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-nav',
  templateUrl: './nav.component.html',
  standalone: true,
  imports: [RouterLink],
  styleUrls: ['./nav.component.scss']
})
export class NavComponent {
  constructor(private authService: AuthService) {}

  logout() {
    this.authService.logout();
  }
}
