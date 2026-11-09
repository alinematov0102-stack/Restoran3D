// api.js - PHP API bilan aloqa (../api/index.php)
const BASE = new URL('../api/index.php', window.location.href).href
export const apiConfig = { lang: 'uz' }

export async function api(route, { method = 'GET', body, params = {} } = {}) {
  const q = new URLSearchParams({ r: route, lang: apiConfig.lang, ...params })
  const opt = { method, credentials: 'same-origin', headers: { Accept: 'application/json' } }
  if (method === 'POST') {
    opt.headers['Content-Type'] = 'application/json'
    opt.headers['X-Requested-With'] = 'fetch'
    opt.body = JSON.stringify(body || {})
  }
  let res
  try { res = await fetch(BASE + '?' + q.toString(), opt) } catch (e) { return { ok: false, error: 'network' } }
  try { return await res.json() } catch (e) { return { ok: false, error: 'server_error' } }
}
