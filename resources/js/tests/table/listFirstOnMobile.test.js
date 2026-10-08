import { describe, it, expect, afterEach } from 'vitest';
import { listFirstOnMobile } from '../../components/table';

const mobile = matches => {
    window.matchMedia = q => ({ matches, media: q, addEventListener: () => {}, removeEventListener: () => {} });
};

afterEach(() => { delete window.matchMedia; });

describe('listFirstOnMobile', () => {
    it('ouvre la liste au lieu du tableau de bord sur téléphone', () => {
        mobile(true);
        expect(listFirstOnMobile('dashboard')).toBe('list');
    });

    it('garde le tableau de bord sur PC', () => {
        mobile(false);
        expect(listFirstOnMobile('dashboard')).toBe('dashboard');
    });

    it('respecte un onglet demandé explicitement', () => {
        mobile(true);
        expect(listFirstOnMobile('templates')).toBe('templates');
        expect(listFirstOnMobile('list')).toBe('list');
    });

    it('retombe sur le tableau de bord sans matchMedia', () => {
        expect(listFirstOnMobile('dashboard')).toBe('dashboard');
    });
});
