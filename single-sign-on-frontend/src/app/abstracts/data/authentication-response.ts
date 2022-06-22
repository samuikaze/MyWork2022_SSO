import { BaseResponse } from "../base-response";
import { FullUser } from "../full-user";

export interface AuthenticationResponse extends BaseResponse {
  data: null|FullUser;
}
