// Shared assertion helpers for Cucumber step definitions
const assert = require('node:assert');

function assertStatus(response, expected, msg) {
  assert.strictEqual(response.status, expected, msg || `Status should be ${expected}`);
}

function assertArray(obj, msg) {
  assert.ok(Array.isArray(obj), msg || 'Response should be an array');
}

function assertToken(context) {
  assert.ok(context.token, 'JWT token should be present in context');
}

function assertField(obj, field, msg) {
  assert.ok(obj[field], msg || `${field} should be present`);
}

function assertFields(obj, fields) {
  for (const field of fields) {
    assert.ok(obj[field], `${field} should be present`);
  }
}


function assertHasOwn(obj, field, msg) {
  assert.ok(Object.hasOwn(obj, field), msg || `${field} should be present`);
}

function assertHasOwnFields(obj, fields) {
  for (const field of fields) {
    assert.ok(Object.hasOwn(obj, field), `${field} should be present`);
  }
}

module.exports = {
  assertStatus,
  assertArray,
  assertToken,
  assertField,
  assertFields,
  assertHasOwn,
  assertHasOwnFields,
};
