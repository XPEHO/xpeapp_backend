const sinon = require('sinon');
const { When, Then, Before, After } = require('@cucumber/cucumber');
const assert = require('node:assert');
const fetch = require('node-fetch');
const { safeJson } = require('../../support/safeJson');

let fetchStub;


Before({ tags: '@mockNotification' }, function () {
  if (!globalThis.fetch || !globalThis.fetch.isSinonProxy) {
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
  assert.ok(q.hasOwnProperty('question_id'), 'question_id should be present');
  assert.ok(q.hasOwnProperty('question'), 'question should be present');
  assert.ok(q.hasOwnProperty('theme'), 'theme should be present');
  assert.ok(q.hasOwnProperty('theme_id'), 'theme_id should be present');
  assert.ok(q.hasOwnProperty('answer_repo_id'), 'answer_repo_id should be present');
  assert.ok(q.hasOwnProperty('numberAsked'), 'numberAsked should be present');
  assert.ok(q.hasOwnProperty('reversed_question'), 'reversed_question should be present');
  assert.ok(q.hasOwnProperty('no_longer_used'), 'no_longer_used should be present');
  assert.ok(Array.isArray(q.answers), 'answers should be an array');
  for (const a of q.answers) {
    assert.ok(a.hasOwnProperty('id'), 'answer.id should be present');
    assert.ok(a.hasOwnProperty('answer'), 'answer.answer should be present');
    assert.ok(a.hasOwnProperty('value'), 'answer.value should be present');
  }
}

function assertQvstCampaignFields(c) {
  assert.ok(c.hasOwnProperty('id'), 'id should be present');
  assert.ok(c.hasOwnProperty('name'), 'name should be present');
  assert.ok(Array.isArray(c.themes), 'themes should be an array');
  for (const t of c.themes) {
    assert.ok(t.hasOwnProperty('id'), 'theme.id should be present');
    assert.ok(t.hasOwnProperty('name'), 'theme.name should be present');
  }
  assert.ok(['OPEN', 'DRAFT', 'CLOSED', 'ARCHIVED'].includes(c.status), 'status should be valid');
  assert.ok(c.hasOwnProperty('start_date'), 'start_date should be present');
  assert.ok(c.hasOwnProperty('end_date'), 'end_date should be present');
  assert.ok(c.hasOwnProperty('action'), 'action should be present');
  assert.ok(c.hasOwnProperty('participation_rate'), 'participation_rate should be present');
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
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
  assert.ok(Array.isArray(this.body), 'Response should be an array');
  for (const q of this.body) {
    assertQvstQuestionFields(q);
    assert.strictEqual(q.no_longer_used, false, 'All questions should be active');
  }
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
  assert.ok(Array.isArray(this.body), 'Response should be an array');
  assert.ok(this.body.some(q => q.no_longer_used === true), 'At least one question should be no longer used');
  for (const q of this.body) {
    assertQvstQuestionFields(q);
  }
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
  assert.ok(Array.isArray(this.body), 'Response should be an array');
  for (const c of this.body) {
    assertQvstCampaignFields(c);
    assert.strictEqual(c.status, 'OPEN', 'Only active campaigns (OPEN) should be present');
  }
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
  assert.ok(Array.isArray(this.body), 'Response should be an array');
  for (const c of this.body) {
    assertQvstCampaignFields(c);
  }
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.strictEqual(this.response.status, 201, 'Status should be 201');
  assert.deepStrictEqual(this.body, {}, 'Response body should be empty');
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.ok(c.hasOwnProperty('campaignId'), 'campaignId should be present');
  assert.ok(c.hasOwnProperty('campaignName'), 'campaignName should be present');
  assert.ok(c.hasOwnProperty('campaignStatus'), 'campaignStatus should be present');
  assert.ok(c.hasOwnProperty('startDate'), 'startDate should be present');
  assert.ok(c.hasOwnProperty('endDate'), 'endDate should be present');
  assert.ok(c.hasOwnProperty('action'), 'action should be present');
  assert.ok(Array.isArray(c.themes), 'themes should be an array');
  for (const t of c.themes) {
    assert.ok(t.hasOwnProperty('id'), 'theme.id should be present');
    assert.ok(t.hasOwnProperty('name'), 'theme.name should be present');
  }
  assert.ok(Array.isArray(c.questions), 'questions should be an array');
  for (const q of c.questions) {
    assert.ok(q.hasOwnProperty('question_id'), 'question_id should be present');
    assert.ok(q.hasOwnProperty('question'), 'question should be present');
    assert.ok(q.hasOwnProperty('answer_repo_id'), 'answer_repo_id should be present');
    assert.ok(q.hasOwnProperty('reversed_question'), 'reversed_question should be present');
    assert.ok(q.hasOwnProperty('no_longer_used'), 'no_longer_used should be present');
    assert.ok(q.hasOwnProperty('status'), 'status should be present');
    assert.ok(q.hasOwnProperty('action'), 'action should be present');
    assert.ok(Array.isArray(q.answers), 'answers should be an array');
    for (const a of q.answers) {
      assert.ok(a.hasOwnProperty('id'), 'answer.id should be present');
      assert.ok(a.hasOwnProperty('answer'), 'answer.answer should be present');
      assert.ok(a.hasOwnProperty('value'), 'answer.value should be present');
      assert.ok(a.hasOwnProperty('numberAnswered'), 'numberAnswered should be present');
    }
  }
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.ok(Array.isArray(this.body), 'Response should be an array');
  for (const q of this.body) {
    assert.ok(q.hasOwnProperty('question_id'), 'question_id should be present');
    assert.ok(q.hasOwnProperty('question'), 'question should be present');
    assert.ok(Array.isArray(q.answers), 'answers should be an array');
    for (const a of q.answers) {
      assert.ok(a.hasOwnProperty('id'), 'answer.id should be present');
      assert.ok(a.hasOwnProperty('answer'), 'answer.answer should be present');
      assert.ok(a.hasOwnProperty('value'), 'answer.value should be present');
    }
  }
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
  assert.ok(Array.isArray(this.body), 'Response should be an array');
  for (const ar of this.body) {
    assert.ok(ar.hasOwnProperty('id'), 'id should be present');
    assert.ok(ar.hasOwnProperty('repoName'), 'name should be present');
    assert.ok(Array.isArray(ar.answers), 'answers should be an array');
    for (const a of ar.answers) {
      assert.ok(a.hasOwnProperty('id'), 'answer.id should be present');
      assert.ok(a.hasOwnProperty('answer'), 'answer.answer should be present');
      assert.ok(a.hasOwnProperty('value'), 'answer.value should be present');
    }
  }
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
  assert.ok(Array.isArray(this.body), 'Response should be an array');
  const expectedThemeId = Number(this.themeId);
  for (const q of this.body) {
    assertQvstQuestionFields(q);
    assert.strictEqual(Number(q.theme_id), expectedThemeId, 'All questions should belong to the requested theme');
  }
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
  assert.ok(Array.isArray(this.body), 'Response should be an array');
  for (const t of this.body) {
    assert.ok(t.hasOwnProperty('id'), 'id should be present');
    assert.ok(t.hasOwnProperty('name'), 'name should be present');
  }
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.strictEqual(this.response.status, 201, 'Status should be 201');
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
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
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
  mainKeys.forEach(key => {
    assert.ok(body.hasOwnProperty(key), `La propriété '${key}' doit être présente dans la réponse`);
  });
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
  assert.strictEqual(this.response.status, 204, 'Status should be 204');
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
  assert.strictEqual(this.response.status, 201, 'Status should be 201');
});