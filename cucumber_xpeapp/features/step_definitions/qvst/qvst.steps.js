const sinon = require('sinon');
const { When, Then, Before, After } = require('@cucumber/cucumber');
const assert = require('node:assert');
const { apiGet, apiPost, apiDelete } = require('../support/httpHelpers');
const { assertStatus, assertArray, assertToken, assertHasOwnFields } = require('../support/assertHelpers');

let fetchStub;

Before({ tags: '@mockNotification' }, function () {
  if (!globalThis.fetch?.isSinonProxy) {
    fetchStub = sinon.stub(globalThis, 'fetch').resolves({
      status: 201,
      json: async () => ({ success: true })
    });
  }
});

After({ tags: '@mockNotification' }, function () {
  if (fetchStub) fetchStub.restore();
});

const QUESTION_FIELDS = ['question_id', 'question', 'theme', 'theme_id', 'answer_repo_id', 'numberAsked', 'reversed_question', 'no_longer_used'];
const ANSWER_FIELDS = ['id', 'answer', 'value'];
const CAMPAIGN_FIELDS = ['id', 'name', 'start_date', 'end_date', 'action', 'participation_rate'];
const THEME_FIELDS = ['id', 'name'];

function assertAnswers(answers) {
  assertArray(answers, 'answers should be an array');
  for (const a of answers) assertHasOwnFields(a, ANSWER_FIELDS);
}

function assertQvstQuestion(q) {
  assertHasOwnFields(q, QUESTION_FIELDS);
  assertAnswers(q.answers);
}

function assertQvstCampaign(c) {
  assertHasOwnFields(c, CAMPAIGN_FIELDS);
  assert.ok(['OPEN', 'DRAFT', 'CLOSED', 'ARCHIVED'].includes(c.status), 'status should be valid');
  assertArray(c.themes, 'themes should be an array');
  for (const t of c.themes) assertHasOwnFields(t, THEME_FIELDS);
}

// ----------- GET QVST QUESTIONS ONLY ACTIVE -----------
When('I fetch the QVST questions', async function () {
  await apiGet(this, '/qvst/');
});

Then('I receive a list of only active QVST questions', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  for (const q of this.body) {
    assertQvstQuestion(q);
    assert.strictEqual(q.no_longer_used, false, 'All questions should be active');
  }
  assertToken(this);
});

// ----------- GET QVST QUESTIONS (include no longer used) -----------
When('I fetch the QVST questions with no longer used included', async function () {
  await apiGet(this, '/qvst/?include_no_longer_used=true');
});

Then('I receive a list of all QVST questions including no longer used', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  assert.ok(this.body.some(q => q.no_longer_used === true), 'At least one question should be no longer used');
  for (const q of this.body) assertQvstQuestion(q);
  assertToken(this);
});

// ----------- GET QVST ACTIVE CAMPAIGNS -----------
When('I fetch the active QVST campaigns', async function () {
  await apiGet(this, '/qvst/campaigns:active');
});

Then('I receive a list of active QVST campaigns', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  for (const c of this.body) {
    assertQvstCampaign(c);
    assert.strictEqual(c.status, 'OPEN', 'Only active campaigns (OPEN) should be present');
  }
  assertToken(this);
});

// ----------- GET QVST ALL CAMPAIGNS -----------
When('I fetch all QVST campaigns', async function () {
  await apiGet(this, '/qvst/campaigns');
});

Then('I receive a list of all QVST campaigns', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  for (const c of this.body) assertQvstCampaign(c);
  assertToken(this);
});

// ----------- POST QVST CAMPAIGN STATUS (with notification stub) -----------
When('I update the status of QVST campaign {int} to {string}', { tags: '@mockNotification' }, async function (id, status) {
  await apiPost(this, `/qvst/campaigns/${id}/status:update`, { status });
});

Then('I receive a confirmation of QVST campaign status update', function () {
  assertStatus(this.response, 201);
  assert.deepStrictEqual(this.body, {}, 'Response body should be empty');
  assertToken(this);
});

// ----------- GET QVST CAMPAIGN STATS -----------
When('I fetch the stats for QVST campaign {int}', async function (id) {
  await apiGet(this, `/qvst/campaigns/${id}:stats`);
});

