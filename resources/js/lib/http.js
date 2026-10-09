// Appels JSON vers le back Laravel (jeton CSRF + en-têtes JSON).
//
// Les écrans migrés de Livewire avaient chacun leur propre `apiFetch`, avec
// des formes d'erreur différentes que leur code lit ensuite (`err.errors`,
// `err.data.errors`, `err.status`…). Chaque variante exportée ici reproduit
// exactement l'une de ces formes : ne pas en changer une sans relire tous
// ses appelants.

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export function jsonHeaders(extra = {}) {
    return {
        'Accept':       'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        ...extra,
    };
}

// Réponse brute (`Response`) : l'appelant teste `res.ok` lui-même.
export function apiRequest(url, options = {}) {
    return fetch(url, { ...options, headers: jsonHeaders(options.headers) });
}

// Succès → JSON. Échec → rejette avec le corps JSON de la réponse
// (`{ message, errors }` pour une 422 Laravel), ou `{ message: statusText }`
// si le corps n'est pas du JSON.
export async function apiFetch(url, options = {}) {
    const res = await apiRequest(url, options);
    if (!res.ok) {
        throw await res.json().catch(() => ({ message: res.statusText }));
    }
    return res.json();
}

// Succès → JSON. Échec → rejette avec `{ status, data }`, `data` étant le
// corps JSON (`{}` s'il n'est pas lisible).
export async function apiFetchWithStatus(url, options = {}) {
    const res = await apiRequest(url, options);
    if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw { status: res.status, data };
    }
    return res.json();
}

// Succès → JSON. Échec → rejette avec une `Error` dont `message` vient du
// back (sinon « HTTP 500 »), plus `errors` (erreurs de validation, `{}` par
// défaut) et `status`.
export async function apiFetchOrThrow(url, options = {}) {
    const res = await apiRequest(url, options);
    if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        const err  = new Error(body.message || `HTTP ${res.status}`);
        err.errors = body.errors ?? {};
        err.status = res.status;
        throw err;
    }
    return res.json();
}
