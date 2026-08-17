import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import WorkshopReportsApp from '../../components/WorkshopReportsApp.jsx';

const REPORT = {
    period:  { key: 'today', label: "Aujourd'hui", from: '17/08/2026 00:00', to: '17/08/2026 14:00' },
    periods: [
        { key: 'today', label: "Aujourd'hui" },
        { key: '7d',    label: '7 derniers jours' },
    ],
    kpi: {
        declared_hours: 7.5, sessions: 3, good_qty: 120, bad_qty: 6, scrap_rate: 4.8,
        finished_tasks: 2, active_users: 2, late_tasks: 4,
    },
    per_day:      [{ date: '2026-08-17', label: '17/08', hours: 7.5, good: 120, bad: 6 }],
    per_resource: [{ label: 'Laser 4kW', hours: 5.25, good: 100, bad: 4, scrap_rate: 3.8, sessions: 2 }],
    per_user:     [{ label: 'Kevin',     hours: 7.5,  good: 120, bad: 6, scrap_rate: 4.8, sessions: 3 }],
    per_service:  [{ label: 'Découpe', color: '#17a2b8', hours: 7.5, good: 120, bad: 6, scrap_rate: 4.8 }],
    andon: {
        total: 2, open: 1, avg_minutes: 35.5,
        by_type:     [{ label: 'breakdown', count: 2, open: 1, avg_minutes: 35.5 }],
        by_resource: [{ label: 'Laser 4kW', count: 2 }],
    },
    in_progress: [{
        task_id: 195, label: 'Pliage capot', service: 'Pliage', color: '#28a745',
        user: 'Kevin', resource: 'Presse 100T', since: '17/08 08:12', minutes: 95,
    }],
    generated_at: '17/08/2026 14:00:00',
};

const ENDPOINTS = { report: '/fr/workshop/Reports/json', task: '/fr/workshop/Task/Statu/Id/__ID__' };

describe('WorkshopReportsApp', () => {
    beforeEach(() => {
        vi.stubGlobal('fetch', vi.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve(REPORT) })));
    });

    it('affiche les KPI atelier de la période servie par le serveur', () => {
        render(<WorkshopReportsApp initial={REPORT} endpoints={ENDPOINTS} />);

        expect(screen.getByText('Heures pointées')).toBeInTheDocument();
        expect(screen.getAllByText('7h30').length).toBeGreaterThan(0);   // 7.5 h formatées
        expect(screen.getByText('Taux 4.8 %')).toBeInTheDocument();
        expect(screen.getByText('Pièces bonnes')).toBeInTheDocument();
    });

    it('liste les sessions ouvertes avec un lien vers la déclaration de production', () => {
        render(<WorkshopReportsApp initial={REPORT} endpoints={ENDPOINTS} />);

        const link = screen.getByRole('link', { name: /Pliage capot/ });
        expect(link).toHaveAttribute('href', '/fr/workshop/Task/Statu/Id/195');
        expect(screen.getByText('1h35')).toBeInTheDocument();   // 95 min ouvertes
    });

    it('ne recharge pas depuis le serveur au montage', () => {
        render(<WorkshopReportsApp initial={REPORT} endpoints={ENDPOINTS} />);
        expect(fetch).not.toHaveBeenCalled();
    });

    it('recharge la période demandée quand on change de bouton', async () => {
        const { findByRole } = render(<WorkshopReportsApp initial={REPORT} endpoints={ENDPOINTS} />);

        (await findByRole('button', { name: '7 derniers jours' })).click();

        await vi.waitFor(() => expect(fetch).toHaveBeenCalled());
        expect(String(fetch.mock.calls[0][0])).toContain('period=7d');
    });
});
