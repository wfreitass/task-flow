import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable } from 'rxjs';
import { SessionTokenStorage } from '../core/auth/session-token.storage';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = 'http://localhost:9000/api';

  constructor(private http: HttpClient, private router: Router, private storage: SessionTokenStorage) {}

  register(username: string, password: string): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/register`, { username, password });
  }

  logout() {
    this.storage.clear();
    this.router.navigate(['/login']);
  }

  isAuthenticated(): boolean {
    return !!this.storage.getToken();
  }
}
