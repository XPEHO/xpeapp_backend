const { When, Then, Before, After } = require('@cucumber/cucumber');
const assert = require('node:assert');
const sinon = require('sinon');

let fetchStub;

Before({ tags: '@mockNotification' }, function () {
  fetchStub = sinon.stub(global, 'fetch').resolves({
    status: 201,
    json: async () => ({ success: true })
  });
});

After({ tags: '@mockNotification' }, function () {
  if (fetchStub) fetchStub.restore();
});

When('I send a notification', async function () {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/notifications', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${this.token}`
    },
    body: JSON.stringify({
      title: "Événement aujourd'hui !",
      message: "Point Leaders d'Offres"
    })
  });
  this.response = res;
  this.body = await res.json();
});

Then('I receive a confirmation of notification sent', function () {
  assert.strictEqual(this.response.status, 201, 'Status should be 201');
  assert.ok(this.body.success, 'Success should be true');
});
