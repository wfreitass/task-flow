import { ChangeDetectionStrategy, Component, OnInit, inject } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { UiTableColumn, UiTableComponent } from '../../../shared/ui/table/table.component';
import { User } from '../domain/user';
import { UserListStore } from './user-list.store';

@Component({
  selector: 'app-user-list',
  standalone: true,
  imports: [MatButtonModule, MatProgressSpinnerModule, UiTableComponent],
  providers: [UserListStore],
  template: `
    <section class="page">
      <div class="breadcrumb">Área de trabalho <span>/</span> <strong>Usuários</strong></div>
      <header><span>TRABALHO EM CONJUNTO</span><h1>Usuários<span>.</span></h1><p>Consulte as pessoas cadastradas no Task Flow.</p></header>
      @if (store.loading()) {
        <div class="message" role="status"><mat-spinner diameter="30" /> Carregando usuários...</div>
      } @else if (store.error()) {
        <div class="message error" role="alert"><p>{{ store.error() }}</p><button mat-stroked-button (click)="store.load()">Tentar novamente</button></div>
      } @else {
        <p class="count">{{ store.users().length }} usuário(s)</p>
        <app-ui-table [rows]="store.users()" [columns]="columns" caption="Lista de usuários" emptyMessage="Nenhum usuário encontrado." />
      }
    </section>
  `,
  styles: [`
    :host{display:block;min-width:0}.page{max-width:1420px;margin:auto;padding:26px 38px}.breadcrumb{padding-bottom:27px;border-bottom:1px solid #e4ebe1;color:#95a08e;font-size:10px}.breadcrumb span{margin:0 10px}.breadcrumb strong{color:#6a7d60;font-weight:500}header{margin:31px 0}header>span{color:#9aaa90;font-size:9px;letter-spacing:1.7px;font-weight:600}h1{margin:9px 0;color:#2c482f;font-size:32px;letter-spacing:-1.1px}h1 span{color:#8dab75}header p,.count{color:#87977c;font-size:12px}.count{margin:24px 0 14px}.message{display:flex;align-items:center;gap:15px;padding:30px;border-radius:10px;background:white;color:#657461;font-size:13px}.error{color:#994435;background:#fff5f1;flex-wrap:wrap}@media(max-width:600px){.page{padding:18px}h1{font-size:28px}}
  `],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UserListComponent implements OnInit {
  readonly store = inject(UserListStore);
  readonly columns: readonly UiTableColumn<User>[] = [
    { key: 'username', label: 'Nome de usuário', value: user => user.username },
    { key: 'id', label: 'Código', value: user => String(user.id) },
  ];
  ngOnInit(): void { void this.store.load(); }
}
