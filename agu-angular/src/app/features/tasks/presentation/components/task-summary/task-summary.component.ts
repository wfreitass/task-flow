import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

@Component({
  selector: 'app-task-summary',
  standalone: true,
  templateUrl: './task-summary.component.html',
  styleUrl: './task-summary.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TaskSummaryComponent {
  @Input() total = 0;
  @Input() pending = 0;
  @Input() inProgress = 0;
  @Input() completed = 0;
}
