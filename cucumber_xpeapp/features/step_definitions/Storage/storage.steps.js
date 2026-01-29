const { When, Then } = require('@cucumber/cucumber');
const assert = require('node:assert');
const fs = require('fs');
const path = require('path');

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
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
  assert.ok(this.body.byteLength > 0, 'Image data should be present');
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
  assert.ok(Array.isArray(this.body), 'Response should be an array');
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.strictEqual(this.response.status, 200, 'Status should be 200');
  assert.ok(Array.isArray(this.body), 'Response should be an array');
  assert.ok(this.token, 'JWT token should be present in context');
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
  assert.strictEqual(this.response.status, 204, 'Status should be 204');
  assert.ok(this.token, 'JWT token should be present in context');
});
