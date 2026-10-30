import { describe, it, expect } from 'vitest';
import {
    criticality, isCritical, isOverdue, criticalityClass, statusClass,
    closeNeedsPermission, emptyDraft, draftFrom, riskPayload,
} from '../../lib/affairRisks';

describe('affairRisks', () => {
    it('computes the criticality as probability × severity', () => {
        expect(criticality(3, 4)).toBe(12);
        expect(criticality('2', '2')).toBe(4);
        expect(criticality(null, 4)).toBe(0);
    });

    it('is critical from the threshold included', () => {
        expect(isCritical({ probability: 3, severity: 3 }, 9)).toBe(true);
        expect(isCritical({ probability: 2, severity: 4 }, 9)).toBe(false);
        expect(isCritical(null, 9)).toBe(false);
    });

    it('flags an overdue due date only on an active risk', () => {
        const today = '2026-10-07';
        expect(isOverdue({ status: 'open', due_date: '2026-10-06' }, today)).toBe(true);
        expect(isOverdue({ status: 'occurred', due_date: '2026-10-06' }, today)).toBe(true);
        expect(isOverdue({ status: 'open', due_date: '2026-10-07' }, today)).toBe(false);
        expect(isOverdue({ status: 'mitigated', due_date: '2026-10-01' }, today)).toBe(false);
        expect(isOverdue({ status: 'closed', due_date: '2026-10-01' }, today)).toBe(false);
        expect(isOverdue({ status: 'open', due_date: null }, today)).toBe(false);
    });

    it('colours the criticality around the threshold', () => {
        expect(criticalityClass(12, 9)).toBe('text-bg-danger');
        expect(criticalityClass(9, 9)).toBe('text-bg-danger');
        expect(criticalityClass(6, 9)).toBe('text-bg-warning');
        expect(criticalityClass(4, 9)).toBe('text-bg-success');
        expect(statusClass('closed')).toBe('text-bg-success');
        expect(statusClass('unknown')).toBe('text-bg-secondary');
    });

    it('requires the permission to close a risk critical before or after the edit', () => {
        const critical = { status: 'open', probability: 3, severity: 4 };
        const minor = { status: 'open', probability: 1, severity: 2 };

        expect(closeNeedsPermission(critical, { ...critical, status: 'closed' }, 9)).toBe(true);
        // Gravité baissée dans la même saisie : toujours critique avant
        expect(closeNeedsPermission(critical, { ...critical, severity: 1, status: 'closed' }, 9)).toBe(true);
        // Nouveau risque créé directement clos et critique
        expect(closeNeedsPermission(null, { ...critical, status: 'closed' }, 9)).toBe(true);
        expect(closeNeedsPermission(minor, { ...minor, status: 'closed' }, 9)).toBe(false);
        expect(closeNeedsPermission(critical, { ...critical, status: 'mitigated' }, 9)).toBe(false);
        // Déjà clos : la modification n'est pas une clôture
        expect(closeNeedsPermission({ ...critical, status: 'closed' }, { ...critical, status: 'closed' }, 9)).toBe(false);
    });

    it('builds the payload with empty fields as null', () => {
        const draft = { ...emptyDraft(['technical', 'schedule']), label: '  Soudure  ', owner_id: '5', orders_id: '' };
        expect(draft.category).toBe('technical');
        expect(riskPayload(draft)).toEqual({
            label: 'Soudure', description: null, category: 'technical', probability: 2, severity: 2,
            mitigation: null, owner_id: 5, due_date: null, status: 'open', orders_id: null,
        });
        expect(draftFrom({ label: 'X', owner_id: null, status: 'occurred' })).toMatchObject({ label: 'X', owner_id: '', status: 'occurred' });
    });
});
