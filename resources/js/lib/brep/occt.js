import { THREE, partMaterial } from '../../components/files/viewers/scene.js';

/**
 * Lecture des formats noyau CAO (STEP, IGES, BREP) par OpenCascade compilé en
 * WebAssembly, partagée par le visualiseur de la GED et les miniatures.
 *
 * Les 7 Mo de WASM ne sont téléchargés qu'au premier fichier B-Rep ouvert, puis
 * gardés pour la page.
 */
let occtPromise = null;

export function loadOcct() {
    if (!occtPromise) {
        occtPromise = Promise.all([
            import('occt-import-js'),
            import('occt-import-js/dist/occt-import-js.wasm?url'),
        ]).then(([{ default: occtimportjs }, { default: wasmUrl }]) => occtimportjs({ locateFile: () => wasmUrl }))
            .catch((error) => { occtPromise = null; throw error; });
    }
    return occtPromise;
}

export function readerFor(occt, extension) {
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
export function toMesh(descriptor) {
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
