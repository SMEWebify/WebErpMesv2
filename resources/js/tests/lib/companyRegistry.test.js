import { describe, it, expect } from 'vitest';
import { applyRegistryResult, formatAddress, formatSiret, registryAddress } from '../../lib/companyRegistry';

const result = {
    label: 'ACME TOLERIE', siren: '552100554', naf_code: '25.62B', intra_community_vat: null,
    address: { adress: '12 RUE DES FORGES', zipcode: '35000', city: 'RENNES', country: 'France' },
};

describe('companyRegistry', () => {
    it('reprend les champs du résultat sans effacer une saisie par un vide', () => {
        const form = { label: 'acme', intra_community_vat: 'FR00000000000', comment: 'x' };
        const next = applyRegistryResult(form, result);
        expect(next.label).toBe('ACME TOLERIE');
        expect(next.siren).toBe('552100554');
        expect(next.intra_community_vat).toBe('FR00000000000');
        expect(next.comment).toBe('x');
        expect(form.label).toBe('acme');
    });

    it('ne propose une adresse que si voie, code postal et commune sont connus', () => {
        expect(registryAddress(result)).toEqual(result.address);
        expect(registryAddress({ address: { adress: '', zipcode: '35000', city: 'RENNES' } })).toBeNull();
        expect(registryAddress({})).toBeNull();
    });

    it('formate le SIRET et l’adresse', () => {
        expect(formatSiret('55210055400013')).toBe('552 100 554 00013');
        expect(formatSiret('123')).toBe('123');
        expect(formatAddress(result.address)).toBe('12 RUE DES FORGES, 35000 RENNES');
    });
});
