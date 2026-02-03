import { When, Then } from '@cucumber/cucumber';
import { apiGet, apiPost, apiPut, apiDelete } from '../../support/httpHelpers.js';
import { assertToken, assertField } from '../../support/assertHelpers.js';
import { assertListResponse, assertDetailResponse, assertCreated, assertNoContent, assertNotFound } from '../../support/assertService.js';

const ENTITY_FIELDS = {
  'event types': ['id', 'label'],
  'event type': ['id', 'label'],
  'events': ['title', 'date', 'type_id'],
  'event': ['id', 'title', 'date', 'type_id'],
  'birthdays': ['first_name', 'birthdate'],
  'birthday': ['id', 'first_name', 'birthdate']
};

// =============================
// GENERIC THEN STEPS
// =============================

Then(/^I receive a list of (event types|events|birthdays)$/, function (entity) {
  assertListResponse(this, ENTITY_FIELDS[entity]);
});

Then(/^I receive an? (event type|event|birthday) detail$/, function (entity) {
  assertDetailResponse(this, ENTITY_FIELDS[entity]);
});

Then('I receive a confirmation of event type creation', function () {
  assertCreated(this);
});

Then('I receive a confirmation of event type update', function () {
  assertNoContent(this);
});

Then('I receive a confirmation of event type deletion', function () {
  assertNoContent(this);
});

Then('I receive a confirmation of event creation', function () {
  assertCreated(this);
});

Then('I receive a confirmation of event update', function () {
  assertNoContent(this);
});

Then('I receive a confirmation of deletion', function () {
  assertNoContent(this);
});

Then('I receive a confirmation of creation', function () {
  assertCreated(this);
});

Then('I receive a confirmation of update', function () {
  assertNoContent(this);
});

Then('I receive a not found error for event', function () {
  assertNotFound(this);
});

Then('I receive a not found error', function () {
  assertNotFound(this);
});

Then('I receive an error response', function () {
  assertField(this.body, 'message');
  assertToken(this);
});

// =============================
// EVENTS TYPES API STEPS
// =============================

When('I fetch the event types', async function () {
  await apiGet(this, '/agenda/events-types/');
});

When('I fetch the event type by the {int}', async function (id) {
  await apiGet(this, `/agenda/events-types/${id}`);
});

When('I create an event type with label {string} and color_code {string}', async function (label, color_code) {
  await apiPost(this, '/agenda/events-types', { label, color_code });
});

When('I update event type with id {int} to label {string} and color_code {string}', async function (id, label, color_code) {
  await apiPut(this, `/agenda/events-types/${id}`, { label, color_code });
});

When('I delete event type with id {int}', async function (id) {
  await apiDelete(this, `/agenda/events-types/${id}`);
});

// =============================
// EVENTS API STEPS
// =============================

When('I fetch the events page {int}', async function (page) {
  await apiGet(this, `/agenda/events?page=${page}`);
});

When('I fetch the event with id {int}', async function (id) {
  await apiGet(this, `/agenda/events/${id}`);
});

When('I create an event with title {string}, date {string}, type_id {string}', async function (title, date, type_id) {
  await apiPost(this, '/agenda/events', { title, date, type_id });
});

When('I update event with id {int} to title {string}, date {string}, type_id {string}', async function (id, title, date, type_id) {
  await apiPut(this, `/agenda/events/${id}`, { title, date, type_id });
});

When('I delete event with id {int}', async function (id) {
  await apiDelete(this, `/agenda/events/${id}`);
});

// =============================
// BIRTHDAYS API STEPS
// =============================

When('I fetch the birthdays page {int}', async function (page) {
  await apiGet(this, `/agenda/birthday?page=${page}`);
});

When('I fetch the birthday with id {int}', async function (id) {
  await apiGet(this, `/agenda/birthday/${id}`);
});

When('I create a birthday with first name {string}, birthdate {string}, email {string}', async function (firstName, birthdate, email) {
  await apiPost(this, '/agenda/birthday', { first_name: firstName, birthdate, email });
});

When('I update birthday with id {int} to first name {string}, birthdate {string}, email {string}', async function (id, firstName, birthdate, email) {
  await apiPut(this, `/agenda/birthday/${id}`, { first_name: firstName, birthdate, email });
});

When('I delete birthday with id {int}', async function (id) {
  await apiDelete(this, `/agenda/birthday/${id}`);
});
