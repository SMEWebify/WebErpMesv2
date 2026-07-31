import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

/**
 * Shared three.js stage used by every 3D viewer (mesh, B-Rep, DXF).
 *
 * Keeping a single implementation means STL, STEP and DXF all get the same
 * camera behaviour, the same lighting and the same standard views, instead of
 * the one-off script that used to live inline in products-show.blade.php.
 */
export function createStage(container, { background = 0x30343c, orthographic = false } = {}) {
    const width = container.clientWidth || 800;
    const height = container.clientHeight || 600;

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(background);

    const camera = orthographic
        ? new THREE.OrthographicCamera(-width / 2, width / 2, height / 2, -height / 2, -10000, 10000)
        : new THREE.PerspectiveCamera(45, width / height, 0.1, 100000);
    camera.position.set(0, 0, 100);

    const renderer = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(width, height);
    container.appendChild(renderer.domElement);

    scene.add(new THREE.AmbientLight(0xffffff, 1.2));

    const key = new THREE.DirectionalLight(0xffffff, 2.0);
    key.position.set(1, 1, 1);
    scene.add(key);

    const fill = new THREE.DirectionalLight(0xffffff, 0.8);
    fill.position.set(-1, -0.5, -1);
    scene.add(fill);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.1;

    let frame = null;
    const animate = () => {
        frame = requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    };
    animate();

    const onResize = () => {
        const w = container.clientWidth || width;
        const h = container.clientHeight || height;

        if (camera.isOrthographicCamera) {
            const halfH = (camera.top - camera.bottom) / 2;
            const halfW = halfH * (w / h);
            camera.left = -halfW;
            camera.right = halfW;
        } else {
            camera.aspect = w / h;
        }

        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
    };

    const observer = new ResizeObserver(onResize);
    observer.observe(container);

    return {
        scene,
        camera,
        renderer,
        controls,

        /**
         * Centre an object at the origin and pull the camera back far enough to
         * see all of it, whatever unit the file was authored in.
         */
        frame(object, { direction = [1, -1, 1] } = {}) {
            const box = new THREE.Box3().setFromObject(object);

            if (box.isEmpty()) return;

            const size = box.getSize(new THREE.Vector3());
            const center = box.getCenter(new THREE.Vector3());

            object.position.sub(center);

            const radius = Math.max(size.x, size.y, size.z) / 2 || 1;

            if (camera.isOrthographicCamera) {
                const w = container.clientWidth || width;
                const h = container.clientHeight || height;
                const halfH = radius * 1.2;
                const halfW = halfH * (w / h);
                camera.left = -halfW;
                camera.right = halfW;
                camera.top = halfH;
                camera.bottom = -halfH;
                camera.position.set(0, 0, radius * 10);
                camera.lookAt(0, 0, 0);
            } else {
                const distance = (radius / Math.sin((camera.fov * Math.PI) / 360)) * 1.6;
                const dir = new THREE.Vector3(...direction).normalize();
                camera.position.copy(dir.multiplyScalar(distance));
                camera.near = distance / 100;
                camera.far = distance * 100;
            }

            camera.updateProjectionMatrix();
            controls.target.set(0, 0, 0);
            controls.update();
        },

        /**
         * Move the camera to one of the standard CAD views, keeping distance.
         */
        setView(view) {
            const distance = camera.position.length() || 100;
            const directions = {
                iso: [1, -1, 1],
                front: [0, -1, 0],
                back: [0, 1, 0],
                top: [0, 0, 1],
                bottom: [0, 0, -1],
                left: [-1, 0, 0],
                right: [1, 0, 0],
            };
            const dir = new THREE.Vector3(...(directions[view] ?? directions.iso)).normalize();
            camera.position.copy(dir.multiplyScalar(distance));
            camera.up.set(0, 0, 1);
            camera.lookAt(0, 0, 0);
            controls.target.set(0, 0, 0);
            controls.update();
        },

        dispose() {
            if (frame !== null) cancelAnimationFrame(frame);
            observer.disconnect();
            controls.dispose();
            scene.traverse((child) => {
                child.geometry?.dispose?.();
                const material = child.material;
                if (Array.isArray(material)) material.forEach((m) => m.dispose?.());
                else material?.dispose?.();
            });
            renderer.dispose();
            renderer.domElement.remove();
        },
    };
}

export { THREE };
