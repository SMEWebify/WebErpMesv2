import { useEffect, useState } from 'react';

function query(q) {
    try {
        return typeof window !== 'undefined' && typeof window.matchMedia === 'function'
            ? window.matchMedia(q)
            : null;
    } catch {
        return null;
    }
}

/** Vrai tant que la media query correspond ; suit les rotations et redimensionnements. */
export default function useMediaQuery(q) {
    const [matches, setMatches] = useState(() => query(q)?.matches ?? false);

    useEffect(() => {
        const mql = query(q);
        if (!mql) return undefined;
        const onChange = () => setMatches(mql.matches);
        onChange();
        mql.addEventListener?.('change', onChange);
        return () => mql.removeEventListener?.('change', onChange);
    }, [q]);

    return matches;
}
