import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { UiIconComponent } from '../../../../../shared/ui/icon/icon.component';

@Component({
  selector: 'app-tasks-empty-state',
  standalone: true,
  imports: [MatButtonModule, UiIconComponent],
  template: `
    <section class="empty-state" aria-labelledby="empty-title">
      <div class="empty-illustration" aria-hidden="true">
        <div class="mini-card"><span></span><i></i><i></i></div>
        <div class="mini-check"><app-ui-icon name="check" /></div>
      </div>
      <span class="eyebrow">UM NOVO COMEÇO</span>
      <h2 id="empty-title">Seu próximo passo começa aqui</h2>
      <p>Você ainda não possui tarefas cadastradas.<br>Crie a primeira e organize o que vem pela frente.</p>
      <button mat-flat-button color="primary" (click)="createRequested.emit()" [disabled]="busy"><app-ui-icon name="plus" /> Criar primeira tarefa</button>
    </section>
  `,
  styleUrl: './tasks-empty-state.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TasksEmptyStateComponent {
  @Input() busy = false;
  @Output() readonly createRequested = new EventEmitter<void>();
}
