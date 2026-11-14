export interface AuthenticatedUser {
  readonly id: number;
  readonly username: string;
  readonly email: string;
  readonly profile: { readonly id: number; readonly code: string; readonly name: string };
  readonly roles: readonly string[];
}
