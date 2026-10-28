import { describe, it, expect } from 'vitest';
import { filterParams, EMPTY_FILTERS } from '../../components/admin/AccessLogApp.jsx';

describe('AccessLogApp filterParams', () => {
    it('sends only the filled filters', () => {
        const params = filterParams({ ...EMPTY_FILTERS, user_id: '3', from: '2026-10-01', subject_id: '  ' });
        expect(params.toString()).toBe('user_id=3&from=2026-10-01');
    });

    it('adds the page for the list but not for the export', () => {
        expect(filterParams({ ...EMPTY_FILTERS, type: 'quote' }, 2).toString()).toBe('type=quote&page=2');
        expect(filterParams({ ...EMPTY_FILTERS, type: 'quote' }).toString()).toBe('type=quote');
    });
});
