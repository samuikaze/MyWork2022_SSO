import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { SignOutResponse } from '../abstracts/data/signout-response';
import { CommonService } from '../services/common.service';
import { RequestService } from '../services/request.service';

@Component({
  selector: 'app-sign-out',
  templateUrl: './sign-out.component.html',
  styleUrls: ['./sign-out.component.sass']
})
export class SignOutComponent implements OnInit {

  public signInCurrently = false;
  constructor(
    private commonService: CommonService,
    private requestService: RequestService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.commonService.setTitle("登出");

    this.commonService.checkIfSignInCurrently()
      .then(auth => this.signInCurrently = auth);
  }

  public submit(): void {
    let token = localStorage.getItem("accessToken");

    if (token == null) {
      alert("您目前未登入系統");
      throw new Error("您目前未登入系統");
    }

    this.requestService.signOut(token)
      .subscribe({
        next: (response: SignOutResponse) => {
          localStorage.removeItem("user");
          localStorage.removeItem("accessToken");

          // this.router.navigateByUrl("/signin");
          location.href = "/signin";
        },
        error: (errors) => {
          alert(errors);
        }
      });
  }
}
