import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';

@Component({
  selector: 'app-ui-file-dropzone',
  standalone: true,
  imports: [MatButtonModule],
  templateUrl: './file-dropzone.component.html',
  styleUrl: './file-dropzone.component.scss',
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
