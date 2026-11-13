export class RegistrationRejectedError extends Error {
  constructor() {
    super('The registration data was rejected');
    this.name = 'RegistrationRejectedError';
  }
}
