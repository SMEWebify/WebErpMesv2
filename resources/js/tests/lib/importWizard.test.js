import { beforeEach, describe, expect, it } from 'vitest';
import { columnLabel, normalize, requiredUnmapped, restoreMapping, saveMapping } from '../../lib/importWizard';

const columns = [
    { index: 0, letter: 'A', header: 'Code tiers' },
    { index: 1, letter: 'B', header: 'Raison sociale' },
    { index: 2, letter: 'C', header: 'Nom commercial' },
];

describe('importWizard', () => {
    beforeEach(() => window.localStorage.clear());

    it('compares header names without case nor accents', () => {
        expect(normalize('  Épaisseur (mm) ')).toBe('epaisseur mm');
    });

    it('keeps the server suggestion when nothing was remembered', () => {
        expect(restoreMapping('companies', columns, { code: 0, label: 1 })).toEqual({ code: 0, label: 1 });
    });

    it('reapplies the column chosen last time for the same header', () => {
        saveMapping('companies', columns, { code: 0, label: 2 });

        // Nouveau fichier, colonnes dans un autre ordre : « Nom commercial » reste le libellé.
        const reordered = [
            { index: 0, letter: 'A', header: 'Nom commercial' },
            { index: 1, letter: 'B', header: 'Raison sociale' },
            { index: 2, letter: 'C', header: 'Code tiers' },
        ];
        expect(restoreMapping('companies', reordered, { code: 2, label: 1 })).toEqual({ code: 2, label: 0 });
    });

    it('lists the required fields without column', () => {
        const fields = [{ key: 'code', required: true }, { key: 'label', required: true }, { key: 'website', required: false }];
        expect(requiredUnmapped(fields, { code: 0, label: null }).map(f => f.key)).toEqual(['label']);
    });

    it('labels a column by its letter and header', () => {
        const t = { column_n: 'Colonne :letter' };
        expect(columnLabel(columns[1], true, t)).toBe('B — Raison sociale');
        expect(columnLabel(columns[1], false, t)).toBe('Colonne B');
    });
});
