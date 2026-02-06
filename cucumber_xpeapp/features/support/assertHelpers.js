import assert from 'node:assert';

export function assertStatus(response, expected, msg) {
  assert.strictEqual(response.status, expected, msg || `Status should be ${expected}`);
}

export function assertArray(obj, msg) {
  assert.ok(Array.isArray(obj), msg || 'Response should be an array');
}

export function assertToken(context) {
  assert.ok(context.token, 'JWT token should be present in context');
}

export function assertField(obj, field, msg) {
  assert.ok(obj[field], msg || `${field} should be present`);
}

export function assertFields(obj, fields) {
  for (const field of fields) {
    assert.ok(obj[field], `${field} should be present`);
  }
}

export function assertHasOwn(obj, field, msg) {
  assert.ok(Object.hasOwn(obj, field), msg || `${field} should be present`);
}

export function assertHasOwnFields(obj, fields) {
  for (const field of fields) {
    assert.ok(Object.hasOwn(obj, field), `${field} should be present`);
  }
}

export function assertNotFoundError(body) {
  assertHasOwn(body.errors, 'not_found', 'Error not_found should be present');
  assertArray(body.errors?.not_found, 'not_found should be an array');
  assert.ok(body.errors?.not_found?.[0]?.includes('not found'), 'Error message should mention not found');
}
