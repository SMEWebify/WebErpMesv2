// Test de charge WebErpMesv2 — 3 profils utilisateurs, montée 10→300 VUs.
//
// Prérequis :
//   1. App servie via Laragon (Apache + PHP-FPM), PAS `php artisan serve` (mono-thread).
//   2. App pointée sur la base wem_loadtest peuplée (cf. php artisan loadtest:seed).
//   3. Un utilisateur staff de test existant (TEST_EMAIL / TEST_PASSWORD).
//   4. loadtest/k6/ids.json généré depuis wem_loadtest (pools d'ids réels).
//
// Lancement (PowerShell) :
//   $env:BASE_URL="http://weberpmesv2.test"; $env:LOCALE="fr"
//   $env:TEST_EMAIL="loadtest@example.test"; $env:TEST_PASSWORD="password"
//   k6 run loadtest/k6/erp-load.js
//
//   # activer les écritures (kanban, pointage, facturation, FEC) — À FAIRE EXPRÈS :
//   $env:ENABLE_WRITES="true"; k6 run loadtest/k6/erp-load.js

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Trend, Counter } from 'k6/metrics';
import { randomItem } from 'https://jslib.k6.io/k6-utils/1.4.0/index.js';

// --- Configuration ----------------------------------------------------------
const BASE = (__ENV.BASE_URL || 'http://weberpmesv2.test').replace(/\/$/, '');
const LOCALE = __ENV.LOCALE || 'fr';
const P = LOCALE ? `/${LOCALE}` : '';
const EMAIL = __ENV.TEST_EMAIL || 'loadtest@example.test';
const PASSWORD = __ENV.TEST_PASSWORD || 'password';
const WRITES = (__ENV.ENABLE_WRITES || 'false') === 'true';

// Pools d'ids réels (générés en Phase 3). Fallback : petites plages.
let IDS = { orders: [], quotes: [], companies: [], invoices: [], products: [] };
try {
  IDS = JSON.parse(open('./ids.json'));
} catch (e) {
  IDS = {
    orders: range(1, 100), quotes: range(1, 100), companies: range(1, 50),
    invoices: range(1, 50), products: range(1, 100),
  };
}
function range(a, b) { const r = []; for (let i = a; i <= b; i++) r.push(i); return r; }

// --- Métriques par profil ---------------------------------------------------
const rtCommercial = new Trend('rt_commercial', true);
const rtAtelier = new Trend('rt_atelier', true);
const rtCompta = new Trend('rt_compta', true);
const errByProfile = new Counter('errors_by_profile');

const TREND = { A: rtCommercial, B: rtAtelier, C: rtCompta };

// --- Montée en charge + seuils ---------------------------------------------
// PROFILE=smoke → ramp court (artisan serve mono-thread / validation harness)
// PROFILE=full (défaut) → ramp 10→300 VUs (vhost + PHP-FPM multi-workers)
const STAGES = (__ENV.PROFILE || 'full') === 'smoke'
  ? [
      { duration: '30s', target: 3 },
      { duration: '1m', target: 3 },
      { duration: '45s', target: 8 },
      { duration: '1m30s', target: 8 },
      { duration: '30s', target: 0 },
    ]
  : [
      { duration: '1m', target: 10 },
      { duration: '2m', target: 10 },
      { duration: '1m', target: 50 },
      { duration: '2m', target: 50 },
      { duration: '1m', target: 100 },
      { duration: '2m', target: 100 },
      { duration: '1m', target: 200 },
      { duration: '2m', target: 200 },
      { duration: '1m', target: 300 },
      { duration: '3m', target: 300 },
      { duration: '1m', target: 0 },
    ];

export const options = {
  scenarios: {
    erp: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: STAGES,
      gracefulStop: '30s',
    },
  },
  thresholds: {
    // critères de rupture (Phase 3)
    http_req_failed: ['rate<0.01'],                          // < 1 % d'erreurs
    http_req_duration: ['p(95)<1000', 'p(99)<3000'],         // p95 < 1 s
    'rt_commercial': ['p(50)<500', 'p(95)<1000', 'p(99)<3000'],
    'rt_atelier': ['p(50)<500', 'p(95)<1000', 'p(99)<3000'],
    'rt_compta': ['p(50)<1000', 'p(95)<2000'],               // compta = plus lourd
  },
};

// --- Auth (CSRF, une fois par VU) -------------------------------------------
let loggedIn = false;
let csrfToken = '';

function login() {
  const res = http.get(`${BASE}${P}/login`, { tags: { name: 'GET /login' } });
  const m = res.body && res.body.match(/name="_token"\s+value="([^"]+)"/);
  csrfToken = m ? m[1] : '';
  const post = http.post(`${BASE}${P}/login`, {
    _token: csrfToken, email: EMAIL, password: PASSWORD,
  }, { tags: { name: 'POST /login' }, redirects: 5 });
  const ok = check(post, { 'login OK (pas sur /login)': (r) => !r.url.endsWith('/login') });
  loggedIn = ok;
  // rafraîchit le token CSRF depuis une page authentifiée (réutilisé pour les POST)
  if (ok) {
    const home = http.get(`${BASE}${P}/dashboard`, { tags: { name: 'GET /dashboard (token)' } });
    const m2 = home.body && home.body.match(/name="csrf-token"\s+content="([^"]+)"/);
    if (m2) csrfToken = m2[1];
  }
}

