import { safeJson } from './safeJson.js';

export const BASE_URL = 'http://localhost:7830/wp-json/xpeho/v1';

export async function apiGet(context, path) {
  const res = await fetch(`${BASE_URL}${path}`, {
    headers: { Authorization: `Bearer ${context.token}` }
  });
  context.response = res;
  context.body = await safeJson(res);
}

export async function apiPost(context, path, body) {
  const res = await fetch(`${BASE_URL}${path}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${context.token}`
    },
    body: JSON.stringify(body)
  });
  context.response = res;
  context.body = await safeJson(res);
}

export async function apiPut(context, path, body) {
  const res = await fetch(`${BASE_URL}${path}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${context.token}`
    },
    body: JSON.stringify(body)
  });
  context.response = res;
  context.body = await safeJson(res);
}

export async function apiDelete(context, path) {
  const res = await fetch(`${BASE_URL}${path}`, {
    method: 'DELETE',
    headers: { Authorization: `Bearer ${context.token}` }
  });
  context.response = res;
  context.body = await safeJson(res);
}
