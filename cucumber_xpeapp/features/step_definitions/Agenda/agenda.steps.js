const { When, Then } = require('@cucumber/cucumber');
const { apiGet, apiPost, apiPut, apiDelete } = require('../support/httpHelpers');
const { assertStatus, assertArray, assertToken, assertField, assertNotFoundError } = require('../support/assertHelpers');

// =============================
// GENERIC RESPONSE ASSERTIONS
// =============================

function assertListResponse(context, fields) {
  assertStatus(context.response, 200);
  assertArray(context.body);
  if (context.body.length > 0) for (const f of fields) assertField(context.body[0], f);
  assertToken(context);
}

function assertDetailResponse(context, fields) {
  assertStatus(context.response, 200);
  for (const f of fields) assertField(context.body, f);
  assertToken(context);
}

function assertCreated(context) {
  assertStatus(context.response, 201);
  assertToken(context);
}

function assertNoContent(context) {
  assertStatus(context.response, 204);
  assertToken(context);
}

function assertNotFound(context) {
  assertStatus(context.response, 404);
  assertNotFoundError(context.body);
  assertToken(context);
}

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
  assertListResponse(this, ['id', 'label']);
});

Then('I receive an event type detail', function () {
  assertDetailResponse(this, ['id', 'label']);
});

When('I create an event type with label {string} and color_code {string}', async function (label, color_code) {
  await apiPost(this, '/agenda/events-types', { label, color_code });
});

Then('I receive a confirmation of event type creation', function () {
  assertCreated(this);
});

When('I update event type with id {int} to label {string} and color_code {string}', async function (id, label, color_code) {
  await apiPut(this, `/agenda/events-types/${id}`, { label, color_code });
});

Then('I receive a confirmation of event type update', function () {
  assertNoContent(this);
});

When('I delete event type with id {int}', async function (id) {
  await apiDelete(this, `/agenda/events-types/${id}`);
});

Then('I receive a confirmation of event type deletion', function () {
  assertNoContent(this);
});

// =============================
// EVENTS API STEPS
// =============================

When('I fetch the events page {int}', async function (page) {
  await apiGet(this, `/agenda/events?page=${page}`);
});

Then('I receive a list of events', function () {
  assertListResponse(this, ['title', 'date', 'type_id']);
});

When('I fetch the event with id {int}', async function (id) {
  await apiGet(this, `/agenda/events/${id}`);
});

Then('I receive an event detail', function () {
  assertDetailResponse(this, ['id', 'title', 'date', 'type_id']);
});

When('I create an event with title {string}, date {string}, type_id {string}', async function (title, date, type_id) {
  await apiPost(this, '/agenda/events', { title, date, type_id });
});

Then('I receive a confirmation of event creation', function () {
  assertCreated(this);
});

When('I update event with id {int} to title {string}, date {string}, type_id {string}', async function (id, title, date, type_id) {
  await apiPut(this, `/agenda/events/${id}`, { title, date, type_id });
});

Then('I receive a confirmation of event update', function () {
  assertNoContent(this);
});

When('I delete event with id {int}', async function (id) {
  await apiDelete(this, `/agenda/events/${id}`);
});

Then('I receive a confirmation of deletion', function () {
  assertNoContent(this);
});

Then('I receive a not found error for event', function () {
  assertNotFound(this);
});

// =============================
// BIRTHDAYS API STEPS
// =============================

When('I fetch the birthdays page {int}', async function (page) {
  await apiGet(this, `/agenda/birthday?page=${page}`);
});

Then('I receive a list of birthdays', function () {
  assertListResponse(this, ['first_name', 'birthdate']);
});

When('I fetch the birthday with id {int}', async function (id) {
  await apiGet(this, `/agenda/birthday/${id}`);
});

Then('I receive a birthday detail', function () {
  assertDetailResponse(this, ['id', 'first_name', 'birthdate']);
});

When('I create a birthday with first name {string}, birthdate {string}, email {string}', async function (firstName, birthdate, email) {
  await apiPost(this, '/agenda/birthday', { first_name: firstName, birthdate, email });
});

Then('I receive a confirmation of creation', function () {
  assertCreated(this);
});

When('I update birthday with id {int} to first name {string}, birthdate {string}, email {string}', async function (id, firstName, birthdate, email) {
  await apiPut(this, `/agenda/birthday/${id}`, { first_name: firstName, birthdate, email });
});

Then('I receive a confirmation of update', function () {
  assertNoContent(this);
});

When('I delete birthday with id {int}', async function (id) {
  await apiDelete(this, `/agenda/birthday/${id}`);
});

Then('I receive an error response', function () {
  assertField(this.body, 'message');
  assertToken(this);
});

Then('I receive a not found error', function () {
  assertNotFound(this);
});