// --- Helper requête mesurée -------------------------------------------------
function req(profile, name, method, url, body, extra) {
  const params = Object.assign({ tags: { name, profile } }, extra || {});
  const res = method === 'GET'
    ? http.get(url, params)
    : http.post(url, body, params);
  TREND[profile].add(res.timings.duration);
  const ok = check(res, { [`${name} < 400`]: (r) => r.status > 0 && r.status < 400 });
  if (!ok) errByProfile.add(1, { profile });
  return res;
}

// --- Profil A : Commercial / ADV (lecture, ~8 actions/min) ------------------
function profileCommercial() {
  group('A-commercial', () => {
    req('A', 'quotes/list', 'GET', `${BASE}${P}/quotes/json/list?page=1`);
    sleep(rnd(5, 8));
    req('A', 'quotes/show', 'GET', `${BASE}${P}/quotes/${randomItem(IDS.quotes)}`);
    sleep(rnd(5, 8));
    req('A', 'orders/list', 'GET', `${BASE}${P}/orders/json/list?page=1`);
    sleep(rnd(5, 8));
    req('A', 'orders/show', 'GET', `${BASE}${P}/orders/${randomItem(IDS.orders)}`);
    sleep(rnd(5, 8));
    const c = randomItem(IDS.companies);
    req('A', 'companies/show', 'GET', `${BASE}${P}/companies/${c}`);
    req('A', 'companies/timeline', 'GET', `${BASE}${P}/companies/${c}/json/timeline`);
    sleep(rnd(6, 10));
  });
}

// --- Profil B : Atelier / Production (~10 actions/min) ----------------------
function profileAtelier() {
  group('B-atelier', () => {
    req('B', 'production/tasks', 'GET', `${BASE}${P}/production/Task`);
    sleep(rnd(4, 6));
    req('B', 'load-planning', 'GET', `${BASE}${P}/production/load-planning/data`);
    sleep(rnd(4, 6));
    req('B', 'gantt/order', 'GET', `${BASE}${P}/production/gantt/order/${randomItem(IDS.orders)}`);
    sleep(rnd(4, 6));
    if (WRITES) {
      // déplacement de carte kanban (à confirmer en Phase 3 : route + payload exacts)
      req('B', 'kanban/sync', 'POST',
        `${BASE}${P}/production/task/kanban/sync`,
        JSON.stringify({ _token: csrfToken }),
        { headers: jsonHeaders() });
    }
    sleep(rnd(5, 8));
  });
}

// --- Profil C : Compta / Gestion (~3 actions/min, lourd) --------------------
function profileCompta() {
  group('C-compta', () => {
    req('C', 'dashboard', 'GET', `${BASE}${P}/dashboard`);
    // widgets KPI du dashboard (pollés par le front)
    req('C', 'kpi/recent-orders', 'GET', `${BASE}${P}/kpi/recent/orders`);
    req('C', 'kpi/otd', 'GET', `${BASE}${P}/kpi/otd`);
    req('C', 'kpi/nc-stats', 'GET', `${BASE}${P}/kpi/nc-stats`);
    req('C', 'kpi/top-clients', 'GET', `${BASE}${P}/kpi/top-clients`);
    sleep(rnd(15, 25));
    req('C', 'invoices/list', 'GET', `${BASE}${P}/invoices/json/list?page=1`);
    req('C', 'invoices/show', 'GET', `${BASE}${P}/invoices/${randomItem(IDS.invoices)}`);
    sleep(rnd(15, 25));
    req('C', 'products/show', 'GET', `${BASE}${P}/products/${randomItem(IDS.products)}`);
    sleep(rnd(15, 25));
    if (WRITES) {
      // facturation groupée + export FEC (très lourds) — à câbler en Phase 3
      req('C', 'invoices/generate-all', 'POST',
        `${BASE}${P}/invoices/request/generate-all`,
        JSON.stringify({ _token: csrfToken }),
        { headers: jsonHeaders() });
    }
    sleep(rnd(10, 20));
  });
}

function jsonHeaders() {
  return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' };
}
function rnd(a, b) { return a + Math.random() * (b - a); }

// --- Point d'entrée VU ------------------------------------------------------
export default function () {
  if (!loggedIn) {
    login();
    if (!loggedIn) { sleep(1); return; }
  }
  // tirage de profil pondéré : 50 % A / 35 % B / 15 % C
  const r = Math.random();
  if (r < 0.50) profileCommercial();
  else if (r < 0.85) profileAtelier();
  else profileCompta();
}
