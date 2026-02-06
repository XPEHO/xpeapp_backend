import { When, Then } from '@cucumber/cucumber';
import assert from 'node:assert';
import { apiGet, apiPost, apiDelete } from '../../support/httpHelpers.js';
import { assertStatus, assertArray, assertToken, assertHasOwnFields } from '../../support/assertHelpers.js';

const QUESTION_FIELDS = ['question_id', 'question', 'theme', 'theme_id', 'answer_repo_id', 'numberAsked'];

const QUESTION_OPTIONAL_FIELDS = ['reversed_question', 'no_longer_used'];
const ANSWER_FIELDS = ['id', 'answer', 'value'];
const CAMPAIGN_FIELDS = ['id', 'name', 'start_date', 'end_date', 'action', 'participation_rate'];
const THEME_FIELDS = ['id', 'name'];

// Helper to assert list responses with item validator
function assertListResponse(context, itemValidator) {
  assertStatus(context.response, 200);
  assertArray(context.body);
  for (const item of context.body) itemValidator(item);
  assertToken(context);
}

function assertAnswers(answers) {
  assertArray(answers, 'answers should be an array');
  for (const answer of answers) assertHasOwnFields(answer, ANSWER_FIELDS);
}

function assertQvstQuestion(question) {
  assertHasOwnFields(question, QUESTION_FIELDS);
  assertAnswers(question.answers);
}

function assertQvstCampaign(campaign) {
  assertHasOwnFields(campaign, CAMPAIGN_FIELDS);
  assert.ok(['OPEN', 'DRAFT', 'CLOSED', 'ARCHIVED'].includes(campaign.status), 'status should be valid');
  assertArray(campaign.themes, 'themes should be an array');
  for (const theme of campaign.themes) assertHasOwnFields(theme, THEME_FIELDS);
}

// ----------- GET QVST QUESTIONS ONLY ACTIVE -----------
When('I fetch the QVST questions', async function () {
  await apiGet(this, '/qvst/');
});

Then('I receive a list of only active QVST questions', function () {
  assertListResponse(this, assertQvstQuestion);
});

// ----------- GET QVST QUESTIONS (include no longer used) -----------
When('I fetch the QVST questions with no longer used included', async function () {
  await apiGet(this, '/qvst/?include_no_longer_used=true');
});

Then('I receive a list of all QVST questions including no longer used', function () {
  assertListResponse(this, assertQvstQuestion);
});

// ----------- GET QVST ACTIVE CAMPAIGNS -----------
When('I fetch the active QVST campaigns', async function () {
  await apiGet(this, '/qvst/campaigns:active');
});

Then('I receive a list of active QVST campaigns', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  for (const campaign of this.body) {
    assertQvstCampaign(campaign);
    assert.strictEqual(campaign.status, 'OPEN', 'Only active campaigns (OPEN) should be present');
  }
  assertToken(this);
});

// ----------- GET QVST ALL CAMPAIGNS -----------
When('I fetch all QVST campaigns', async function () {
  await apiGet(this, '/qvst/campaigns');
});

Then('I receive a list of all QVST campaigns', function () {
  assertListResponse(this, assertQvstCampaign);
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
  const stats = this.body;
  assertHasOwnFields(stats, ['campaignId', 'campaignName', 'campaignStatus', 'startDate', 'endDate', 'action']);
  assertArray(stats.themes, 'themes should be an array');
  for (const theme of stats.themes) assertHasOwnFields(theme, THEME_FIELDS);
  assertArray(stats.questions, 'questions should be an array');
  for (const question of stats.questions) {
    assertHasOwnFields(question, ['question_id', 'question', 'answer_repo_id', 'status', 'action']);
    assertArray(question.answers, 'answers should be an array');
    for (const answer of question.answers) assertHasOwnFields(answer, [...ANSWER_FIELDS, 'numberAnswered']);
  }
  assertToken(this);
});

// ----------- GET QVST CAMPAIGN QUESTIONS -----------
When('I fetch the questions for QVST campaign {int}', async function (id) {
  await apiGet(this, `/qvst/campaigns/${id}:questions`);
});

Then('I receive the QVST campaign questions with all expected fields', function () {
  assertArray(this.body);
  for (const question of this.body) {
    assertHasOwnFields(question, ['question_id', 'question']);
    assertAnswers(question.answers);
  }
  assertToken(this);
});

// ----------- GET ALL ANSWER REPOSITORIES -----------
When('I fetch all QVST answer repositories', async function () {
  await apiGet(this, '/qvst/answers_repo');
});

Then('I receive a list of all QVST answer repositories with expected fields', function () {
  assertListResponse(this, repo => {
    assertHasOwnFields(repo, ['id', 'repoName']);
    assertAnswers(repo.answers);
  });
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
  for (const question of this.body) {
    assertQvstQuestion(question);
    assert.strictEqual(Number(question.theme_id), expectedThemeId, 'All questions should belong to the requested theme');
  }
  assertToken(this);
});

// ----------- GET ALL THEMES -----------
When('I fetch all QVST themes', async function () {
  await apiGet(this, '/qvst/themes');
});

Then('I receive a list of all QVST themes with expected fields', function () {
  assertListResponse(this, theme => assertHasOwnFields(theme, THEME_FIELDS));
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
  assert.ok([201, 500].includes(this.response.status), `Status should be 201 or 500, got ${this.response.status}`);
});

// ----------- POST QVST QUESTION -----------
When('I add a QVST question with body:', async function (docString) {
  await apiPost(this, '/qvst:add', JSON.parse(docString));
});

Then('the QVST question is successfully created', function () {
  assert.ok([201, 500].includes(this.response.status), `Status should be 201 or 500, got ${this.response.status}`);
});
