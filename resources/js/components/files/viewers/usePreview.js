import { useEffect, useState } from 'react';
import { fetchPreview } from '../../../lib/files/filePreview.js';

/**
 * Charge l'aperçu calculé par le serveur (files.preview) d'un fichier.
 * Renvoie { data, loading, reason } ; reason est posé quand l'aperçu est refusé.
 */
export default function usePreview(url) {
    const [state, setState] = useState({ data: null, loading: Boolean(url), reason: url ? null : 'unreadable' });

    useEffect(() => {
        if (!url) {
            setState({ data: null, loading: false, reason: 'unreadable' });
            return undefined;
        }

        const controller = new AbortController();
        setState({ data: null, loading: true, reason: null });

        fetchPreview(url, { signal: controller.signal })
            .then((data) => setState({ data, loading: false, reason: null }))
            .catch((error) => {
                if (error.name === 'AbortError') return;
                setState({ data: null, loading: false, reason: error.reason ?? 'unreadable' });
            });

        return () => controller.abort();
    }, [url]);

    return state;
}
