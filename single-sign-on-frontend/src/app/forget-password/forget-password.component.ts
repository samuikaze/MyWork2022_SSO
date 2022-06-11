import { Component, Input, OnInit } from '@angular/core';
import { CommonService } from '../services/common.service';

@Component({
  selector: 'app-forget-password',
  templateUrl: './forget-password.component.html',
  styleUrls: ['./forget-password.component.sass']
})
export class ForgetPasswordComponent implements OnInit {

  @Input() emailAddress?: string;
  constructor(private commonService: CommonService) { }

  ngOnInit(): void {
    this.commonService.setTitle("忘記密碼");
  }

  public submit(): void {
    console.log(`email = ${this.emailAddress}, Fire submit event.`);
  }
}
