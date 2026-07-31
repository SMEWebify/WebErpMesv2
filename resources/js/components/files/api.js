/**
 * Thin client over the /files/json endpoints exposed by FileApiController.
 */

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function parse(res) {
    const payload = await res.json().catch(() => ({}));

    if (!res.ok) {
        const message = payload?.message
            || Object.values(payload?.errors ?? {}).flat().join(' ')
            || res.statusText;
        throw new Error(message);
    }

    return payload;
}

export async function listFiles(endpoints, target) {
    const url = new URL(endpoints.list, window.location.origin);
    url.searchParams.set('fileable_type', target.type);
    url.searchParams.set('fileable_id', target.id);

    return parse(await fetch(url, {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
    }));
}

/**
 * Upload a batch of files.
 *
 * XMLHttpRequest is used instead of fetch because it is the only way to get
 * upload progress events, which matter for the 50 MB STEP assemblies this is
 * meant to carry.
 */
export function uploadFiles(endpoints, target, files, meta = {}, onProgress) {
    return new Promise((resolve, reject) => {
        const body = new FormData();
        body.append('fileable_type', target.type);
        body.append('fileable_id', target.id);
        files.forEach((file) => body.append('files[]', file));

        if (meta.role) body.append('role', meta.role);
        if (meta.isPrimary) body.append('is_primary', '1');
        if (meta.comment) body.append('comment', meta.comment);
        if (meta.hashtags) body.append('hashtags', meta.hashtags);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', endpoints.store);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());

        xhr.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable && onProgress) {
                onProgress(Math.round((event.loaded / event.total) * 100));
            }
        });

        xhr.addEventListener('load', () => {
            let payload = {};
            try { payload = JSON.parse(xhr.responseText); } catch { /* empty body */ }

            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(payload);
                return;
            }

            reject(new Error(
                payload?.message
                || Object.values(payload?.errors ?? {}).flat().join(' ')
                || xhr.statusText
            ));
        });

        xhr.addEventListener('error', () => reject(new Error('Network error')));
        xhr.send(body);
    });
}

export async function updateFile(endpoints, target, fileId, payload) {
    const url = endpoints.update.replace('__ID__', fileId);

    return parse(await fetch(url, {
        method: 'PATCH',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({
            fileable_type: target.type,
            fileable_id: target.id,
            ...payload,
        }),
    }));
}

export async function deleteFile(endpoints, target, fileId) {
    const url = endpoints.destroy.replace('__ID__', fileId);

    return parse(await fetch(url, {
        method: 'DELETE',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ fileable_type: target.type, fileable_id: target.id }),
    }));
}
