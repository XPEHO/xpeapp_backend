const { When, Then } = require('@cucumber/cucumber');
const assert = require('node:assert');
const fs = require('fs');
const path = require('path');

When('I upload an image to storage', { timeout: 15000 }, async function () {
  const imagePath = path.join(__dirname, '../../image_for_test/image.jpg');
  const buffer = fs.readFileSync(imagePath);

  const form = new FormData();
  form.append('file', new Blob([buffer], { type: 'image/jpeg' }), 'image.jpg');
  form.append('folder', 'tests');

  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/image-storage', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${this.token}`
      // NE PAS METTRE Content-Type, le boundary est géré automatiquement
    },
    body: form
  });

  this.response = res;
  this.body = await res.text();
  console.log('Storage upload response:', res.status, this.body);
});

Then('I receive a confirmation of image upload', function () {
  assert.strictEqual(this.response.status, 201, 'Status should be 201');
  assert.ok(this.token, 'JWT token should be present in context');
});
