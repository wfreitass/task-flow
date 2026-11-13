import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

@Component({
  selector: 'app-task-summary',
  standalone: true,
  template: `
    <div class="summary" aria-label="Resumo das tarefas">
      <div><span class="label">Todas as tarefas</span><strong>{{ total }}</strong><span class="detail">Um olhar para o seu trabalho</span></div>
      <div><span class="label"><i></i>A fazer</span><strong>{{ pending }}</strong><span class="detail">Prontas para o próximo passo</span></div>
      <div><span class="label"><i class="amber"></i>Em andamento</span><strong>{{ inProgress }}</strong><span class="detail">Ideias em movimento</span></div>
      <div><span class="label"><i class="green"></i>Concluídas</span><strong>{{ completed }}</strong><span class="detail">Pequenas e grandes conquistas</span></div>
    </div>
  `,
  styles: [`
    .summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin:28px 0}
    .summary>div{display:flex;flex-direction:column;padding:20px;border:1px solid #e1e8de;border-radius:12px;background:#fff}
    .label{color:#788574;font-size:11px}strong{margin:10px 0 8px;color:#334d35;font-size:29px;font-weight:600}.detail{color:#96a08f;font-size:10px}
    i{display:inline-block;width:6px;height:6px;margin-right:7px;border-radius:50%;background:#94a38c}.amber{background:#cca95f}.green{background:#6d9f6b}
    @media(max-width:1050px){.detail{display:none}}@media(max-width:600px){.summary{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.summary>div{padding:15px}strong{font-size:25px}}
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TaskSummaryComponent {
  @Input() total = 0;
  @Input() pending = 0;
  @Input() inProgress = 0;
  @Input() completed = 0;
}
