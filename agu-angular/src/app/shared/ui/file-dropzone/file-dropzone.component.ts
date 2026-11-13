import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';

@Component({
  selector: 'app-ui-file-dropzone',
  standalone: true,
  imports: [MatButtonModule],
  template: `
    <section class="dropzone" (dragover)="onDragOver($event)" (drop)="onDrop($event)">
      <p>Arraste arquivos aqui ou selecione no seu dispositivo.</p>
      <input #picker type="file" multiple hidden accept=".pdf,.png,.jpg,.jpeg,.gif,.webp,.txt,.csv" [disabled]="disabled" (change)="onSelect($event)">
      <button mat-stroked-button type="button" [disabled]="disabled" (click)="picker.click()">Selecionar arquivos</button>
      <p class="hint">Até 5 arquivos de 10 MB cada: PDF, imagens, TXT ou CSV.</p>
      @if (error()) { <p role="alert" class="error">{{ error() }}</p> }
      @if (files().length) {
        <ul>@for (file of files(); track $index) {
          <li><span>{{ file.name }}</span><button mat-button type="button" [disabled]="disabled" [attr.aria-label]="'Remover ' + file.name" (click)="remove($index)">Remover</button></li>
        }</ul>
      }
    </section>
  `,
  styles: [':host{display:block;min-width:0}.dropzone{border:1px dashed #a8b6a4;border-radius:8px;padding:16px;text-align:center}.dropzone p{margin:0 0 10px}.hint{font-size:11px;color:#657461;margin-top:10px!important}.error{color:#9b3030;font-size:12px}ul{padding:0;margin:12px 0 0;list-style:none;text-align:left}li{display:flex;align-items:center;justify-content:space-between;gap:8px}li span{overflow-wrap:anywhere;min-width:0}'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class UiFileDropzoneComponent {
  @Input() disabled = false;
  @Output() readonly filesChange = new EventEmitter<readonly File[]>();
  readonly files = signal<readonly File[]>([]);
  readonly error = signal('');

  onDragOver(event: DragEvent): void { event.preventDefault(); }
  onDrop(event: DragEvent): void {
    event.preventDefault();
    if (!this.disabled && event.dataTransfer) this.add(Array.from(event.dataTransfer.files));
  }
  onSelect(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.add(Array.from(input.files ?? []));
    input.value = '';
  }
  remove(index: number): void {
    this.files.update(files => files.filter((_, i) => i !== index));
    this.error.set('');
    this.filesChange.emit(this.files());
  }
  private add(incoming: File[]): void {
    const files = [...this.files(), ...incoming];
    if (files.length > 5) { this.error.set('Selecione no máximo 5 arquivos.'); return; }
    if (incoming.some(file => file.size === 0 || file.size > 10 * 1024 * 1024)) { this.error.set('Cada arquivo deve ter conteúdo e no máximo 10 MB.'); return; }
    if (incoming.some(file => !/\.(pdf|png|jpe?g|gif|webp|txt|csv)$/i.test(file.name))) { this.error.set('Selecione arquivos PDF, PNG, JPEG, GIF, WebP, TXT ou CSV.'); return; }
    this.error.set('');
    this.files.set(files);
    this.filesChange.emit(files);
  }
}
