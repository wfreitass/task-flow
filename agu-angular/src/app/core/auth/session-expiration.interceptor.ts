import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { apiUrl } from '../config/api.config';
import { SessionTokenStorage } from './session-token.storage';

export const sessionExpirationInterceptor: HttpInterceptorFn = (request, next) => {
  const storage = inject(SessionTokenStorage);
  const router = inject(Router);
  const api = new URL(apiUrl(''));
  const target = new URL(request.url, api.origin);
  const authorization = request.headers.get('Authorization');

  return next(request).pipe(catchError((error: unknown) => {
    const token = storage.getToken();
    if (error instanceof HttpErrorResponse && error.status === 401 && token &&
        authorization === `Bearer ${token}` && target.origin === api.origin &&
        target.pathname.startsWith(api.pathname)) {
      storage.clear();
      void router.navigateByUrl('/login', { replaceUrl: true });
    }
    return throwError(() => error);
  }));
};
