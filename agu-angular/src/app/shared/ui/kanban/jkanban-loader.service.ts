import { DOCUMENT } from '@angular/common';
import { Injectable, inject } from '@angular/core';
import { JkanbanConstructor } from './jkanban.types';

@Injectable({ providedIn: 'root' })
export class JkanbanLoader {
  private readonly document = inject(DOCUMENT);
  private pending: Promise<JkanbanConstructor> | null = null;

  load(): Promise<JkanbanConstructor> {
    if (this.pending) return this.pending;
    const browser = this.document.defaultView as (Window & { jKanban?: JkanbanConstructor }) | null;
    if (browser?.jKanban) return Promise.resolve(browser.jKanban);

    this.pending = new Promise<JkanbanConstructor>((resolve, reject) => {
      const script = this.document.createElement('script');
      script.src = new URL('assets/vendor/jkanban/jkanban.min.js', this.document.baseURI).href;
      script.async = true;
      script.onload = () => browser?.jKanban ? resolve(browser.jKanban) : reject(new Error('jKanban constructor unavailable'));
      script.onerror = () => { script.remove(); reject(new Error('Could not load jKanban')); };
      this.document.head.append(script);
    }).catch((error: unknown) => { this.pending = null; throw error; });
    return this.pending;
  }
}
