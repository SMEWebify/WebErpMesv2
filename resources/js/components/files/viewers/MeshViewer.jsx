import React from 'react';
import Viewer3D from './Viewer3D.jsx';
import { THREE } from './scene.js';

/**
 * Viewer for triangle mesh formats: STL, OBJ, PLY, 3MF, glTF/GLB.
 *
 * Loaders are imported on demand so that opening an STL never downloads the
 * glTF parser and vice versa.
 */
async function loaderFor(extension) {
    switch (extension) {
        case 'stl': {
            const { STLLoader } = await import('three/examples/jsm/loaders/STLLoader.js');
            return { loader: new STLLoader(), returnsGeometry: true };
        }
        case 'obj': {
            const { OBJLoader } = await import('three/examples/jsm/loaders/OBJLoader.js');
            return { loader: new OBJLoader(), returnsGeometry: false };
        }
        case 'ply': {
            const { PLYLoader } = await import('three/examples/jsm/loaders/PLYLoader.js');
            return { loader: new PLYLoader(), returnsGeometry: true };
        }
        case '3mf': {
            const { ThreeMFLoader } = await import('three/examples/jsm/loaders/3MFLoader.js');
            return { loader: new ThreeMFLoader(), returnsGeometry: false };
        }
        case 'glb':
        case 'gltf': {
            const { GLTFLoader } = await import('three/examples/jsm/loaders/GLTFLoader.js');
            return { loader: new GLTFLoader(), returnsGeometry: false, unwrap: (result) => result.scene };
        }
        default:
            throw new Error(`Unsupported mesh format: ${extension}`);
    }
}

function countTriangles(object) {
    let triangles = 0;

    object.traverse((child) => {
        const geometry = child.geometry;
        if (!geometry) return;
        triangles += geometry.index
            ? geometry.index.count / 3
            : (geometry.attributes?.position?.count ?? 0) / 3;
    });

    return Math.round(triangles);
}

export default function MeshViewer({ file, t }) {
    const load = async (stage, url, signal) => {
        const { loader, returnsGeometry, unwrap } = await loaderFor(file.extension);

        const buffer = await fetch(url, { signal, headers: { Accept: '*/*' } })
            .then((res) => {
                if (!res.ok) throw new Error(res.statusText);
                return res.arrayBuffer();
            });

        const parsed = loader.parse(buffer, '');

        let object;

        if (returnsGeometry) {
            parsed.computeVertexNormals();
            object = new THREE.Mesh(
                parsed,
                new THREE.MeshPhongMaterial({ color: 0xfff2cc, specular: 0x333333, shininess: 40 }),
            );
        } else {
            object = unwrap ? unwrap(parsed) : parsed;
        }

        return {
            object,
            info: t('triangles').replace(':count', countTriangles(object).toLocaleString()),
        };
    };

    return <Viewer3D file={file} t={t} load={load} />;
}
