import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import TimeInput from '../../components/TimeInput';

describe('TimeInput', () => {
    it('minutes : saisir 30 renvoie 0,5 h (valeur stockée)', () => {
        const onChange = vi.fn();
        render(<TimeInput unit="minutes" value={0} onChange={onChange} data-testid="t" />);
        fireEvent.change(screen.getByTestId('t'), { target: { value: '30' } });
        expect(onChange).toHaveBeenLastCalledWith(0.5);
    });

    it('minutes : affiche la valeur stockée en minutes et accepte la virgule', () => {
        const onChange = vi.fn();
        render(<TimeInput unit="minutes" value={0.5} onChange={onChange} data-testid="t" />);
        expect(screen.getByTestId('t').value).toBe('30');
        fireEvent.change(screen.getByTestId('t'), { target: { value: '1,5' } });
        expect(onChange).toHaveBeenLastCalledWith(0.025);
        fireEvent.change(screen.getByTestId('t'), { target: { value: '' } });
        expect(onChange).toHaveBeenLastCalledWith('');
    });

    it('heures : champ numérique historique, valeur inchangée', () => {
        const onChange = vi.fn();
        render(<TimeInput unit="hours" value="0.500" onChange={onChange} data-testid="t" />);
        const input = screen.getByTestId('t');
        expect(input.type).toBe('number');
        expect(input.step).toBe('0.001');
        expect(input.value).toBe('0.500');
        fireEvent.change(input, { target: { value: '0.75' } });
        expect(onChange).toHaveBeenLastCalledWith('0.75');
    });
});
