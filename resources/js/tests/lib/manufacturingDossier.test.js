import { describe, it, expect } from 'vitest';
import { dossierState, dossierValidatedText, dossierActionIds } from '../../lib/manufacturingDossier.js';

describe('manufacturingDossier', () => {
    it('distingue non exigé, à valider et validé', () => {
        expect(dossierState(null)).toBe('none');
        expect(dossierState({ required: false, validated_at: null })).toBe('none');
        expect(dossierState({ required: true, validated_at: null })).toBe('pending');
        expect(dossierState({ required: true, validated_at: '2026-10-29T09:00:00+00:00' })).toBe('validated');
    });

    it('affiche la date et l’auteur de la validation', () => {
        const text = dossierValidatedText('Dossier validé le :date par :user', {
            validated_at: '2026-10-29T09:00:00+00:00', validated_by: 'Alice',
        }, 'fr-FR');

        expect(text).toBe('Dossier validé le 29/10/2026 par Alice');
    });

    it('retire « par » sans auteur (validation Siteflow)', () => {
        const text = dossierValidatedText('File validated on :date by :user', {
            validated_at: '2026-10-29T09:00:00+00:00', validated_by: null, source: 'siteflow',
        }, 'en-GB');

        expect(text).toBe('File validated on 29/10/2026');
    });

    it('ne rend rien sans validation', () => {
        expect(dossierValidatedText('Dossier validé le :date', { validated_at: null })).toBe('');
    });

    it('ne retient que les articles cochés que l’action change', () => {
        const lines = [
            { id: 1, line_type: 'article', dossier: { required: true, validated_at: null } },
            { id: 2, line_type: 'article', dossier: { required: true, validated_at: '2026-10-29T09:00:00+00:00' } },
            { id: 3, line_type: 'section', dossier: { required: false, validated_at: null } },
            { id: 4, dossier: { required: false, validated_at: null } },
            { id: 5, line_type: 'article', dossier: { required: true, validated_at: null } },
        ];
        const selected = new Set([1, 2, 3, 4]);

        expect(dossierActionIds(lines, selected, 'validate')).toEqual([1, 4]);
        expect(dossierActionIds(lines, selected, 'revoke')).toEqual([2]);
        expect(dossierActionIds(lines, selected, 'require')).toEqual([4]);
        expect(dossierActionIds(lines, selected, 'waive')).toEqual([1, 2]);
        expect(dossierActionIds(lines, selected, 'unknown')).toEqual([]);
    });
});
