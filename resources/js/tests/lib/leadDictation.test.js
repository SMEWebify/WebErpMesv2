import { describe, it, expect } from 'vitest';
import { leadPayload } from '../../lib/leadDictation.js';

const draft = {
    companies_id: '', company_label: ' Dupont Métal ', companies_contacts_id: '',
    contact_first_name: 'Paul', contact_name: 'Martin', contact_function: '', contact_mail: 'p@d.test', contact_number: null,
    subject: '200 supports', comment: 'Besoin', source: 'Téléphone', priority: '2',
};

describe('leadPayload', () => {
    it('sends the new company and contact fields when nothing is chosen', () => {
        expect(leadPayload(draft)).toEqual({
            companies_id: null, company_label: 'Dupont Métal', companies_contacts_id: null,
            contact_first_name: 'Paul', contact_name: 'Martin', contact_function: null, contact_mail: 'p@d.test', contact_number: null,
            subject: '200 supports', comment: 'Besoin', source: 'Téléphone', priority: 2,
        });
    });

    it('sends only the ids of an existing company and contact', () => {
        const body = leadPayload({ ...draft, companies_id: '12', companies_contacts_id: '7' });
        expect(body.companies_id).toBe(12);
        expect(body.company_label).toBeNull();
        expect(body.companies_contacts_id).toBe(7);
        expect(body).not.toHaveProperty('contact_name');
    });

    it('ignores a contact left over without its company', () => {
        const body = leadPayload({ ...draft, companies_contacts_id: '7' });
        expect(body.companies_contacts_id).toBeNull();
        expect(body.contact_name).toBe('Martin');
    });

    it('falls back to a lukewarm priority', () => {
        expect(leadPayload({ ...draft, priority: '' }).priority).toBe(3);
    });
});
