import { FullUser } from "./full-user";

export interface SignIn {
  user: FullUser;
  tokenType: string;
  accessToken: string;
}
