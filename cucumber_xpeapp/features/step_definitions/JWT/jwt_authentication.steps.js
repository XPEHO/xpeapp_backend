const { Given, When, Then } = require('@cucumber/cucumber');
const assert = require('node:assert');
const fetch = require('node-fetch');

let response;

Given('the WordPress JWT API is available', async function () {
  this.apiUrl = process.env.WP_API_URL || 'http://localhost:7830/wp-json/jwt-auth/v1/token';
  });


When('I login with username {string} and password {string}', async function (username, password) {
  response = await fetch(this.apiUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
  });
  this.body = await response.json();
});


Then('I receive a valid JWT token', function () {
  assert.strictEqual(response.status, 200, 'Response status should be 200');
  assert.ok(this.body.token, 'JWT token should be present');
});
  
Then('I receive a invalid JWT token', function () {
  assert.strictEqual(response.status, 403, 'Response status should be 403');
  assert.ok(!this.body.token, 'JWT token should not be present');
});
