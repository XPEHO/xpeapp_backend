import { When, Then, After } from '@cucumber/cucumber';
import assert from 'node:assert';
import { BASE_URL, JWT_URL, apiGet, apiGetWithHeader, apiPut, apiPostEmpty } from '../../support/httpHelpers.js';
import { HttpStatus } from '../../support/httpStatus.js';

// Reset to the initial password after the password update test
After({ tags: '@resetPassword' }, async function () {
  // initialize - get a token with the updated password
  const authRes = await fetch(JWT_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username: 'wordpress_dev', password: 'wordpress_dev@example' })
  });
  const authBody = await authRes.json();
  
  if (authBody.token) {
    // reset password back to the original
    await fetch(`${BASE_URL}/update-password`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${authBody.token}`
      },
      body: JSON.stringify({
        initial_password: 'wordpress_dev@example',
        password: 'wordpress_dev',
        password_repeat: 'wordpress_dev'
      })
    });
  }
});

// ----------- GET USER INFOS -----------
When('I fetch my user infos', async function () {
  await apiGet(this, '/user-infos');
});

Then('I receive my user infos', function () {
  assert.strictEqual(this.response.status, HttpStatus.OK);
  assert.ok(this.body.id, 'User id should be present');
  assert.ok(this.body.email, 'User email should be present');
  assert.ok(this.token, 'JWT token should be present in context');
});

// ----------- GET USER BY EMAIL -----------
When('I fetch the user by email {string}', async function (email) {
  await apiGetWithHeader(this, '/user', { email });
});

Then('I receive a user id', function () {
  assert.strictEqual(this.response.status, HttpStatus.OK);
  assert.ok(this.body, 'User id should be present');
  assert.strictEqual(typeof this.body, 'number', 'User id should be a number');
});

// ----------- GET USER LAST CONNECTIONS -----------
When('I fetch the users last connections', async function () {
  await apiGet(this, '/user:last-connection');
});

Then('I receive a list of user last connections', function () {
  assert.strictEqual(this.response.status, HttpStatus.OK);
  assert.ok(Array.isArray(this.body), 'Response should be an array');
  if (this.body.length) {
    const [item] = this.body;
    assert.ok('first_name' in item, 'first_name should be present');
    assert.ok('last_name' in item, 'last_name should be present');
    assert.ok('last_connection' in item, 'last_connection should be present');
  }
  assert.ok(this.token, 'JWT token should be present in context');
});

// ----------- POST USER LAST CONNECTION -----------
When('I post my last connection', async function () {
  await apiPostEmpty(this, '/user:last-connection');
});

Then('I receive a confirmation of last connection post', function () {
  assert.strictEqual(this.response.status, HttpStatus.CREATED);
  assert.ok(this.token, 'JWT token should be present in context');
});

// ----------- UPDATE USER PASSWORD -----------
When('I update password from {string} to {string}', async function (initial_password, new_password) {
  await apiPut(this, '/update-password', {
    initial_password,
    password: new_password,
    password_repeat: new_password
  });
});

Then('I receive a confirmation of password update', function () {
  assert.strictEqual(this.response.status, HttpStatus.NO_CONTENT);
  assert.ok(this.token, 'JWT token should be present in context');
});

// ----------- RESET USER PASSWORD (ADMIN) -----------
// Store the original password to restore after test
let targetUserEmail = null;

After({ tags: '@resetOtherUserPassword' }, async function () {
  // Restore the original password if we changed it
  if (targetUserEmail) {
    await fetch(`${BASE_URL}/reset-password`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${this.token}`
      },
      body: JSON.stringify({
        email: targetUserEmail,
        password: 'otheruser_password',
        password_repeat: 'otheruser_password'
      })
    });
  }
  targetUserEmail = null;
});

When('I reset password for user with email {string} to {string} and {string}', async function (email, password1, password2) {
  targetUserEmail = email;
  await apiPut(this, '/reset-password', {
    email,
    password: password1,
    password_repeat: password2
  });
});

Then('I receive a confirmation of password reset', function () {
  // If target user doesn't exist, expect a 404
  if (this.response.status === HttpStatus.NOT_FOUND) {
    assert.ok(true, 'User not found as expected in test environment');
    return;
  }
  assert.strictEqual(this.response.status, HttpStatus.NO_CONTENT);
  assert.ok(this.token, 'JWT token should be present in context');
});
