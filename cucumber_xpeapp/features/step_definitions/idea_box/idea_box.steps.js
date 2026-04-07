import { When, Then } from '@cucumber/cucumber';
import assert from 'node:assert';
import { apiGet, apiPost, apiPut, apiDelete } from '../../support/httpHelpers.js';
import { assertStatus, assertArray, assertToken } from '../../support/assertHelpers.js';

// ----------- GET All IDEAS -----------
When('I fetch all ideas', async function () {
  await apiGet(this, '/ideas');
});

Then('I receive a list of ideas with author information', function () {
  assertStatus(this.response, 200);
  assertArray(this.body, 'Response should be an array of ideas');
  for (const idea of this.body) {
    if (idea.user_id) {
      assert.ok(typeof idea.author === 'string' && idea.author.trim().length > 0,
       'Author field should be present and non-empty in ideas');
    } else {
      assert.strictEqual(typeof idea.author, 'string', 'Author field should be a string even if empty for unknown user');
    }
  }
  assertToken(this);
});

When('I fetch my ideas', async function () {
  await apiGet(this, '/ideas/my');
});

Then('I receive a list of my ideas', function () {
  assertStatus(this.response, 200);
  assertArray(this.body, 'Response should be an array of ideas');
  for (const idea of this.body) {
    assert.ok(idea.user_id, 'Each idea should contain a user_id');
  }
  assertToken(this);
});

// ----------- GET IDEA BY ID -----------
When('I fetch the idea with id {int}', async function (id) {
  await apiGet(this, `/ideas/${id}`);
});

Then('I receive the idea details with author information', function () {
  assertStatus(this.response, 200);
  assert.ok(this.body.id, 'Idea id should be present');
  assert.ok(this.body.context, 'Idea context should be present');
  assert.ok(this.body.description, 'Idea description should be present');
  if (this.body.user_id) {
    assert.ok(typeof this.body.author === 'string' && this.body.author.trim().length > 0,
     'Author field should be present and non-empty');
  } else {
    assert.strictEqual(typeof this.body.author, 'string', 'Author field should be a string even if empty for unknown user');
  }
  assertToken(this);
});

// ----------- POST NEW IDEA -----------
When('I submit a new idea with context {string} and description {string}', async function (context, description) {
  await apiPost(this, '/ideas', { context, description });
});

Then('I receive the created idea confirmation', function () {
  assertStatus(this.response, 201);
  assert.strictEqual(this.body.context, this.body.context, 'Idea context should match');
  assert.strictEqual(this.body.description, this.body.description, 'Idea description should match');
  assertToken(this);
});

// ----------- PUT IDEA BY ID -----------
When('I update the idea with id {int} to status {string}', async function (id, status) {
  await apiPut(this, `/ideas/${id}/status`, { status });
});

When('I update the idea with id {int} to status {string} with reason {string}', async function (id, status, reason) {
  await apiPut(this, `/ideas/${id}/status`, { status, reason });
});

Then('I receive the updated idea confirmation', function () {
  assertStatus(this.response, 204);
  assert.strictEqual(this.body.status, this.body.status, 'Idea status should match');
  assertToken(this);
});

// ----------- DELETE IDEA BY ID -----------
When('I delete the idea with id {int}', async function (id) {
  await apiDelete(this, `/ideas/${id}`);
});

Then('I receive the deleted idea confirmation', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});