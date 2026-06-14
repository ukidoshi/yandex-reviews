function getCookie(name) {
  const match = document.cookie.match(new RegExp(`(^|;\\s*)${name}=([^;]*)`))
  return match ? decodeURIComponent(match[2]) : null
}

async function ensureCsrfCookie() {
  const response = await fetch('/sanctum/csrf-cookie', {
    credentials: 'include',
  })

  if (!response.ok) {
    throw new Error('Не удалось получить CSRF cookie')
  }
}

function getCsrfToken() {
  return getCookie('XSRF-TOKEN')
}

export async function apiRequest(url, options = {}) {
  const method = (options.method ?? 'GET').toUpperCase()
  const needsCsrf = !['GET', 'HEAD', 'OPTIONS'].includes(method)

  if (needsCsrf) {
    await ensureCsrfCookie()
  }

  const headers = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    ...(options.headers ?? {}),
  }

  const csrfToken = needsCsrf ? getCsrfToken() : null
  if (csrfToken) {
    headers['X-XSRF-TOKEN'] = csrfToken
  }

  const response = await fetch(url, {
    ...options,
    method,
    headers,
    credentials: 'include',
    body: options.body ? JSON.stringify(options.body) : undefined,
  })

  let data = null
  const contentType = response.headers.get('content-type') ?? ''

  if (contentType.includes('application/json')) {
    data = await response.json()
  }

  if (!response.ok) {
    const error = new Error(data?.message ?? 'Ошибка запроса')
    error.status = response.status
    error.data = data
    throw error
  }

  return data
}
