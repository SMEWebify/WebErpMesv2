import { describe, it, expect } from 'vitest';
import { applyProductCriticality, criticalityChoices, criticalityBadges } from '../../lib/purchaseCriticality.js';

describe('purchaseCriticality', () => {
    it('reprend la criticité de l’article choisi', () => {
        const form = { label: 'Tube', certificate_type: '', construction_code: '', nuclear_level: '' };
        const next = applyProductCriticality(form, { certificate_type: '3.1', construction_code: 'RCC-M', nuclear_level: null });

        expect(next).toEqual({ label: 'Tube', certificate_type: '3.1', construction_code: 'RCC-M', nuclear_level: '' });
        expect(form.certificate_type).toBe('');
    });

    it('ne garde pas l’exigence de l’article précédent', () => {
        const form = { certificate_type: '3.2', construction_code: 'RCC-MX', nuclear_level: 'N1' };

        expect(applyProductCriticality(form, { id: 4 })).toEqual({ certificate_type: '', construction_code: '', nuclear_level: '' });
    });

    it('propose la valeur portée même retirée de la configuration', () => {
        expect(criticalityChoices(['2.1', '3.1'], '3.1')).toEqual(['2.1', '3.1']);
        expect(criticalityChoices(['2.1', '3.1'], '3.1B')).toEqual(['2.1', '3.1', '3.1B']);
        expect(criticalityChoices(undefined, '')).toEqual([]);
    });

    it('n’affiche que les champs renseignés', () => {
        expect(criticalityBadges({ certificate_type: '3.1', construction_code: null, nuclear_level: 'N2' })).toEqual(['3.1', 'N2']);
        expect(criticalityBadges({})).toEqual([]);
    });
});
