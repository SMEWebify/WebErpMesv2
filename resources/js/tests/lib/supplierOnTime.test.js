import { describe, it, expect } from 'vitest';
import { onTimeBadgeClass, formatOnTimeRate } from '../../lib/supplierOnTime';

describe('supplierOnTime', () => {
    it('colours the rate with the same thresholds as the supplier page', () => {
        expect(onTimeBadgeClass(100)).toBe('text-bg-success');
        expect(onTimeBadgeClass(95)).toBe('text-bg-success');
        expect(onTimeBadgeClass(85)).toBe('text-bg-warning');
        expect(onTimeBadgeClass(84.9)).toBe('text-bg-danger');
        expect(onTimeBadgeClass(0)).toBe('text-bg-danger');
        expect(onTimeBadgeClass(null)).toBe('text-bg-secondary');
    });

    it('formats the rate, or a dash without measured line', () => {
        expect(formatOnTimeRate(87.5, 'en-US')).toBe('87.5 %');
        expect(formatOnTimeRate(100, 'en-US')).toBe('100 %');
        expect(formatOnTimeRate(null)).toBe('—');
    });
});
