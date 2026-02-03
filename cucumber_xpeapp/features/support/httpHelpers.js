import { safeJson } from './safeJson.js';

export const BASE_URL = 'http://localhost:7830/wp-json/xpeho/v1';
export const JWT_URL = 'http://localhost:7830/wp-json/jwt-auth/v1/token';

export async function apiGet(context, path) {
  const res = await fetch(`${BASE_URL}${path}`, {
    headers: { Authorization: `Bearer ${context.token}` }
  });
  context.response = res;
  context.body = await safeJson(res);
}

export async function apiGetWithHeader(context, path, headers) {
  const res = await fetch(`${BASE_URL}${path}`, { headers });
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

export async function apiPostForm(context, path, formData) {
  const res = await fetch(`${BASE_URL}${path}`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${context.token}` },
    body: formData
  });
  context.response = res;
  context.body = await res.text();
}

export async function apiGetRaw(context, path) {
  const res = await fetch(`${BASE_URL}${path}`, {
    headers: { Authorization: `Bearer ${context.token}` }
  });
  context.response = res;
  context.body = await res.arrayBuffer();
}

export async function apiDeleteText(context, path) {
  const res = await fetch(`${BASE_URL}${path}`, {
    method: 'DELETE',
    headers: { Authorization: `Bearer ${context.token}` }
  });
  context.response = res;
  context.body = await res.text();
}

export async function apiPostEmpty(context, path) {
  const res = await fetch(`${BASE_URL}${path}`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${context.token}` }
  });
  context.response = res;
  context.body = await safeJson(res);
}
