const { When, Then } = require('@cucumber/cucumber');
const { apiGet, apiPost, apiPut, apiDelete } = require('../support/httpHelpers');
const { assertStatus, assertArray, assertToken, assertField, assertNotFoundError } = require('../support/assertHelpers');

// =============================
// EVENTS TYPES API STEPS
// =============================

When('I fetch the event types', async function () {
  await apiGet(this, '/agenda/events-types/');
});

When('I fetch the event type by the {int}', async function (id) {
  await apiGet(this, `/agenda/events-types/${id}`);
});

Then('I receive a list of event types', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  if (this.body.length > 0) {
    assertField(this.body[0], 'id');
    assertField(this.body[0], 'label');
  }
  assertToken(this);
});

Then('I receive an event type detail', function () {
  assertStatus(this.response, 200);
  assertField(this.body, 'id');
  assertField(this.body, 'label');
  assertToken(this);
});

When('I create an event type with label {string} and color_code {string}', async function (label, color_code) {
  await apiPost(this, '/agenda/events-types', { label, color_code });
});

Then('I receive a confirmation of event type creation', function () {
  assertStatus(this.response, 201);
  assertToken(this);
});

When('I update event type with id {int} to label {string} and color_code {string}', async function (id, label, color_code) {
  await apiPut(this, `/agenda/events-types/${id}`, { label, color_code });
});

Then('I receive a confirmation of event type update', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});

When('I delete event type with id {int}', async function (id) {
  await apiDelete(this, `/agenda/events-types/${id}`);
});

Then('I receive a confirmation of event type deletion', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});

// =============================
// EVENTS API STEPS
// =============================

When('I fetch the events page {int}', async function (page) {
  await apiGet(this, `/agenda/events?page=${page}`);
});

Then('I receive a list of events', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  if (this.body.length > 0) {
    assertField(this.body[0], 'title');
    assertField(this.body[0], 'date');
    assertField(this.body[0], 'type_id');
  }
  assertToken(this);
});

When('I fetch the event with id {int}', async function (id) {
  await apiGet(this, `/agenda/events/${id}`);
});

Then('I receive an event detail', function () {
  assertStatus(this.response, 200);
  assertField(this.body, 'id');
  assertField(this.body, 'title');
  assertField(this.body, 'date');
  assertField(this.body, 'type_id');
  assertToken(this);
});

When('I create an event with title {string}, date {string}, type_id {string}', async function (title, date, type_id) {
  await apiPost(this, '/agenda/events', { title, date, type_id });
});

Then('I receive a confirmation of event creation', function () {
  assertStatus(this.response, 201);
  assertToken(this);
});

When('I update event with id {int} to title {string}, date {string}, type_id {string}', async function (id, title, date, type_id) {
  await apiPut(this, `/agenda/events/${id}`, { title, date, type_id });
});

Then('I receive a confirmation of event update', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});

When('I delete event with id {int}', async function (id) {
  await apiDelete(this, `/agenda/events/${id}`);
});

Then('I receive a confirmation of deletion', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});

Then('I receive a not found error for event', function () {
  assertStatus(this.response, 404);
  assertNotFoundError(this.body);
  assertToken(this);
});

// =============================
// BIRTHDAYS API STEPS
// =============================

When('I fetch the birthdays page {int}', async function (page) {
  await apiGet(this, `/agenda/birthday?page=${page}`);
});

Then('I receive a list of birthdays', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  if (this.body.length > 0) {
    assertField(this.body[0], 'first_name');
    assertField(this.body[0], 'birthdate');
  }
  assertToken(this);
});

When('I fetch the birthday with id {int}', async function (id) {
  await apiGet(this, `/agenda/birthday/${id}`);
});

Then('I receive a birthday detail', function () {
  assertStatus(this.response, 200);
  assertField(this.body, 'id');
  assertField(this.body, 'first_name');
  assertField(this.body, 'birthdate');
  assertToken(this);
});

When('I create a birthday with first name {string}, birthdate {string}, email {string}', async function (firstName, birthdate, email) {
  await apiPost(this, '/agenda/birthday', { first_name: firstName, birthdate, email });
});

Then('I receive a confirmation of creation', function () {
  assertStatus(this.response, 201);
  assertToken(this);
});

When('I update birthday with id {int} to first name {string}, birthdate {string}, email {string}', async function (id, firstName, birthdate, email) {
  await apiPut(this, `/agenda/birthday/${id}`, { first_name: firstName, birthdate, email });
});

Then('I receive a confirmation of update', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});

When('I delete birthday with id {int}', async function (id) {
  await apiDelete(this, `/agenda/birthday/${id}`);
});

Then('I receive an error response', function () {
  assertField(this.body, 'message');
  assertToken(this);
});

Then('I receive a not found error', function () {
  assertStatus(this.response, 404);
  assertNotFoundError(this.body);
  assertToken(this);
});
