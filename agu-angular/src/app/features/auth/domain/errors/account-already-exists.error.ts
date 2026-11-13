export class AccountAlreadyExistsError extends Error {
  constructor(readonly field: 'username' | 'email') {
    super(field === 'username' ? 'Username already exists' : 'Email already exists');
    this.name = 'AccountAlreadyExistsError';
  }
}
