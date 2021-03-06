import { Component, Input, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { SignInResponse } from '../abstracts/data/signin-response';
import { CommonService } from '../services/common.service';
import { RequestService } from '../services/request.service';

@Component({
  selector: 'app-sign-in',
  templateUrl: './sign-in.component.html',
  styleUrls: ['./sign-in.component.sass']
})
export class SignInComponent implements OnInit {

  @Input() account?: string;
  @Input() password?: string;
  @Input() remember: boolean = false;
  public signInCurrently = false;
  constructor(
    private commonService: CommonService,
    private requestService: RequestService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.commonService.setTitle("登入");

    this.commonService.checkIfSignInCurrently()
      .then(auth => this.signInCurrently = auth);
  }

  public verifySubmitable(): boolean {
    return (
      this.account != null &&
      this.password != null
    );
  }

  public signup(): void {
    this.router.navigateByUrl("/signup");
  }

  public submit(): void {
    if (this.account != null && this.password != null && this.remember != null) {
      let signInUser = {
        account: this.account,
        password: this.password,
        remember: this.remember
      };

      console.log("fire login action!");
      this.requestService.signIn(signInUser)
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
