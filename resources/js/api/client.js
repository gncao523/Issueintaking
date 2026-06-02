async function request(path, options = {}) {
  const response = await fetch(path, {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...options.headers,
    },
    ...options,
  });

  const payload = await response.json().catch(() => ({}));

  if (!response.ok) {
    const error = new Error(payload.message || 'Request failed');
    error.status = response.status;
    error.errors = payload.errors;
    throw error;
  }

  return payload;
}

export const api = {
  listIssues: (params) => {
    const query = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value) query.set(key, value);
    });
    const qs = query.toString();
    return request(`/issues${qs ? `?${qs}` : ''}`);
  },
  getIssue: (id) => request(`/issues/${id}`),
  createIssue: (body) =>
    request('/issues', { method: 'POST', body: JSON.stringify(body) }),
  updateIssue: (id, body) =>
    request(`/issues/${id}`, { method: 'PATCH', body: JSON.stringify(body) }),
  addComment: (id, body) =>
    request(`/issues/${id}/comments`, {
      method: 'POST',
      body: JSON.stringify(body),
    }),
};
