import { useEffect, useState } from 'react';

// Server-side product search for the line entry pickers. The full catalogue is no
// longer sent to the browser: past a few thousand references it exceeds PHP memory.
export default function useProductSearch(url, query, active, delay = 250) {
    const [results, setResults] = useState([]);

    useEffect(() => {
        if (!active || !url) return undefined;

        const controller = new AbortController();
        const timer = setTimeout(() => {
            const separator = url.includes('?') ? '&' : '?';
            fetch(`${url}${separator}q=${encodeURIComponent(query.trim())}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            })
                .then((r) => (r.ok ? r.json() : { products: [] }))
                .then((d) => setResults(d.products ?? []))
                .catch((e) => { if (e.name !== 'AbortError') setResults([]); });
        }, delay);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [url, query, active, delay]);

    return results;
}