Then('I receive the QVST campaign stats with all expected fields', function () {
  const c = this.body;
  assertHasOwnFields(c, ['campaignId', 'campaignName', 'campaignStatus', 'startDate', 'endDate', 'action']);
  assertArray(c.themes, 'themes should be an array');
  for (const t of c.themes) assertHasOwnFields(t, THEME_FIELDS);
  assertArray(c.questions, 'questions should be an array');
  for (const q of c.questions) {
    assertHasOwnFields(q, ['question_id', 'question', 'answer_repo_id', 'reversed_question', 'no_longer_used', 'status', 'action']);
    assertArray(q.answers, 'answers should be an array');
    for (const a of q.answers) assertHasOwnFields(a, [...ANSWER_FIELDS, 'numberAnswered']);
  }
  assertToken(this);
});

// ----------- GET QVST CAMPAIGN QUESTIONS -----------
When('I fetch the questions for QVST campaign {int}', async function (id) {
  await apiGet(this, `/qvst/campaigns/${id}:questions`);
});

Then('I receive the QVST campaign questions with all expected fields', function () {
  assertArray(this.body);
  for (const q of this.body) {
    assertHasOwnFields(q, ['question_id', 'question']);
    assertAnswers(q.answers);
  }
  assertToken(this);
});

// ----------- GET ALL ANSWER REPOSITORIES -----------
When('I fetch all QVST answer repositories', async function () {
  await apiGet(this, '/qvst/answers_repo');
});

Then('I receive a list of all QVST answer repositories with expected fields', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  for (const ar of this.body) {
    assertHasOwnFields(ar, ['id', 'repoName']);
    assertAnswers(ar.answers);
  }
  assertToken(this);
});

// ----------- GET ALL QUESTIONS BY THEME -----------
When('I fetch all QVST questions for theme {int}', async function (themeId) {
  this.themeId = themeId;
  await apiGet(this, `/qvst/themes/${themeId}/questions`);
});

Then('I receive a list of all QVST questions for the theme with expected fields', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  const expectedThemeId = Number(this.themeId);
  for (const q of this.body) {
    assertQvstQuestion(q);
    assert.strictEqual(Number(q.theme_id), expectedThemeId, 'All questions should belong to the requested theme');
  }
  assertToken(this);
});

// ----------- GET ALL THEMES -----------
When('I fetch all QVST themes', async function () {
  await apiGet(this, '/qvst/themes');
});

Then('I receive a list of all QVST themes with expected fields', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  for (const t of this.body) assertHasOwnFields(t, THEME_FIELDS);
  assertToken(this);
});

// ----------- POST QVST Campaign -----------
When('I add a new QVST campaign with body:', async function (docString) {
  await apiPost(this, '/qvst/campaigns:add', JSON.parse(docString));
});

Then('the QVST campaign is successfully created', function () {
  assertStatus(this.response, 201);
});

// ----------- GET QVST CAMPAIGN ANALYSIS -----------
When('I fetch the QVST campaign analysis for id {int}', async function (id) {
  await apiGet(this, `/qvst/campaigns/${id}:analysis`);
});

Then('the QVST campaign analysis contains all main stats', function () {
  assertStatus(this.response, 200);
  const keys = ['campaign_id', 'campaign_name', 'campaign_status', 'start_date', 'end_date', 'themes', 'global_stats', 'global_distribution', 'questions_analysis', 'questions_requiring_action', 'at_risk_employees'];
  assertHasOwnFields(this.body, keys);
  assertArray(this.body.themes, 'themes doit etre un tableau');
  assert.ok(typeof this.body.global_stats === 'object', 'global_stats doit etre un objet');
  assertArray(this.body.global_distribution, 'global_distribution doit etre un tableau');
  assertArray(this.body.questions_analysis, 'questions_analysis doit etre un tableau');
  assertArray(this.body.questions_requiring_action, 'questions_requiring_action doit etre un tableau');
  assertArray(this.body.at_risk_employees, 'at_risk_employees doit etre un tableau');
});

// ----------- DELETE QVST QUESTION -----------
When('I delete the QVST question with id {int}', async function (id) {
  await apiDelete(this, `/qvst/${id}:delete`);
});

Then('the QVST question is successfully deleted', function () {
  assertStatus(this.response, 204);
});

// ----------- POST QVST QUESTION -----------
When('I add a QVST question with body:', async function (docString) {
  await apiPost(this, '/qvst:add', JSON.parse(docString));
});

Then('the QVST question is successfully created', function () {
  assertStatus(this.response, 201);
});
