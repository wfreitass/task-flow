import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { UiIconComponent } from '../../../../../shared/ui/icon/icon.component';

@Component({
  selector: 'app-tasks-empty-state',
  standalone: true,
  imports: [MatButtonModule, UiIconComponent],
  templateUrl: './tasks-empty-state.component.html',
  styleUrl: './tasks-empty-state.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TasksEmptyStateComponent {
  @Input() busy = false;
  @Output() readonly createRequested = new EventEmitter<void>();
}
