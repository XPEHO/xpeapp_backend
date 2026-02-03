import { assertStatus, assertArray, assertToken, assertField, assertNotFoundError } from './assertHelpers.js';
import { HttpStatus } from './httpStatus.js';

export function assertListResponse(context, fields) {
  assertStatus(context.response, HttpStatus.OK);
  assertArray(context.body);
  if (context.body.length) {
    const [item] = context.body;
    for (const f of fields) assertField(item, f);
  }
  assertToken(context);
}

export function assertDetailResponse(context, fields) {
  assertStatus(context.response, HttpStatus.OK);
  for (const f of fields) assertField(context.body, f);
  assertToken(context);
}

export function assertCreated(context) {
  assertStatus(context.response, HttpStatus.CREATED);
  assertToken(context);
}

export function assertNoContent(context) {
  assertStatus(context.response, HttpStatus.NO_CONTENT);
  assertToken(context);
}

export function assertNotFound(context) {
  assertStatus(context.response, HttpStatus.NOT_FOUND);
  assertNotFoundError(context.body);
  assertToken(context);
}
