import { describe, it, expect } from 'vitest';
import { canRenderThumbnail, prepareSvg, flattenLines } from '../../lib/files/fileThumbnail';
import { dxfToThree } from '../../lib/dxf/toThree';
import { THUMBNAIL_SIDE } from '../../lib/pdf/pdfRender';

describe('fileThumbnail', () => {
    it('draws only the kinds the browser can render, and a heavy STEP only from local bytes', () => {
        expect(canRenderThumbnail(null)).toBe(false);
        expect(canRenderThumbnail({ id: 1, kind: 'other' })).toBe(false);
        expect(canRenderThumbnail({ id: 2, kind: 'cad2d' })).toBe(true);
        expect(canRenderThumbnail({ id: 3, kind: 'vector' })).toBe(true);
        expect(canRenderThumbnail({ id: 4, kind: 'brep', size: 2_000_000 })).toBe(true);
        expect(canRenderThumbnail({ id: 5, kind: 'brep', size: 40_000_000 })).toBe(false);
        expect(canRenderThumbnail({ id: 5, kind: 'brep', size: 40_000_000 }, true)).toBe(true);
    });

    it('sizes an SVG from its viewBox and thickens its strokes', () => {
        // Contour tel que le produit GeoParser : trait en mm, sans taille en px.
        const { markup, width, height } = prepareSvg(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100" width="200mm" height="100mm"><g stroke="#000" stroke-width="0.5"><path d="M0 0 L200 100"/></g></svg>',
        );

        expect(width).toBe(THUMBNAIL_SIDE);
        expect(height).toBe(THUMBNAIL_SIDE / 2);
        expect(markup).toContain(`width="${THUMBNAIL_SIDE}"`);
        expect(markup).toContain('non-scaling-stroke');
        expect(markup).toContain('<path');
    });

    it('builds a viewBox for an SVG that only has a size', () => {
        const { markup, width, height } = prepareSvg('<svg xmlns="http://www.w3.org/2000/svg" width="50" height="100"></svg>');

        expect(markup).toContain('viewBox="0 0 50 100"');
        expect(width).toBe(THUMBNAIL_SIDE / 2);
        expect(height).toBe(THUMBNAIL_SIDE);
    });

    it('rejects a file that is not an SVG', () => {
        expect(() => prepareSvg('<html><body>nope</body></html>')).toThrow();
    });

    it('flattens DXF lines in world coordinates, inserted blocks included', async () => {
        const { object } = await dxfToThree({
            entities: [
                { type: 'LINE', vertices: [{ x: 0, y: 0 }, { x: 10, y: 0 }] },
                { type: 'INSERT', name: 'TROU', position: { x: 100, y: 50 } },
            ],
            blocks: {
                TROU: { position: { x: 0, y: 0 }, entities: [{ type: 'LINE', vertices: [{ x: 0, y: 0 }, { x: 0, y: 5 }] }] },
            },
        });

        const paths = flattenLines(object);

        expect(paths).toHaveLength(2);
        expect(paths[0]).toEqual([[0, 0], [10, 0]]);
        expect(paths[1]).toEqual([[100, 50], [100, 55]]);
    });
});
