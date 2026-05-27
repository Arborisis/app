<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import * as THREE from 'three';

const canvasContainer = ref(null);
let renderer, scene, camera, animationId;
let trees = [];
let fireflies;
let fogMesh;
let startTime = 0;
let mouseX = 0, mouseY = 0;
let targetMouseX = 0, targetMouseY = 0;

const COLORS = {
    bg: 0x050A08,
    tree: 0x1a3d2e,
    treeLight: 0x2d5a3f,
    firefly: 0x34D399,
    fireflyWarm: 0xD4A574,
    fog: 0x0B0F0E,
};

onMounted(() => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) return;
    if (!canvasContainer.value) return;

    startTime = performance.now();
    const width = canvasContainer.value.clientWidth;
    const height = canvasContainer.value.clientHeight;

    // Scene
    scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(COLORS.bg, 0.035);

    // Camera
    camera = new THREE.PerspectiveCamera(60, width / height, 0.1, 100);
    camera.position.set(0, 2, 12);
    camera.lookAt(0, 1, 0);

    // Renderer
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setClearColor(COLORS.bg, 1);
    canvasContainer.value.appendChild(renderer.domElement);

    // Ground
    const groundGeometry = new THREE.PlaneGeometry(100, 100, 64, 64);
    const groundMaterial = new THREE.MeshStandardMaterial({
        color: 0x0d1f15,
        roughness: 0.9,
        metalness: 0.1,
    });
    
    // Add subtle displacement to ground
    const positions = groundGeometry.attributes.position;
    for (let i = 0; i < positions.count; i++) {
        const x = positions.getX(i);
        const y = positions.getY(i);
        const z = Math.sin(x * 0.3) * Math.cos(y * 0.3) * 0.3;
        positions.setZ(i, z);
    }
    groundGeometry.computeVertexNormals();
    
    const ground = new THREE.Mesh(groundGeometry, groundMaterial);
    ground.rotation.x = -Math.PI / 2;
    ground.position.y = -2;
    scene.add(ground);

    // Trees - procedural with variations
    const treeCount = window.innerWidth < 768 ? 30 : 60;
    
    for (let i = 0; i < treeCount; i++) {
        const treeGroup = new THREE.Group();
        
        // Trunk
        const trunkHeight = 2 + Math.random() * 3;
        const trunkGeometry = new THREE.CylinderGeometry(
            0.05 + Math.random() * 0.05,
            0.1 + Math.random() * 0.08,
            trunkHeight,
            6
        );
        const trunkMaterial = new THREE.MeshStandardMaterial({
            color: 0x3d2817,
            roughness: 0.9,
        });
        const trunk = new THREE.Mesh(trunkGeometry, trunkMaterial);
        trunk.position.y = trunkHeight / 2 - 2;
        treeGroup.add(trunk);
        
        // Foliage layers
        const layers = 2 + Math.floor(Math.random() * 3);
        for (let j = 0; j < layers; j++) {
            const foliageGeometry = new THREE.ConeGeometry(
                0.4 + Math.random() * 0.6 - j * 0.15,
                1 + Math.random() * 1.5,
                5 + Math.floor(Math.random() * 3)
            );
            const foliageMaterial = new THREE.MeshStandardMaterial({
                color: Math.random() > 0.5 ? COLORS.tree : COLORS.treeLight,
                roughness: 0.8,
                transparent: true,
                opacity: 0.85,
            });
            const foliage = new THREE.Mesh(foliageGeometry, foliageMaterial);
            foliage.position.y = trunkHeight - 1 + j * 0.8;
            treeGroup.add(foliage);
        }
        
        // Position
        const angle = Math.random() * Math.PI * 2;
        const radius = 3 + Math.random() * 20;
        treeGroup.position.x = Math.cos(angle) * radius;
        treeGroup.position.z = Math.sin(angle) * radius;
        treeGroup.rotation.y = Math.random() * Math.PI;
        treeGroup.rotation.z = (Math.random() - 0.5) * 0.1;
        
        // Scale variation
        const scale = 0.7 + Math.random() * 0.6;
        treeGroup.scale.set(scale, scale, scale);
        
        scene.add(treeGroup);
        trees.push({ mesh: treeGroup, swaySpeed: 0.5 + Math.random() * 0.5, swayAmount: 0.02 + Math.random() * 0.03 });
    }

    // Fireflies with trails
    const fireflyCount = window.innerWidth < 768 ? 50 : 100;
    const fireflyPositions = new Float32Array(fireflyCount * 3);
    const fireflyColors = new Float32Array(fireflyCount * 3);
    const fireflySizes = new Float32Array(fireflyCount);
    const fireflySpeeds = [];
    
    const colorObj = new THREE.Color();
    
    for (let i = 0; i < fireflyCount; i++) {
        fireflyPositions[i * 3] = (Math.random() - 0.5) * 30;
        fireflyPositions[i * 3 + 1] = Math.random() * 8 - 1;
        fireflyPositions[i * 3 + 2] = (Math.random() - 0.5) * 30;
        
        const colorHex = Math.random() > 0.7 ? COLORS.fireflyWarm : COLORS.firefly;
        colorObj.setHex(colorHex);
        fireflyColors[i * 3] = colorObj.r;
        fireflyColors[i * 3 + 1] = colorObj.g;
        fireflyColors[i * 3 + 2] = colorObj.b;
        
        fireflySizes[i] = 0.05 + Math.random() * 0.1;
        
        fireflySpeeds.push({
            x: (Math.random() - 0.5) * 0.02,
            y: (Math.random() - 0.5) * 0.01,
            z: (Math.random() - 0.5) * 0.02,
            phase: Math.random() * Math.PI * 2,
        });
    }
    
    const fireflyGeometry = new THREE.BufferGeometry();
    fireflyGeometry.setAttribute('position', new THREE.BufferAttribute(fireflyPositions, 3));
    fireflyGeometry.setAttribute('color', new THREE.BufferAttribute(fireflyColors, 3));
    fireflyGeometry.setAttribute('size', new THREE.BufferAttribute(fireflySizes, 1));
    
    const fireflyMaterial = new THREE.ShaderMaterial({
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
                
                // Gentle floating motion
                pos.y += sin(uTime * 0.5 + position.x * 0.5) * 0.3;
                pos.x += cos(uTime * 0.3 + position.z * 0.5) * 0.2;
                
                vec4 mvPosition = modelViewMatrix * vec4(pos, 1.0);
                gl_PointSize = size * (400.0 / -mvPosition.z);
                gl_Position = projectionMatrix * mvPosition;
            }
        `,
        fragmentShader: `
            varying vec3 vColor;
            
            void main() {
                float dist = length(gl_PointCoord - vec2(0.5));
                if (dist > 0.5) discard;
                
                // Soft glow
                float alpha = 1.0 - smoothstep(0.0, 0.5, dist);
                alpha *= alpha; // Sharpen
                
                // Color with glow
                vec3 glow = vColor * 2.0;
                gl_FragColor = vec4(glow, alpha * 0.8);
            }
        `,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
    });
    
    fireflies = new THREE.Points(fireflyGeometry, fireflyMaterial);
    scene.add(fireflies);

    // Volumetric fog planes
    const fogGeometry = new THREE.PlaneGeometry(40, 15, 32, 16);
    const fogMaterial = new THREE.ShaderMaterial({
        uniforms: {
            uTime: { value: 0 },
            uColor: { value: new THREE.Color(COLORS.fog) },
        },
        vertexShader: `
            varying vec2 vUv;
            uniform float uTime;
            
            void main() {
                vUv = uv;
                vec3 pos = position;
                pos.y += sin(uTime * 0.2 + position.x * 0.3) * 0.5;
                gl_Position = projectionMatrix * modelViewMatrix * vec4(pos, 1.0);
            }
        `,
        fragmentShader: `
            uniform vec3 uColor;
            uniform float uTime;
            varying vec2 vUv;
            
            void main() {
                float noise = sin(vUv.x * 10.0 + uTime * 0.1) * sin(vUv.y * 8.0 + uTime * 0.15);
                float alpha = smoothstep(0.0, 0.3, vUv.y) * smoothstep(1.0, 0.7, vUv.y);
                alpha *= 0.15 + noise * 0.05;
                gl_FragColor = vec4(uColor, alpha);
            }
        `,
        transparent: true,
        depthWrite: false,
        side: THREE.DoubleSide,
    });
    
    fogMesh = new THREE.Mesh(fogGeometry, fogMaterial);
    fogMesh.position.set(0, 1, -5);
    scene.add(fogMesh);
    
    // Second fog layer
    const fogMesh2 = fogMesh.clone();
    fogMesh2.position.set(5, 0.5, -8);
    fogMesh2.rotation.y = Math.PI * 0.3;
    scene.add(fogMesh2);

    // Mouse tracking
    const onMouseMove = (e) => {
        targetMouseX = (e.clientX / window.innerWidth - 0.5) * 2;
        targetMouseY = (e.clientY / window.innerHeight - 0.5) * 2;
    };
    window.addEventListener('mousemove', onMouseMove);

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
        
        // Smooth mouse follow
        mouseX += (targetMouseX - mouseX) * 0.02;
        mouseY += (targetMouseY - mouseY) * 0.02;
        
        // Camera parallax
        camera.position.x = mouseX * 2;
        camera.position.y = 2 + mouseY * 0.5;
        camera.lookAt(0, 1, 0);
        
        // Tree swaying
        trees.forEach((tree, i) => {
            tree.mesh.rotation.z = Math.sin(elapsed * tree.swaySpeed + i) * tree.swayAmount;
        });
        
        // Update fireflies
        fireflyMaterial.uniforms.uTime.value = elapsed;
        const positions = fireflies.geometry.attributes.position.array;
        for (let i = 0; i < fireflyCount; i++) {
            const speed = fireflySpeeds[i];
            positions[i * 3] += speed.x + Math.sin(elapsed * 0.5 + speed.phase) * 0.005;
            positions[i * 3 + 1] += speed.y + Math.cos(elapsed * 0.3 + speed.phase) * 0.003;
            positions[i * 3 + 2] += speed.z + Math.sin(elapsed * 0.4 + speed.phase) * 0.005;
            
            // Wrap around
            if (positions[i * 3] > 15) positions[i * 3] = -15;
            if (positions[i * 3] < -15) positions[i * 3] = 15;
            if (positions[i * 3 + 1] > 7) positions[i * 3 + 1] = -1;
            if (positions[i * 3 + 1] < -1) positions[i * 3 + 1] = 7;
            if (positions[i * 3 + 2] > 15) positions[i * 3 + 2] = -15;
            if (positions[i * 3 + 2] < -15) positions[i * 3 + 2] = 15;
        }
        fireflies.geometry.attributes.position.needsUpdate = true;
        
        // Update fog
        fogMaterial.uniforms.uTime.value = elapsed;
        
        renderer.render(scene, camera);
    };
    animate();

    // Cleanup storage
    renderer.userData = { onResize, onMouseMove };
});

onUnmounted(() => {
    if (animationId) cancelAnimationFrame(animationId);
    if (renderer) {
        if (renderer.userData?.onResize) {
            window.removeEventListener('resize', renderer.userData.onResize);
        }
        if (renderer.userData?.onMouseMove) {
            window.removeEventListener('mousemove', renderer.userData.onMouseMove);
        }
        trees.forEach(t => {
            t.mesh.traverse(child => {
                if (child.geometry) child.geometry.dispose();
                if (child.material) child.material.dispose();
            });
        });
        if (fireflies) {
            fireflies.geometry.dispose();
            fireflies.material.dispose();
        }
        if (fogMesh) {
            fogMesh.geometry.dispose();
            fogMesh.material.dispose();
        }
        renderer.dispose();
        if (canvasContainer.value && renderer.domElement) {
            canvasContainer.value.removeChild(renderer.domElement);
        }
    }
});
</script>

<template>
    <div ref="canvasContainer" class="absolute inset-0 w-full h-full" aria-hidden="true" />
</template>