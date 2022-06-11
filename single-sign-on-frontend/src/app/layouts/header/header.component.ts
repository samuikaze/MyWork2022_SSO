import { Component, OnInit } from '@angular/core';
import { CommonService } from 'src/app/services/common.service';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.sass']
})
export class HeaderComponent implements OnInit {

  public signInCurrently = false;
  constructor(private commonService: CommonService) { }

  ngOnInit(): void {
    this.signInCurrently = this.commonService.checkIfSignInCurrently();
  }

}
