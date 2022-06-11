import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse, HttpHeaders } from '@angular/common/http';
import { catchError, Observable, of, throwError } from 'rxjs';
import { BaseResponse } from '../abstracts/base-response';
import { RegisterUser } from '../abstracts/register-user';
import { ENV } from 'src/environments/env';
import { SignInResponse } from '../abstracts/data/signin-response';
import { SingInUser } from '../abstracts/signin-user';
import { SignOutResponse } from '../abstracts/data/signout-response';

@Injectable({
  providedIn: 'root'
})
export class RequestService {
  private backendURI: string = ENV.backendUri;
  constructor(private http: HttpClient) { }

  public getTokenInformation(token: string): Observable<BaseResponse> {
    if (token == null || token.length == 0) {
      throw Error("權杖不可留空");
    }

    const url = `${this.backendURI}/api/v1/reset/password/token`;
    const options = {
      headers: new HttpHeaders({ "Authorization": `Bearer ${token}` })
    };
    return this.http.get<BaseResponse>(url, options)
      .pipe(
        catchError((error) => {
          return throwError(() => error);
        })
      );
  }

  public signUp(registerUser: RegisterUser): Observable<SignInResponse> {
    const url = `${this.backendURI}/api/v1/user/signup`;

    return this.http.post<SignInResponse>(url, registerUser)
      .pipe(
        catchError((error: HttpErrorResponse) => {
          console.error(error);

          let msg = error.error.message;

          return throwError(() => new Error(msg));
        })
      );
  }

  public signIn(signInUser: SingInUser): Observable<SignInResponse> {
    const url = `${this.backendURI}/api/v1/user/signin`;

    return this.http.post<SignInResponse>(url, signInUser)
      .pipe(
        catchError((error: HttpErrorResponse) => {
          console.log(error);

          let msg = error.error.message;

          return throwError(() => new Error(msg));
        })
      );
  }

  public signOut(token: string): Observable<SignOutResponse> {
    const url = `${this.backendURI}/api/v1/user/signout`;
    const options = {
      headers: new HttpHeaders({ "Authorization": `Bearer ${token}` })
    };

    return this.http.post<SignOutResponse>(url, {}, options)
      .pipe(
        catchError((error: HttpErrorResponse) => {
          console.log(error);

          let msg = error.error.message;

          return throwError(() => new Error(msg));
        })
      );
  }

  /**
   * Handle Http operation that failed.
   * Let the app continue.
   * @param operation - name of the operation that failed
   * @param result - optional value to return as the observable result
   */
   private handleError<T>(operation = 'operation', result?: T) {
    return (error: any): Observable<T> => {
      // TODO: send the error to remote logging infrastructure
      console.log(error);  // log to console instead

      // TODO: better job of transforming error for user consumption
      console.log(`${operation} failed: ${error.message}`);

      // Let the app keep running by returning an empty result.
      return of(result as T);
    };
  }
}
