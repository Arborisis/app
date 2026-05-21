<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import * as THREE from 'three';

const canvasContainer = ref(null);
let renderer, scene, camera, barsGroup, animationId;
let startTime = 0;

const BAR_COUNT = 48;
const BAR_WIDTH = 0.06;
const BAR_GAP = 0.04;
const TOTAL_WIDTH = BAR_COUNT * (BAR_WIDTH + BAR_GAP);

onMounted(() => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) return;

    if (!canvasContainer.value) return;
    startTime = performance.now();

    const width = canvasContainer.value.clientWidth;
    const height = canvasContainer.value.clientHeight;

    // Scene
    scene = new THREE.Scene();

    // Camera - orthographic for flat waveform look
    const aspect = width / height;
    const frustumSize = 3;
    camera = new THREE.OrthographicCamera(
        (frustumSize * aspect) / -2,
        (frustumSize * aspect) / 2,
        frustumSize / 2,
        frustumSize / -2,
        0.1,
        100
    );
    camera.position.z = 5;

    // Renderer
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setClearColor(0x000000, 0);
    canvasContainer.value.appendChild(renderer.domElement);

    // Bars
    barsGroup = new THREE.Group();
    const geometry = new THREE.BoxGeometry(BAR_WIDTH, 1, BAR_WIDTH);
    const material = new THREE.MeshBasicMaterial({
        color: 0x34D399,
        transparent: true,
        opacity: 0.15,
    });

    for (let i = 0; i < BAR_COUNT; i++) {
        const bar = new THREE.Mesh(geometry, material.clone());
        const x = (i * (BAR_WIDTH + BAR_GAP)) - TOTAL_WIDTH / 2 + BAR_WIDTH / 2;
        bar.position.set(x, 0, 0);
        bar.userData = {
            phase: i * 0.25,
            baseOpacity: 0.1 + Math.random() * 0.15,
        };
        barsGroup.add(bar);
    }

    scene.add(barsGroup);

    // Resize handler
    const onResize = () => {
        if (!canvasContainer.value) return;
        const w = canvasContainer.value.clientWidth;
        const h = canvasContainer.value.clientHeight;
        const a = w / h;
        camera.left = (frustumSize * a) / -2;
        camera.right = (frustumSize * a) / 2;
        camera.top = frustumSize / 2;
        camera.bottom = frustumSize / -2;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
    };
    window.addEventListener('resize', onResize);

    // Animation loop
    const animate = () => {
        animationId = requestAnimationFrame(animate);
        const elapsed = (performance.now() - startTime) / 1000;

        barsGroup.children.forEach((bar) => {
            const t = elapsed * 1.2 + bar.userData.phase;
            const scaleY = 0.3 + Math.abs(Math.sin(t)) * 1.2 + Math.abs(Math.cos(t * 0.7)) * 0.4;
            bar.scale.y = scaleY;
            bar.position.y = (scaleY * 0.5) - 0.5;
            bar.material.opacity = bar.userData.baseOpacity + Math.sin(t) * 0.05;
        });

        renderer.render(scene, camera);
    };
    animate();

    renderer.userData = { onResize };
});

onUnmounted(() => {
    if (animationId) cancelAnimationFrame(animationId);
    if (renderer) {
        if (renderer.userData?.onResize) {
            window.removeEventListener('resize', renderer.userData.onResize);
        }
        if (barsGroup) {
            barsGroup.children.forEach((bar) => {
                bar.geometry.dispose();
                bar.material.dispose();
            });
        }
        renderer.dispose();
        if (canvasContainer.value && renderer.domElement) {
            canvasContainer.value.removeChild(renderer.domElement);
        }
    }
});
</script>

<template>
    <div ref="canvasContainer" class="absolute inset-0 w-full h-full pointer-events-none" aria-hidden="true" />
</template>
