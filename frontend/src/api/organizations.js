import { apiRequest } from './client'

export function fetchOrganizations() {
  return apiRequest('/api/organizations')
}

export function addOrganization(url) {
  return apiRequest('/api/organizations', {
    method: 'POST',
    body: { url },
  })
}

export function refreshOrganization(id) {
  return apiRequest(`/api/organizations/${id}/refresh`, { method: 'POST' })
}

export function deleteOrganization(id) {
  return apiRequest(`/api/organizations/${id}`, { method: 'DELETE' })
}

export function fetchOrganizationReviews(id, page = 1) {
  return apiRequest(`/api/organizations/${id}/reviews?page=${page}`)
}
