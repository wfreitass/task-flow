import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-login-brand',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './login-brand.component.html',
  styleUrl: './login-brand.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LoginBrandComponent {}
