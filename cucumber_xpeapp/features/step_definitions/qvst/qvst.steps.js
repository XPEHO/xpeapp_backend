const sinon = require('sinon');
const { When, Then, Before, After } = require('@cucumber/cucumber');
const assert = require('node:assert');
const fetch = require('node-fetch');
const { safeJson } = require('../../support/safeJson');
const { assertStatus, assertArray, assertToken, assertField, assertFields, assertHasOwn, assertHasOwnFields } = require('../support/assertHelpers');

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



function assertQvstQuestionFields(q) {
  assertHasOwnFields(q, ['question_id', 'question', 'theme', 'theme_id', 'answer_repo_id', 'numberAsked', 'reversed_question', 'no_longer_used']);
  assertArray(q.answers, 'answers should be an array');
  for (const a of q.answers) {
    assertHasOwnFields(a, ['id', 'answer', 'value']);
  }
}


function assertQvstCampaignFields(c) {
  assertHasOwnFields(c, ['id', 'name', 'start_date', 'end_date', 'action', 'participation_rate']);
  assert.ok(['OPEN', 'DRAFT', 'CLOSED', 'ARCHIVED'].includes(c.status), 'status should be valid');
  assertArray(c.themes, 'themes should be an array');
  for (const t of c.themes) {
    assertHasOwnFields(t, ['id', 'name']);
  }
}

// ----------- GET QVST QUESTIONS ONLY ACTIVE -----------
When('I fetch the QVST questions', async function () {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/qvst/', {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a list of only active QVST questions', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  for (const q of this.body) {
    assertQvstQuestionFields(q);
    assert.strictEqual(q.no_longer_used, false, 'All questions should be active');
  }
  assertToken(this);
});

// ----------- GET QVST QUESTIONS (include no longer used) -----------
When('I fetch the QVST questions with no longer used included', async function () {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/qvst/?include_no_longer_used=true', {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await res.json();
});

Then('I receive a list of all QVST questions including no longer used', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  assert.ok(this.body.some(q => q.no_longer_used === true), 'At least one question should be no longer used');
  for (const q of this.body) {
    assertQvstQuestionFields(q);
  }
  assertToken(this);
});

// ----------- GET QVST ACTIVE CAMPAIGNS -----------
When('I fetch the active QVST campaigns', async function () {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/qvst/campaigns:active', {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await res.json();
});

Then('I receive a list of active QVST campaigns', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  for (const c of this.body) {
    assertQvstCampaignFields(c);
    assert.strictEqual(c.status, 'OPEN', 'Only active campaigns (OPEN) should be present');
  }
  assertToken(this);
});

// ----------- GET QVST ALL CAMPAIGNS -----------
When('I fetch all QVST campaigns', async function () {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/qvst/campaigns', {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await res.json();
});

Then('I receive a list of all QVST campaigns', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  for (const c of this.body) {
    assertQvstCampaignFields(c);
  }
  assertToken(this);
});

// ----------- POST QVST CAMPAIGN STATUS (with notification stub) -----------
When('I update the status of QVST campaign {int} to {string}', { tags: '@mockNotification' }, async function (id, status) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/qvst/campaigns/${id}/status:update`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify({ status })
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a confirmation of QVST campaign status update', function () {
  assertStatus(this.response, 201);
  assert.deepStrictEqual(this.body, {}, 'Response body should be empty');
  assertToken(this);
});

// ----------- GET QVST CAMPAIGN STATS -----------
When('I fetch the stats for QVST campaign {int}', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/qvst/campaigns/${id}:stats`, {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive the QVST campaign stats with all expected fields', function () {
  const c = this.body;
  assertHasOwnFields(c, ['campaignId', 'campaignName', 'campaignStatus', 'startDate', 'endDate', 'action']);
  assertArray(c.themes, 'themes should be an array');
  for (const t of c.themes) {
    assertHasOwnFields(t, ['id', 'name']);
  }
  assertArray(c.questions, 'questions should be an array');
  for (const q of c.questions) {
    assertHasOwnFields(q, ['question_id', 'question', 'answer_repo_id', 'reversed_question', 'no_longer_used', 'status', 'action']);
    assertArray(q.answers, 'answers should be an array');
    for (const a of q.answers) {
      assertHasOwnFields(a, ['id', 'answer', 'value', 'numberAnswered']);
    }
  }
  assertToken(this);
});

