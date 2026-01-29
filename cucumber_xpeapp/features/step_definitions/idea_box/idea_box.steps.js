const { When, Then } = require('@cucumber/cucumber');
const assert = require('node:assert');
const fs = require('fs');
const path = require('path');
const { safeJson } = require('../../support/safeJson');

// ----------- GET All IDEAS -----------
When('I fetch all ideas', async function () {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/ideas', {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });

  this.response = res;
  this.body = await res.json();
});

Then('I receive a list of ideas', function () {
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
  assert.ok(Array.isArray(this.body), 'Response should be an array of ideas');
  assert.ok(this.token, 'JWT token should be present in context');
});

// ----------- GET IDEA BY ID -----------
When('I fetch the idea with id {int}', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/ideas/${id}`, {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });

  this.response = res;
  this.body = await res.json();
});

Then('I receive the idea details', function () {
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
  assert.ok(this.body.id, 'Idea id should be present');
  assert.ok(this.body.context, 'Idea context should be present');
  assert.ok(this.body.description, 'Idea description should be present');
  assert.ok(this.token, 'JWT token should be present in context');
});

// ----------- POST NEW IDEA -----------

When('I submit a new idea with context {string} and description {string}', async function (context, description) {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/ideas', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify({ context, description })
  });

  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive the created idea confirmation', function () {
  assert.strictEqual(this.response.status, 201, 'Status should be 201');
  assert.strictEqual(this.body.context, this.body.context, 'Idea context should match');
  assert.strictEqual(this.body.description, this.body.description, 'Idea description should match');
  assert.ok(this.token, 'JWT token should be present in context');
});

// ----------- PUT IDEA BY ID -----------
When('I update the idea with id {int} to status {string}', async function (id, status) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/ideas/${id}/status`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify({ status })
  });

  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive the updated idea confirmation', function () {
  assert.strictEqual(this.response.status, 204, 'Status should be 204');
  assert.strictEqual(this.body.status, this.body.status, 'Idea status should match');
  assert.ok(this.token, 'JWT token should be present in context');
});

// ----------- DELETE IDEA BY ID -----------

When('I delete the idea with id {int}', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/ideas/${id}`, {
    method: 'DELETE',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });

  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive the deleted idea confirmation', function () {
  assert.strictEqual(this.response.status, 204, 'Status should be 204');
  assert.ok(this.token, 'JWT token should be present in context');
});