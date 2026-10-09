import { describe, it, expect } from 'vitest';
import { formatDate, formatCurrency, formatCurrencyRounded } from '../../utils';

// Copies locales remplacées par utils.js, gardées ici pour prouver que la
// version partagée affiche exactement la même chose.
const legacy = {
    // QuotesIndex, OrdersIndex, InvoicesIndex, LeadsIndex, *LinesIndex…
    formatDate(dateStr, locale) {
        if (!dateStr) return '—';
        try {
            const [y, m, d] = dateStr.split('-').map(Number);
            return new Intl.DateTimeFormat(locale || 'fr-FR').format(new Date(y, m - 1, d));
        } catch {
            return dateStr;
        }
    },
    // QuoteLinesPage, OrderLinesPage (locale figée, appelées avec un seul argument)
    formatDateFr(dateStr) {
        if (!dateStr) return '—';
        try {
            const [y, m, d] = dateStr.split('-').map(Number);
            return new Intl.DateTimeFormat('fr-FR').format(new Date(y, m - 1, d));
        } catch { return dateStr; }
    },
    formatCurrency(amount, currency, locale) {
        try {
            return new Intl.NumberFormat(locale || 'fr-FR', {
                style:    'currency',
                currency: currency || 'EUR',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(amount);
        } catch {
            return `${Number(amount).toFixed(2)} ${currency ?? '€'}`;
        }
    },
    // CreditNotesIndex, DeliverysIndex, OpportunitiesIndex
    formatCurrencyRounded(amount, currency, locale) {
        try {
            return new Intl.NumberFormat(locale || 'fr-FR', {
                style:                'currency',
                currency:             currency || 'EUR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(amount);
        } catch {
            return `${Number(amount).toFixed(0)} ${currency ?? '€'}`;
        }
    },
};

const DATES   = [null, undefined, '', '2026-10-09', '2026-01-31', '2026-10-09 14:30:00', '09/10/2026', 'n/a', 20261009];
const LOCALES = [undefined, null, '', 'fr-FR', 'en-US', 'de'];
const AMOUNTS = [0, 1234.5, -99.999, '1500.75', null, undefined, 'abc', 1e9];
const CURRENCIES = [undefined, null, '', 'EUR', 'USD', 'XX'];

describe('formatDate', () => {
    it.each(DATES)('date %s : identique à la copie locale, toutes langues', (date) => {
        for (const locale of LOCALES) {
            expect(formatDate(date, locale)).toBe(legacy.formatDate(date, locale));
        }
        expect(formatDate(date)).toBe(legacy.formatDateFr(date));
    });

    it('affiche une date ISO sans décalage de fuseau', () => {
        expect(formatDate('2026-10-09')).toBe('09/10/2026');
        expect(formatDate(null)).toBe('—');
    });
});

describe.each([
    ['formatCurrency',        formatCurrency,        legacy.formatCurrency],
    ['formatCurrencyRounded', formatCurrencyRounded, legacy.formatCurrencyRounded],
])('%s', (_name, shared, old) => {
    it.each(AMOUNTS)('montant %s : identique à la copie locale', (amount) => {
        for (const currency of CURRENCIES) {
            for (const locale of LOCALES) {
                expect(shared(amount, currency, locale)).toBe(old(amount, currency, locale));
            }
        }
    });
});
