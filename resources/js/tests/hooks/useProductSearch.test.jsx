import { renderHook, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import useProductSearch from '../../hooks/useProductSearch';

const PRODUCTS = [{ id: 1, code: 'P-001', label: 'Platine' }];

describe('useProductSearch', () => {
    beforeEach(() => {
        vi.stubGlobal('fetch', vi.fn(() => Promise.resolve({
            ok: true,
            json: () => Promise.resolve({ products: PRODUCTS }),
        })));
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('queries the server and returns the products', async () => {
        const { result } = renderHook(() => useProductSearch('/fr/products/json/search', 'plat', true, 0));

        await waitFor(() => expect(result.current).toEqual(PRODUCTS));
        expect(fetch).toHaveBeenCalledWith('/fr/products/json/search?q=plat', expect.any(Object));
    });

    it('appends the query to an URL that already has parameters', async () => {
        renderHook(() => useProductSearch('/fr/products/json/search?supplier_id=4', ' tole ', true, 0));

        await waitFor(() => expect(fetch).toHaveBeenCalledWith(
            '/fr/products/json/search?supplier_id=4&q=tole',
            expect.any(Object),
        ));
    });

    it('does not query while inactive', async () => {
        renderHook(() => useProductSearch('/fr/products/json/search', 'plat', false, 0));

        await new Promise((resolve) => setTimeout(resolve, 20));
        expect(fetch).not.toHaveBeenCalled();
    });
});
