import { When, Then, After } from '@cucumber/cucumber';
import assert from 'node:assert';
import fs from 'node:fs';
import path from 'node:path';

import { safeJson } from '../../support/safeJson.js';

// Reset to the initial password after the password update test
After({ tags: '@resetPassword' }, async function () {
  // initialize - get a token with the updated password
  const authRes = await fetch('http://localhost:7830/wp-json/jwt-auth/v1/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username: 'wordpress_dev', password: 'wordpress_dev@example' })
  });
  const authBody = await authRes.json();
  
  if (authBody.token) {
    // reset password back to the original
    await fetch('http://localhost:7830/wp-json/xpeho/v1/update-password', {
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
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/user-infos', {
    headers: { Authorization: `Bearer ${this.token}` }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive my user infos', function () {
  assert.strictEqual(this.response.status, 200);
  assert.ok(this.body.id, 'User id should be present');
  assert.ok(this.body.email, 'User email should be present');
  assert.ok(this.token, 'JWT token should be present in context');
});

// ----------- GET USER BY EMAIL -----------
When('I fetch the user by email {string}', async function (email) {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/user', {
    headers: {
      email: email
    }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a user id', function () {
  assert.strictEqual(this.response.status, 200);
  assert.ok(this.body, 'User id should be present');
  assert.strictEqual(typeof this.body, 'number', 'User id should be a number');
});

// ----------- GET USER LAST CONNECTIONS -----------
When('I fetch the users last connections', async function () {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/user:last-connection', {
    headers: { Authorization: `Bearer ${this.token}` }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a list of user last connections', function () {
  assert.strictEqual(this.response.status, 200);
  assert.ok(Array.isArray(this.body), 'Response should be an array');
  if (this.body.length > 0) {
    const item = this.body[0];
    assert.ok('first_name' in item, 'first_name should be present');
    assert.ok('last_name' in item, 'last_name should be present');
    assert.ok('last_connection' in item, 'last_connection should be present');
  }
  assert.ok(this.token, 'JWT token should be present in context');
});

// ----------- POST USER LAST CONNECTION -----------
When('I post my last connection', async function () {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/user:last-connection', {
    method: 'POST',
    headers: { Authorization: `Bearer ${this.token}` }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a confirmation of last connection post', function () {
  assert.strictEqual(this.response.status, 201, 'Status should be 201');
  assert.ok(this.token, 'JWT token should be present in context');
});

// ----------- UPDATE USER PASSWORD -----------
When('I update password from {string} to {string}', async function (initial_password, new_password) {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/update-password', {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify({
      initial_password,
      password: new_password,
      password_repeat: new_password
    })
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a confirmation of password update', function () {
  assert.strictEqual(this.response.status, 204, 'Status should be 204');
  assert.ok(this.token, 'JWT token should be present in context');
});
