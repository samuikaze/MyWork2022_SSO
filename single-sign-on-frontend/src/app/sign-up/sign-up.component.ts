import { Component, Input, OnInit } from '@angular/core';
import { RegisterUser } from '../abstracts/register-user';
import { RequestService } from '../services/request.service';
import { SignInResponse } from '../abstracts/data/signin-response';
import { CommonService } from '../services/common.service';

@Component({
  selector: 'app-sign-up',
  templateUrl: './sign-up.component.html',
  styleUrls: ['./sign-up.component.sass']
})
export class SignUpComponent implements OnInit {

  @Input() account?: string;
  @Input() password?: string;
  @Input() passwordConfirmation?: string;
  @Input() email?: string;
  @Input() name?: string;
  public signInCurrently = false;
  constructor(
    private commonService: CommonService,
    private requestService: RequestService
  ) { }

  ngOnInit(): void {
    this.signInCurrently = this.commonService.checkIfSignInCurrently();
  }

  public verifySubmitable(): boolean {
    return ! (
      this.account != null && this.account.length > 0 &&
      this.password != null && this.password.length > 0 &&
      this.passwordConfirmation != null && this.passwordConfirmation.length > 0 &&
      this.email != null && this.email.length > 0 &&
      this.name != null && this.name.length > 0
    );
  }

  public submit(): void {
    if (
      this.account != null && this.account.length > 0 &&
      this.password != null && this.password.length > 0 &&
      this.passwordConfirmation != null && this.passwordConfirmation.length > 0 &&
      this.email != null && this.email.length > 0 &&
      this.name != null && this.name.length > 0
    ) {
      let registerUser: RegisterUser = {
        account: this.account,
        password: this.password,
        password_confirmation: this.passwordConfirmation,
        email: this.email,
        name: this.name
      };

      this.requestService.signUp(registerUser)
        .subscribe({
          next: (response: SignInResponse) => {
            if (response.data != null) {
              if (localStorage.getItem("user") == null) {
                localStorage.setItem("user", JSON.stringify(response.data.user));
              }

              localStorage.setItem("accessToken", response.data.accessToken);

              location.reload();
            }
          },
          error: (errors) => {
            alert(errors);
          }
        });
    }
  }

}
