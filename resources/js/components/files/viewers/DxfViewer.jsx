import React from 'react';
import Viewer3D from './Viewer3D.jsx';

/**
 * 2D CAD viewer (DXF).
 *
 * Uses an orthographic camera so a flat pattern reads like a drawing rather
 * than a perspective view, while still reusing the shared three.js stage.
 */
export default function DxfViewer({ file, t }) {
    const load = async (stage, url, signal) => {
        const [{ default: DxfParser }, { dxfToThree }] = await Promise.all([
            import('dxf-parser'),
            import('../../../lib/dxf/toThree.js'),
        ]);

        const text = await fetch(url, { signal, headers: { Accept: '*/*' } })
            .then((res) => {
                if (!res.ok) throw new Error(res.statusText);
                return res.text();
            });

        const parsed = new DxfParser().parseSync(text);
        const { object, stats } = await dxfToThree(parsed);

        if (!object.children.length) {
            throw new Error(t('no_drawable_entity'));
        }

        const info = [
            t('entities').replace(':count', stats.entities.toLocaleString()),
            t('layers').replace(':count', stats.layers.toLocaleString()),
            stats.skipped.length ? `${t('ignored')}: ${stats.skipped.join(', ')}` : null,
        ].filter(Boolean).join(' — ');

        return { object, info };
    };

    return <Viewer3D file={file} t={t} load={load} orthographic />;
}
