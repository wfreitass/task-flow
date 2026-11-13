import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { NavComponent } from '../../../components/nav/nav.component';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [NavComponent, RouterOutlet],
  template: '<app-nav /><main><router-outlet /></main>',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class MainLayoutComponent {}
