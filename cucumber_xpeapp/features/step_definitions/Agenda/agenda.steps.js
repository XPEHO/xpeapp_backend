
const { When, Then } = require('@cucumber/cucumber');
const assert = require('node:assert');
const fetch = require('node-fetch');
const { safeJson } = require('../../support/safeJson');
const { assertStatus, assertArray, assertToken } = require('../support/assertHelpers');

// =============================
// EVENTS TYPES API STEPS
// =============================

// ----------- GET -----------
When('I fetch the event types', async function () {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/agenda/events-types/', {
    headers: { Authorization: `Bearer ${this.token}` }
  });
  this.response = res;
  this.body = await safeJson(res);
});

// ----------- GET -----------
When('I fetch the event type by the {int}', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/agenda/events-types/${id}`, {
    headers: { Authorization: `Bearer ${this.token}` }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a list of event types', function () {
  assertStatus(this.response, 200);
  assertArray(this.body, 'Response should be an array');
  if (this.body.length > 0) {
    const item = this.body[0];
    assert.ok(item.id, 'Event type id should be present');
    assert.ok(item.label, 'Event type label should be present');
  }
  assertToken(this);
});

Then('I receive an event type detail', function () {
  assertStatus(this.response, 200);
  assert.ok(this.body.id, 'Event type id should be present');
  assert.ok(this.body.label, 'Event type label should be present');
  assertToken(this);
});

// ----------- POST -----------
When('I create an event type with label {string} and color_code {string}', async function (label, color_code) {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/agenda/events-types', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify({ label, color_code })
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a confirmation of event type creation', function () {
  assertStatus(this.response, 201);
  if (this.body.id) assert.ok(this.body.id, 'Created event type should have an id');
  assertToken(this);
});

// ----------- PUT -----------
When('I update event type with id {int} to label {string} and color_code {string}', async function (id, label, color_code) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/agenda/events-types/${id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify({ label, color_code })
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a confirmation of event type update', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});

// ----------- DELETE -----------
When('I delete event type with id {int}', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/agenda/events-types/${id}`, {
    method: 'DELETE',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a confirmation of event type deletion', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});

// =============================
// EVENTS API STEPS
// =============================
  
// ----------- GET -----------
When('I fetch the events page {int}', async function (page) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/agenda/events?page=${page}`, {
    headers: { Authorization: `Bearer ${this.token}` }
  });
  this.response = res;
  this.body = await safeJson(res);
});


Then('I receive a list of events', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  if (this.body.length > 0) {
    const item = this.body[0];
    assert.ok(item.title, 'title should be present');
    assert.ok(item.date, 'date should be present');
    assert.ok(item.type_id, 'type_id should be present');
  }
  assertToken(this);
});

Then('I receive a birthday detail', function () {
  assertStatus(this.response, 200);
  assert.ok(this.body.id, 'Birthday id should be present');
  assert.ok(this.body.first_name, 'first_name should be present');
  assert.ok(this.body.birthdate, 'birthdate should be present');
  assertToken(this);
});

When('I fetch the event with id {int}', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/agenda/events/${id}`, {
    headers: { Authorization: `Bearer ${this.token}` }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive an event detail', function () {
  assertStatus(this.response, 200);
  assert.ok(this.body.id, 'Event id should be present');
  assert.ok(this.body.title, 'title should be present');
  assert.ok(this.body.date, 'date should be present');
  assert.ok(this.body.type_id, 'type_id should be present');
  assertToken(this);
});
// ----------- POST -----------
When('I create an event with title {string}, date {string}, type_id {string}', async function (title, date, type_id) {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/agenda/events', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify({ title, date, type_id })
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a confirmation of event creation', function () {
  assertStatus(this.response, 201);
  if (this.body.id) assert.ok(this.body.id, 'Created event should have an id');
  assertToken(this);
});

// ----------- PUT -----------
When('I update event with id {int} to title {string}, date {string}, type_id {string}', async function (id, title, date, type_id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/agenda/events/${id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify({ title, date, type_id })
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a confirmation of event update', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});

// ----------- DELETE -----------
When('I delete event with id {int}', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/agenda/events/${id}`, {
    method: 'DELETE',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await safeJson(res);
});


// ----------- DELETE -----------
Then('I receive a confirmation of deletion', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});

// ----------- ERROR -----------
Then('I receive a not found error for event', function () {
  assertStatus(this.response, 404);
  assert.ok(this.body.errors?.not_found, 'Error not_found should be present');
  assert.ok(Array.isArray(this.body.errors.not_found), 'not_found should be an array');
  assert.ok(this.body.errors.not_found[0].includes('not found'), 'Error message should mention not found');
  assert.strictEqual(this.body.error_data.not_found.status, 404, 'Error status should be 404');
  assertToken(this);
});

// =============================
// BIRTHDAYS API STEPS
// =============================

// ----------- GET -----------

When('I fetch the birthdays page {int}', async function (page) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/agenda/birthday?page=${page}`, {
    headers: { Authorization: `Bearer ${this.token}` }
  });
  this.response = res;
  this.body = await safeJson(res);
});


// ----------- GET BY ID -----------
When('I fetch the birthday with id {int}', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/agenda/birthday/${id}`, {
    headers: { Authorization: `Bearer ${this.token}` }
  });
  this.response = res;
  this.body = await safeJson(res);
});

// ----------- POST -----------

When('I create a birthday with first name {string}, birthdate {string}, email {string}', async function (firstName, birthdate, email) {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/agenda/birthday', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify({ first_name: firstName, birthdate, email })
  });
  this.response = res;
  this.body = await safeJson(res);
});


Then('I receive a confirmation of creation', function () {
  assertStatus(this.response, 201);
  if (this.body.id) assert.ok(this.body.id, 'Created birthday should have an id');
  assertToken(this);
});

// ----------- PUT -----------

When('I update birthday with id {int} to first name {string}, birthdate {string}, email {string}', async function (id, firstName, birthdate, email) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/agenda/birthday/${id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify({ first_name: firstName, birthdate, email })
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a confirmation of update', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});


// ----------- DELETE -----------
When('I delete birthday with id {int}', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/agenda/birthday/${id}`, {
    method: 'DELETE',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a list of birthdays', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  if (this.body.length > 0) {
    const item = this.body[0];
    assert.ok(item.first_name, 'first_name should be present');
    assert.ok(item.birthdate, 'birthdate should be present');
  }
  assertToken(this);
});


// ----------- ERROR -----------

Then('I receive an error response', function () {
  assert.notStrictEqual(this.response.status, 400);
  assert.ok(this.body.message, 'Error message should be present');
  assertToken(this);
});

Then('I receive a not found error', function () {
  assert.strictEqual(this.response.status, 404);
  assert.ok(this.body.errors && this.body.errors.not_found, 'Error not_found should be present');
  assert.ok(Array.isArray(this.body.errors.not_found), 'not_found should be an array');
  assert.ok(this.body.errors.not_found[0].includes('not found'), 'Error message should mention not found');
  assert.strictEqual(this.body.error_data.not_found.status, 404, 'Error status should be 404');
  assertToken(this);
});