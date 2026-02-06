import { When, Then } from '@cucumber/cucumber';
import assert from 'node:assert';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { apiGet, apiGetRaw, apiPostForm, apiDeleteText } from '../../support/httpHelpers.js';
import { assertStatus, assertArray, assertToken } from '../../support/assertHelpers.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// ----------- POST UPLOAD IMAGE -----------
When('I upload an image to storage', async function () {
  const imagePath = path.join(__dirname, '../../../image_for_test/image.jpg');
  const buffer = fs.readFileSync(imagePath);

  const form = new FormData();
  form.append('file', new Blob([buffer], { type: 'image/jpeg' }), 'image.jpg');
  form.append('folder', 'tests');

  await apiPostForm(this, '/image-storage', form);
});

Then('I receive a confirmation of image upload', function () {
  assert.strictEqual(this.response.status, 201, 'Status should be 201');
  assert.ok(this.token, 'JWT token should be present in context');
});

// ----------- GET IMAGE FROM STORAGE (by folder and filename) -----------
When('I fetch the image {string} from folder {string}', async function (filename, folder) {
  await apiGetRaw(this, `/image-storage/${encodeURIComponent(folder)}/${encodeURIComponent(filename)}`);
});

Then('I receive the image from storage', function () {
  assertStatus(this.response, 200);
  assert.ok(this.body.byteLength > 0, 'Image data should be present');
  assertToken(this);
});

// ----------- GET ALL FOLDERS -----------
When('I fetch all folders', async function () {
  await apiGet(this, '/image-storage');
});

Then('I receive a list of folders', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  assertToken(this);
});

// ----------- GET ALL IMAGES BY FOLDER -----------
When('I fetch all images from folder {string}', async function (folder) {
  await apiGet(this, `/image-storage?folder=${encodeURIComponent(folder)}`);
});

Then('I receive a list of images from the folder', function () {
  assertStatus(this.response, 200);
  assertArray(this.body);
  assertToken(this);
});

// ----------- DELETE IMAGE FROM STORAGE -----------
When('I delete an image with id {int} from storage', async function (id) {
  await apiDeleteText(this, `/image-storage/${id}`);
});

Then('I receive a confirmation of image deletion', function () {
  assertStatus(this.response, 204);
  assertToken(this);
});
