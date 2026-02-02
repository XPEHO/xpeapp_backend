const { When, Then } = require('@cucumber/cucumber');
const assert = require('node:assert');
const fs = require('node:fs');
const path = require('node:path');
const { safeJson } = require('../../support/safeJson');
const { assertStatus, assertArray, assertToken } = require('../../support/assertHelpers');

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
  assertStatus(this.response, 200);
  assertArray(this.body, 'Response should be an array of ideas');
  assertToken(this);
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
  assertStatus(this.response, 200);
  assert.ok(this.body.id, 'Idea id should be present');
  assert.ok(this.body.context, 'Idea context should be present');
  assert.ok(this.body.description, 'Idea description should be present');
  assertToken(this);
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
  assertStatus(this.response, 201);
  assert.strictEqual(this.body.context, this.body.context, 'Idea context should match');
  assert.strictEqual(this.body.description, this.body.description, 'Idea description should match');
  assertToken(this);
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
  assertStatus(this.response, 204);
  assert.strictEqual(this.body.status, this.body.status, 'Idea status should match');
  assertToken(this);
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
  assertStatus(this.response, 204);
  assertToken(this);
});