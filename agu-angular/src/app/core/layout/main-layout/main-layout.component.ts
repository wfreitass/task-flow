import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { NavComponent } from '../../../components/nav/nav.component';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [NavComponent, RouterOutlet],
  template: '<app-nav /><main><router-outlet /></main>',
  styles: [':host{display:grid;grid-template-columns:224px minmax(0,1fr);min-height:100dvh;background:#f6f8f3}main{min-width:0}@media(max-width:900px){:host{grid-template-columns:minmax(0,1fr)}}'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class MainLayoutComponent {}