// ----------- GET QVST CAMPAIGN QUESTIONS -----------
When('I fetch the questions for QVST campaign {int}', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/qvst/campaigns/${id}:questions`, {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive the QVST campaign questions with all expected fields', function () {
  assertArray(this.body);
  for (const q of this.body) {
    assertHasOwnFields(q, ['question_id', 'question']);
    assertArray(q.answers, 'answers should be an array');
    for (const a of q.answers) {
      assertHasOwnFields(a, ['id', 'answer', 'value']);
    }
  }
  assertToken(this);
});

// ----------- GET ALL ANSWER REPOSITORIES -----------
When('I fetch all QVST answer repositories', async function () {
  const res = await fetch('http:localhost:7830/wp-json/xpeho/v1/qvst/answers_repo', {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a list of all QVST answer repositories with expected fields', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  for (const ar of this.body) {
    assertHasOwnFields(ar, ['id', 'repoName']);
    assertArray(ar.answers, 'answers should be an array');
    for (const a of ar.answers) {
      assertHasOwnFields(a, ['id', 'answer', 'value']);
    }
  }
  assertToken(this);
});

// ----------- GET ALL QUESTIONS BY THEME -----------
When('I fetch all QVST questions for theme {int}', async function (themeId) {
  this.themeId = themeId;
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/qvst/themes/${themeId}/questions`, {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a list of all QVST questions for the theme with expected fields', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  const expectedThemeId = Number(this.themeId);
  for (const q of this.body) {
    assertQvstQuestionFields(q);
    assert.strictEqual(Number(q.theme_id), expectedThemeId, 'All questions should belong to the requested theme');
  }
  assertToken(this);
});

// ----------- GET ALL THEMES -----------

When('I fetch all QVST themes', async function () {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/qvst/themes', {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('I receive a list of all QVST themes with expected fields', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  for (const t of this.body) {
    assertHasOwnFields(t, ['id', 'name']);
  }
  assertToken(this);
});

// ----------- POST QVST Campaign -----------

When('I add a new QVST campaign with body:', async function (docString) {
  const body = JSON.parse(docString);
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/qvst/campaigns:add', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify(body)
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('the QVST campaign is successfully created', function () {
  assertStatus(this.response, 201);
});

// ----------- GET QVST CAMPAIGN ANALYSIS -----------
When('I fetch the QVST campaign analysis for id {int}', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/qvst/campaigns/${id}:analysis`, {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('the QVST campaign analysis contains all main stats', function () {
  assertStatus(this.response, 200);
  const body = this.body;
  const mainKeys = [
    'campaign_id',
    'campaign_name',
    'campaign_status',
    'start_date',
    'end_date',
    'themes',
    'global_stats',
    'global_distribution',
    'questions_analysis',
    'questions_requiring_action',
    'at_risk_employees'
  ];
  for (const key of mainKeys) {
    assert.ok(body.hasOwnProperty(key), `La propriété '${key}' doit être présente dans la réponse`);
  }
  assert.ok(Array.isArray(body.themes), 'themes doit être un tableau');
  assert.ok(typeof body.global_stats === 'object', 'global_stats doit être un objet');
  assert.ok(Array.isArray(body.global_distribution), 'global_distribution doit être un tableau');
  assert.ok(Array.isArray(body.questions_analysis), 'questions_analysis doit être un tableau');
  assert.ok(Array.isArray(body.questions_requiring_action), 'questions_requiring_action doit être un tableau');
  assert.ok(Array.isArray(body.at_risk_employees), 'at_risk_employees doit être un tableau');
});

// ----------- DELETE QVST QUESTION -----------
When('I delete the QVST question with id {int}', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/qvst/${id}:delete`, {
    method: 'DELETE',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('the QVST question is successfully deleted', function () {
  assertStatus(this.response, 204);
}); 

// ----------- POST QVST QUESTION -----------
When('I add a QVST question with body:', async function (docString) {
  const body = JSON.parse(docString);
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/qvst:add', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify(body)
  });
  this.response = res;
  this.body = await safeJson(res);
});

Then('the QVST question is successfully created', function () {
  assertStatus(this.response, 201);
});