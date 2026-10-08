import { renderHook, act } from '@testing-library/react';
import { describe, it, expect, afterEach } from 'vitest';
import { useIndexTab } from '../../components/table';

const KEY = 'test_index_tab';

const mobile = matches => {
    window.matchMedia = q => ({ matches, media: q, addEventListener: () => {}, removeEventListener: () => {} });
};

afterEach(() => {
    delete window.matchMedia;
    localStorage.clear();
});

describe('useIndexTab', () => {
    it('ouvre le tableau de bord sur PC et la liste sur téléphone sans mémoire', () => {
        mobile(false);
        expect(renderHook(() => useIndexTab(KEY)).result.current[0]).toBe('dashboard');

        mobile(true);
        expect(renderHook(() => useIndexTab(KEY)).result.current[0]).toBe('list');
    });

    it('mémorise le dernier onglet choisi et le rouvre, sur PC comme sur téléphone', () => {
        mobile(false);
        const { result } = renderHook(() => useIndexTab(KEY));
        act(() => result.current[1]('list'));
        expect(result.current[0]).toBe('list');

        expect(renderHook(() => useIndexTab(KEY)).result.current[0]).toBe('list');

        mobile(true);
        localStorage.setItem(KEY, 'dashboard');
        expect(renderHook(() => useIndexTab(KEY)).result.current[0]).toBe('dashboard');
    });

    it('ignore un onglet mémorisé qui n\'est plus proposé', () => {
        mobile(false);
        localStorage.setItem(KEY, 'duplicates');
        expect(renderHook(() => useIndexTab(KEY)).result.current[0]).toBe('dashboard');
        expect(renderHook(() => useIndexTab(KEY, { tabs: ['dashboard', 'list', 'duplicates'] })).result.current[0]).toBe('duplicates');
    });

    it('un onglet imposé prime et ses changements ne sont pas mémorisés', () => {
        mobile(false);
        localStorage.setItem(KEY, 'dashboard');
        const { result } = renderHook(() => useIndexTab(KEY, { forced: 'list' }));
        expect(result.current[0]).toBe('list');

        act(() => result.current[1]('dashboard'));
        expect(result.current[0]).toBe('dashboard');
        act(() => result.current[1]('list'));
        expect(localStorage.getItem(KEY)).toBe('dashboard');
    });
});
