import { Injectable } from '@angular/core';
import { Title } from '@angular/platform-browser';

@Injectable({
  providedIn: 'root'
})
export class CommonService {

  constructor(private titleService: Title) { }

  public setTitle(newTitle: string): void {
    const EXISTS_TITLE = "Single Sign On";

    if (newTitle.length > 0) {
      this.titleService.setTitle(`${newTitle} - ${EXISTS_TITLE}`);
    } else {
      this.titleService.setTitle(EXISTS_TITLE);
    }
  }

  public checkIfSignInCurrently(): boolean {
    return localStorage.getItem("user") != null;
  }
}
