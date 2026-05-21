<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import * as THREE from 'three';

const canvasContainer = ref(null);
let renderer, scene, camera, particles, animationId;
let startTime = 0;

const COLORS = [
    0x34D399, // arbor-emerald
    0x4A6741, // arbor-moss
    0x8FA68E, // arbor-sage
    0xD4A574, // arbor-amber
];

onMounted(() => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) return;

    if (!canvasContainer.value) return;
    startTime = performance.now();

    const width = canvasContainer.value.clientWidth;
    const height = canvasContainer.value.clientHeight;

    // Scene
    scene = new THREE.Scene();

    // Camera
    camera = new THREE.PerspectiveCamera(60, width / height, 0.1, 100);
    camera.position.z = 5;

    // Renderer
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setClearColor(0x000000, 0);
    canvasContainer.value.appendChild(renderer.domElement);

    // Particles geometry — reduce on mobile for performance and visual clarity
    const isMobile = window.innerWidth < 768;
    const particleCount = isMobile ? 15 : 30;
    const positions = new Float32Array(particleCount * 3);
    const colors = new Float32Array(particleCount * 3);
    const sizes = new Float32Array(particleCount);
    const speeds = new Float32Array(particleCount);

    const colorObj = new THREE.Color();

    for (let i = 0; i < particleCount; i++) {
        positions[i * 3] = (Math.random() - 0.5) * 12;
        positions[i * 3 + 1] = (Math.random() - 0.5) * 8;
        positions[i * 3 + 2] = (Math.random() - 0.5) * 6;

        colorObj.setHex(COLORS[Math.floor(Math.random() * COLORS.length)]);
        colors[i * 3] = colorObj.r;
        colors[i * 3 + 1] = colorObj.g;
        colors[i * 3 + 2] = colorObj.b;

        sizes[i] = 0.03 + Math.random() * 0.06;
        speeds[i] = 0.2 + Math.random() * 0.5;
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
    geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));

    // Custom shader for soft particles
    const material = new THREE.ShaderMaterial({
        uniforms: {
            uTime: { value: 0 },
        },
        vertexShader: `
            attribute float size;
            attribute vec3 color;
            varying vec3 vColor;
            uniform float uTime;
            void main() {
                vColor = color;
                vec3 pos = position;
                pos.y += sin(uTime * 0.3 + position.x) * 0.15;
                pos.x += cos(uTime * 0.2 + position.y) * 0.1;
                vec4 mvPosition = modelViewMatrix * vec4(pos, 1.0);
                gl_PointSize = size * (300.0 / -mvPosition.z);
                gl_Position = projectionMatrix * mvPosition;
            }
        `,
        fragmentShader: `
            varying vec3 vColor;
            void main() {
                float dist = length(gl_PointCoord - vec2(0.5));
                if (dist > 0.5) discard;
                float alpha = 1.0 - smoothstep(0.2, 0.5, dist);
                gl_FragColor = vec4(vColor, alpha * 0.6);
            }
        `,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
    });

    particles = new THREE.Points(geometry, material);
    scene.add(particles);

    // Resize handler
    const onResize = () => {
        if (!canvasContainer.value) return;
        const w = canvasContainer.value.clientWidth;
        const h = canvasContainer.value.clientHeight;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
    };
    window.addEventListener('resize', onResize);

    // Animation loop
    const animate = () => {
        animationId = requestAnimationFrame(animate);
        const elapsed = (performance.now() - startTime) / 1000;
        material.uniforms.uTime.value = elapsed;
        renderer.render(scene, camera);
    };
    animate();

    // Cleanup function storage
    renderer.userData = { onResize };
});

onUnmounted(() => {
    if (animationId) cancelAnimationFrame(animationId);
    if (renderer) {
        if (renderer.userData?.onResize) {
            window.removeEventListener('resize', renderer.userData.onResize);
        }
        if (particles) {
            particles.geometry.dispose();
            particles.material.dispose();
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
