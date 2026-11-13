import { DOCUMENT } from '@angular/common';
import { Injectable, inject } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class SessionTokenStorage {
  private readonly document = inject(DOCUMENT);

  getToken(): string | null {
    try {
      return this.storage.getItem('token');
    } catch {
      return null;
    }
  }

  setToken(token: string): void {
    this.storage.setItem('token', token);
  }

  clear(): void {
    this.storage.removeItem('token');
  }

  private get storage(): Storage {
    const browser = this.document.defaultView;

    if (!browser) {
      throw new Error('Browser storage is unavailable');
    }

    return browser.localStorage;
  }
}
