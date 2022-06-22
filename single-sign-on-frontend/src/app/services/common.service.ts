import { HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Title } from '@angular/platform-browser';
import { AuthenticationResponse } from '../abstracts/data/authentication-response';
import { RequestService } from './request.service';

@Injectable({
  providedIn: 'root'
})
export class CommonService {

  constructor(
    private titleService: Title,
    private requestService: RequestService
  ) { }

  public setTitle(newTitle: string): void {
    const EXISTS_TITLE = "Single Sign On";

    if (newTitle.length > 0) {
      this.titleService.setTitle(`${newTitle} - ${EXISTS_TITLE}`);
    } else {
      this.titleService.setTitle(EXISTS_TITLE);
    }
  }

  public async checkIfSignInCurrently(): Promise<boolean> {
    const CONDITION1 = localStorage.getItem("user") != null;
    try {
      await this.checkAuthStatus();
    } catch (error) {
      return false;
    }

    return CONDITION1;
  }

  private async checkAuthStatus() {
    const ACCESS_TOKEN = localStorage.getItem("accessToken");
    if (ACCESS_TOKEN == null) {
      throw Error("Unauthorized");
    }

    this.requestService.checkAuthenticateStatus(ACCESS_TOKEN)
      .subscribe({
        next: (response: AuthenticationResponse) => {
          if (response.data != null) {
            localStorage.setItem("user", JSON.stringify(response.data));
          }
        },
        error: (error: HttpErrorResponse) => {
          localStorage.removeItem("user");
          localStorage.removeItem("accessToken");
        }
      });
  }
}
