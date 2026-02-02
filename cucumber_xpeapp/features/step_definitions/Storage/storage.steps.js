const { When, Then } = require('@cucumber/cucumber');
const assert = require('node:assert');
const fs = require('node:fs');
const path = require('node:path');
const { assertStatus, assertArray, assertToken } = require('../../support/assertHelpers');

// ----------- POST UPLOAD IMAGE -----------
// When('I upload an image to storage', { timeout: 15000 }, async function () {
//   const imagePath = path.join(__dirname, '../../image_for_test/image.jpg');
//   const buffer = fs.readFileSync(imagePath);

//   const form = new FormData();
//   form.append('file', new Blob([buffer], { type: 'image/jpeg' }), 'image.jpg');
//   form.append('folder', 'tests');

//   const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/image-storage', {
//     method: 'POST',
//     headers: {
//       Authorization: `Bearer ${this.token}`
//     },
//     body: form
//   });

//   this.response = res;
//   this.body = await res.text();
//   console.log('Storage upload response:', res.status, this.body);
// });

// Then('I receive a confirmation of image upload', function () {
//   assert.strictEqual(this.response.status, 201, 'Status should be 201');
//   assert.ok(this.token, 'JWT token should be present in context');
// });

// ----------- GET IMAGE FROM STORAGE (by folder and filename) -----------
When('I fetch the image {string} from folder {string}', async function (filename, folder) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/image-storage/${encodeURIComponent(folder)}/${encodeURIComponent(filename)}`, {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });

  this.response = res;
  this.body = await res.arrayBuffer();
});

Then('I receive the image from storage', function () {
  assertStatus(this.response, 200);
  assert.ok(this.body.byteLength > 0, 'Image data should be present');
  assertToken(this);
});

// ----------- GET ALL FOLDERS -----------
When('I fetch all folders', async function () {
  const res = await fetch('http://localhost:7830/wp-json/xpeho/v1/image-storage', {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await res.json();
});

Then('I receive a list of folders', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  assertToken(this);
});

// ----------- GET ALL IMAGES BY FOLDER -----------
When('I fetch all images from folder {string}', async function (folder) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/image-storage?folder=${encodeURIComponent(folder)}`, {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${this.token}`
    }
  });
  this.response = res;
  this.body = await res.json();
});

Then('I receive a list of images from the folder', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  assertToken(this);
});

// ----------- DELETE IMAGE FROM STORAGE -----------
When('I delete an image with id {int} from storage', async function (id) {
  const res = await fetch(`http://localhost:7830/wp-json/xpeho/v1/image-storage/${id}`, {
    method: 'DELETE',
    headers: {
      Authorization: `Bearer ${this.token}`,
      'Content-Type': 'application/json'
    },
  });

  this.response = res;
  this.body = await res.text();
});

Then('I receive a confirmation of image deletion', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});
