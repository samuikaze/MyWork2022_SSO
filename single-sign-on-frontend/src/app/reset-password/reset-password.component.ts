import { Component, Input, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { BaseResponse } from '../abstracts/base-response';
import { User } from '../abstracts/user';
import { CommonService } from '../services/common.service';
import { RequestService } from '../services/request.service';

@Component({
  selector: 'app-reset-password',
  templateUrl: './reset-password.component.html',
  styleUrls: ['./reset-password.component.sass']
})
export class ResetPasswordComponent implements OnInit {

  @Input() userId?: number;
  @Input() emailAddress?: string;
  @Input() password?: string;
  @Input() passwordConfirmation?: string;
  public tokenIsValid: boolean;
  private token: string;

  constructor(
    private commonService: CommonService,
    private requestService: RequestService,
    private route: ActivatedRoute
  ) {
    let token = "";
    this.route.queryParams.subscribe(params => {
      token = params['token'];
    });

    this.token = token;
    this.tokenIsValid = false;
  }

  ngOnInit(): void {
    this.commonService.setTitle("重設密碼");

    let user: User;
    try {
      this.requestService.getTokenInformation(this.token)
        .subscribe((response: BaseResponse) => {
          console.log(response);
          this.userId = user.id;
          this.emailAddress = user.email;
          this.tokenIsValid = true;
        });
    } catch (error) {
      console.log(error);
      this.tokenIsValid = false;
    }
  }

  public submit(): void {
    console.log(`email = ${this.emailAddress}, pswd = ${this.password}, pswd is the same ? ${this.password == this.passwordConfirmation}`);
  }

}
