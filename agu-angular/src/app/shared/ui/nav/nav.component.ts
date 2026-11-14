import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { UiIconComponent, UiIconName } from '../icon/icon.component';

export interface NavLink { readonly path: string; readonly label: string; readonly icon: UiIconName; }

@Component({
  selector: 'app-nav',
  templateUrl: './nav.component.html',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, MatButtonModule, UiIconComponent],
  styleUrls: ['./nav.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NavComponent {
  @Input() homeLink = '/';
  @Input() homeLabel = '';
  @Input() brandPrimary = '';
  @Input() brandSecondary = '';
  @Input() navigationLabel = '';
  @Input() links: readonly NavLink[] = [];
  @Input() footerCaption = '';
  @Input() footerMessage = '';
  @Input() footerDetail = '';
  @Input() avatar = '';
  @Input() accountName = '';
  @Input() accountDetail = '';
  @Input() actionLabel = '';
  @Output() readonly action = new EventEmitter<void>();
}
