import React, { useEffect, useState } from 'react';
import { MINUTES, getTimeUnit, parseDuration, toInputValue } from '../lib/duration.js';

/**
 * Champ de saisie d'un temps opératoire. La valeur échangée est TOUJOURS en
 * heures (valeur stockée) ; en mode minutes le champ affiche et reçoit des
 * minutes, converties à la volée (minutes / 60, 3 décimales). En mode heures
 * c'est le <input type="number"> historique, inchangé.
 */
export default function TimeInput({ value, onChange, as: Field = 'input', unit = getTimeUnit(), ...props }) {
    const isMinutes = unit === MINUTES;
    const [text, setText] = useState(() => toInputValue(value, unit));

    // Resynchronise le texte quand la valeur change de l'extérieur (ouverture
    // d'un autre enregistrement), sans écraser une saisie en cours ("0," ...).
    useEffect(() => {
        if (!isMinutes) return;
        if (parseDuration(text, unit) !== parseDuration(value, 'hours')) {
            setText(toInputValue(value, unit));
        }
    }, [value, isMinutes]); // eslint-disable-line react-hooks/exhaustive-deps

    if (!isMinutes) {
        return <Field type="number" step="0.001" min="0" value={value} onChange={e => onChange(e.target.value)} {...props} />;
    }

    return (
        <Field
            type="text"
            inputMode="decimal"
            value={text}
            onChange={e => {
                setText(e.target.value);
                const hours = parseDuration(e.target.value, unit);
                onChange(hours === null ? '' : hours);
            }}
            onBlur={() => setText(toInputValue(value, unit))}
            {...props}
        />
    );
}
