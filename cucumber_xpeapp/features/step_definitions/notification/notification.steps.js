import { When, Then, Before, After } from '@cucumber/cucumber';
import assert from 'node:assert';
import sinon from 'sinon';

let fetchStub;
let originalFetch;

Before({ tags: '@mockNotification' }, function () {
  originalFetch = globalThis.fetch;
  fetchStub = sinon.stub(globalThis, 'fetch').callsFake(async (url, options) => {
    // Mock the /notifications endpoint
    if (url.includes('/notifications')) {
      return {
        status: 201,
        text: async () => JSON.stringify({ success: true }),
        json: async () => ({ success: true })
      };
    }
    // Mock the /status:update endpoint
    if (url.includes('/status:update')) {
      return {
        status: 201,
        text: async () => '{}',
        json: async () => ({})
      };
    }
    // For other requests, use the original fetch
    return originalFetch(url, options);
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
