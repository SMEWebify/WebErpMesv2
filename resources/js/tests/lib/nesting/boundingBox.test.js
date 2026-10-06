import { describe, it, expect } from 'vitest';
import { geometryOnlyClone } from '../../../lib/nesting/boundingBox';

const parse = (text) => new DOMParser().parseFromString(text, 'image/svg+xml').querySelector('svg');

// GHSA-8fp9-7236-m69r: an SVG without viewBox nor size was attached to the
// live document to measure it, so its event handlers ran in our origin.
describe('geometryOnlyClone', () => {
    it('drops every element and attribute able to run script', () => {
        const svg = parse(`<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">
            <image href="/nope" onerror="alert(document.domain)"/>
            <script>alert(2)</script>
            <foreignObject><div onclick="alert(3)"/></foreignObject>
            <a href="javascript:alert(4)"><rect width="1" height="1"/></a>
            <rect width="10" height="10" onmouseover="alert(5)"/>
        </svg>`);

        const markup = geometryOnlyClone(svg).outerHTML;

        expect(markup).not.toMatch(/on\w+=/i);
        expect(markup).not.toMatch(/<(image|script|foreignObject|a)\b/i);
        expect(markup).not.toMatch(/javascript:/i);
    });

    it('keeps the geometry needed to measure the part', () => {
        const svg = parse(`<svg xmlns="http://www.w3.org/2000/svg">
            <g transform="translate(5 5)">
                <rect x="0" y="0" width="10" height="20"/>
                <circle cx="3" cy="3" r="2"/>
                <path d="M0 0 L5 5"/>
            </g>
        </svg>`);

        const clone = geometryOnlyClone(svg);

        expect(clone.querySelector('g').getAttribute('transform')).toBe('translate(5 5)');
        expect(clone.querySelector('rect').getAttribute('height')).toBe('20');
        expect(clone.querySelector('circle').getAttribute('r')).toBe('2');
        expect(clone.querySelector('path').getAttribute('d')).toBe('M0 0 L5 5');
    });
});
