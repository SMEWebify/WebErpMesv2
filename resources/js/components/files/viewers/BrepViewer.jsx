import React from 'react';
import Viewer3D from './Viewer3D.jsx';
import { THREE, partMaterial } from './scene.js';

/**
 * Viewer for CAD kernel formats: STEP, IGES and BREP.
 *
 * Unlike a mesh, these files describe exact surfaces and have to be tessellated
 * before anything can be drawn. That is done in the browser by OpenCascade
 * compiled to WebAssembly, so no converter has to be installed on the server —
 * which matters for the one-container-per-client deployment model.
 *
 * The 7 MB WASM payload is fetched only when a B-Rep file is actually opened.
 */
async function loadOcct() {
    const [{ default: occtimportjs }, { default: wasmUrl }] = await Promise.all([
        import('occt-import-js'),
        import('occt-import-js/dist/occt-import-js.wasm?url'),
    ]);

    return occtimportjs({ locateFile: () => wasmUrl });
}

function readerFor(occt, extension) {
    switch (extension) {
        case 'step':
        case 'stp':
            return (bytes) => occt.ReadStepFile(bytes, null);
        case 'iges':
        case 'igs':
            return (bytes) => occt.ReadIgesFile(bytes, null);
        case 'brep':
            return (bytes) => occt.ReadBrepFile(bytes, null);
        default:
            throw new Error(`Unsupported B-Rep format: ${extension}`);
    }
}

/**
 * Turn one OCCT mesh descriptor into a three.js mesh.
 */
function toMesh(descriptor) {
    const geometry = new THREE.BufferGeometry();

    geometry.setAttribute(
        'position',
        new THREE.Float32BufferAttribute(descriptor.attributes.position.array, 3),
    );

    if (descriptor.attributes.normal) {
        geometry.setAttribute(
            'normal',
            new THREE.Float32BufferAttribute(descriptor.attributes.normal.array, 3),
        );
    }

    if (descriptor.index) {
        geometry.setIndex(new THREE.Uint32BufferAttribute(descriptor.index.array, 1));
    }

    if (!descriptor.attributes.normal) {
        geometry.computeVertexNormals();
    }

    const mesh = new THREE.Mesh(geometry, partMaterial());
    mesh.name = descriptor.name ?? '';

    return mesh;
}

export default function BrepViewer({ file, t }) {
    const load = async (stage, url, signal) => {
        const occt = await loadOcct();

        const buffer = await fetch(url, { signal, headers: { Accept: '*/*' } })
            .then((res) => {
                if (!res.ok) throw new Error(res.statusText);
                return res.arrayBuffer();
            });

        const result = readerFor(occt, file.extension)(new Uint8Array(buffer));

        if (!result?.success || !result.meshes?.length) {
            throw new Error(t('brep_read_failed'));
        }

        const group = new THREE.Group();
        result.meshes.forEach((descriptor) => group.add(toMesh(descriptor)));

        // A STEP assembly carries its hierarchy, unlike an STL: surfacing the
        // solid count is the cheapest way to show it was read whole.
        return {
            object: group,
            info: t('solids').replace(':count', result.meshes.length.toLocaleString()),
        };
    };

    return <Viewer3D file={file} t={t} load={load} />;
}
