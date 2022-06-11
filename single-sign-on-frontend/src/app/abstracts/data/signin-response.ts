import { BaseResponse } from "../base-response";
import { SignIn } from "../sign-in";

export interface SignInResponse extends BaseResponse {
  data: null|SignIn;
}
