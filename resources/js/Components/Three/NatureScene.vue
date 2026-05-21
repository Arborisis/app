<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import * as THREE from 'three';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';
import { ShaderPass } from 'three/addons/postprocessing/ShaderPass.js';
import { VignetteShader } from 'three/addons/shaders/VignetteShader.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';

const canvasContainer = ref(null);
let renderer, scene, camera, composer, animationId;
let trees = [];
let fireflies, dustParticles;
let mouseX = 0, mouseY = 0;
let targetCameraX = 0, targetCameraY = 2;
let scrollProgress = 0;
let startTime = 0;

const COLORS = {
    trunk: 0x6B4423,
    trunkDark: 0x3E2723,
    foliageDark: 0x1B4332,
    foliageMid: 0x2D6A4F,
    foliageLight: 0x40916C,
    foliageEmerald: 0x34D399,
    ground: 0x132A13,
    groundLight: 0x1B4332,
    sky: 0x0B1220,
    groundHemi: 0x1a2f1a,
};

function createLowPolyTree(x, z, scale = 1, type = 'pine') {
    const group = new THREE.Group();

    // Tronc avec détail
    const trunkHeight = 1.2 * scale;
    const trunkGeo = new THREE.CylinderGeometry(0.06 * scale, 0.16 * scale, trunkHeight, 7);
    const trunkMat = new THREE.MeshPhongMaterial({
        color: COLORS.trunk,
        flatShading: true,
        shininess: 5,
    });
    const trunk = new THREE.Mesh(trunkGeo, trunkMat);
    trunk.position.y = trunkHeight / 2;
    trunk.castShadow = true;
    trunk.receiveShadow = true;
    group.add(trunk);

    // Branches basses pour les grands arbres
    if (scale > 1.0 && type === 'pine') {
        const branchGeo = new THREE.ConeGeometry(0.08 * scale, 0.3 * scale, 5);
        const branchMat = new THREE.MeshPhongMaterial({
            color: COLORS.trunkDark,
            flatShading: true,
            shininess: 5,
        });
        [-1, 1].forEach(side => {
            const branch = new THREE.Mesh(branchGeo, branchMat);
            branch.position.set(side * 0.18 * scale, trunkHeight * 0.35, 0);
            branch.rotation.z = side * Math.PI / 3;
            branch.castShadow = true;
            group.add(branch);
        });
    }

    // Feuillage PBR
    const foliageColor = type === 'pine'
        ? COLORS.foliageMid
        : type === 'dark'
            ? COLORS.foliageDark
            : COLORS.foliageLight;

    const foliageMat = new THREE.MeshPhongMaterial({
        color: foliageColor,
        flatShading: true,
        shininess: 10,
    });

    if (type === 'pine') {
        const layers = [
            { y: trunkHeight + 0.22 * scale, r: 0.6 * scale, h: 0.65 * scale },
            { y: trunkHeight + 0.6 * scale, r: 0.45 * scale, h: 0.55 * scale },
            { y: trunkHeight + 0.95 * scale, r: 0.28 * scale, h: 0.45 * scale },
        ];
        layers.forEach((layer, i) => {
            const cone = new THREE.Mesh(
                new THREE.ConeGeometry(layer.r, layer.h, 8),
                foliageMat.clone()
            );
            cone.position.y = layer.y;
            // Variation de couleur subtile par couche
            const colorVar = new THREE.Color(foliageColor);
            colorVar.offsetHSL(0, 0, (i - 1) * 0.03);
            cone.material.color = colorVar;
            cone.castShadow = true;
            cone.receiveShadow = true;
            group.add(cone);
        });
    } else if (type === 'round') {
        const sphere = new THREE.Mesh(
            new THREE.DodecahedronGeometry(0.55 * scale, 1),
            foliageMat
        );
        sphere.position.y = trunkHeight + 0.45 * scale;
        sphere.castShadow = true;
        sphere.receiveShadow = true;
        group.add(sphere);

        const sphere2 = new THREE.Mesh(
            new THREE.DodecahedronGeometry(0.38 * scale, 1),
            foliageMat.clone()
        );
        sphere2.position.set(0.18 * scale, trunkHeight + 0.75 * scale, 0.12 * scale);
        sphere2.material.color.offsetHSL(0, 0, 0.04);
        sphere2.castShadow = true;
        group.add(sphere2);
    } else {
        const cone = new THREE.Mesh(
            new THREE.ConeGeometry(0.32 * scale, 1.3 * scale, 7),
            foliageMat
        );
        cone.position.y = trunkHeight + 0.55 * scale;
        cone.castShadow = true;
        cone.receiveShadow = true;
        group.add(cone);
    }

    group.position.set(x, 0, z);

    group.userData = {
        initialRotation: { x: 0, z: 0 },
        swaySpeed: 0.4 + Math.random() * 0.7,
        swayAmount: 0.015 + Math.random() * 0.025,
        phase: Math.random() * Math.PI * 2,
    };

    return group;
}

function createGround() {
    const groundGeo = new THREE.PlaneGeometry(30, 30, 12, 12);

    const posAttribute = groundGeo.attributes.position;
    for (let i = 0; i < posAttribute.count; i++) {
        const x = posAttribute.getX(i);
        const y = posAttribute.getY(i);
        const z = Math.sin(x * 0.4) * 0.12 + Math.cos(y * 0.25) * 0.08 + Math.sin(x * 0.8 + y * 0.6) * 0.04;
        posAttribute.setZ(i, z);
    }
    groundGeo.computeVertexNormals();

    const groundMat = new THREE.MeshPhongMaterial({
        color: COLORS.ground,
        flatShading: true,
        shininess: 0,
    });

    const ground = new THREE.Mesh(groundGeo, groundMat);
    ground.rotation.x = -Math.PI / 2;
    ground.position.y = -0.1;
    ground.receiveShadow = true;
    return ground;
}

function createFireflies() {
    const count = 50;
    const positions = new Float32Array(count * 3);
    const colors = new Float32Array(count * 3);
    const sizes = new Float32Array(count);
    const phases = new Float32Array(count);

    const colorObj = new THREE.Color();

    for (let i = 0; i < count; i++) {
        positions[i * 3] = (Math.random() - 0.5) * 14;
        positions[i * 3 + 1] = 0.3 + Math.random() * 3.5;
        positions[i * 3 + 2] = (Math.random() - 0.5) * 14;

        const colorChoice = Math.random();
        if (colorChoice < 0.4) {
            colorObj.setHex(COLORS.foliageEmerald);
        } else if (colorChoice < 0.7) {
            colorObj.setHex(0xD4A574);
        } else {
            colorObj.setHex(0x8FA68E);
        }
        colors[i * 3] = colorObj.r;
        colors[i * 3 + 1] = colorObj.g;
        colors[i * 3 + 2] = colorObj.b;

        sizes[i] = 0.025 + Math.random() * 0.06;
        phases[i] = Math.random() * Math.PI * 2;
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
    geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));
    geometry.setAttribute('phase', new THREE.BufferAttribute(phases, 1));

    const material = new THREE.ShaderMaterial({
        uniforms: {
            uTime: { value: 0 },
        },
        vertexShader: `
            attribute float size;
            attribute vec3 color;
            attribute float phase;
            varying vec3 vColor;
            varying float vAlpha;
            uniform float uTime;

            void main() {
                vColor = color;
                vec3 pos = position;
                pos.x += sin(uTime * 0.7 + phase) * 0.35;
                pos.y += cos(uTime * 0.45 + phase * 1.3) * 0.18 + sin(uTime * 1.1 + phase) * 0.08;
                pos.z += sin(uTime * 0.55 + phase * 0.7) * 0.22;
                vAlpha = 0.35 + 0.65 * pow(sin(uTime * 2.2 + phase), 2.0);
                vec4 mvPosition = modelViewMatrix * vec4(pos, 1.0);
                gl_PointSize = size * (450.0 / -mvPosition.z);
                gl_Position = projectionMatrix * mvPosition;
            }
        `,
        fragmentShader: `
            varying vec3 vColor;
            varying float vAlpha;

            void main() {
                float dist = length(gl_PointCoord - vec2(0.5));
                if (dist > 0.5) discard;
                float alpha = (1.0 - smoothstep(0.08, 0.5, dist)) * vAlpha;
                gl_FragColor = vec4(vColor * 1.3, alpha);
            }
        `,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
    });

    return new THREE.Points(geometry, material);
}

function createDustParticles() {
    const count = 80;
    const positions = new Float32Array(count * 3);
    const speeds = new Float32Array(count);

    for (let i = 0; i < count; i++) {
        positions[i * 3] = (Math.random() - 0.5) * 16;
        positions[i * 3 + 1] = Math.random() * 4;
        positions[i * 3 + 2] = (Math.random() - 0.5) * 16;
        speeds[i] = 0.1 + Math.random() * 0.3;
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('speed', new THREE.BufferAttribute(speeds, 1));

    const material = new THREE.ShaderMaterial({
        uniforms: {
            uTime: { value: 0 },
        },
        vertexShader: `
            attribute float speed;
            varying float vAlpha;
            uniform float uTime;
            void main() {
                vec3 pos = position;
                pos.y += sin(uTime * speed + position.x) * 0.05;
                pos.x += cos(uTime * speed * 0.7 + position.z) * 0.03;
                vAlpha = 0.15 + 0.1 * sin(uTime * 0.5 + position.y);
                vec4 mvPosition = modelViewMatrix * vec4(pos, 1.0);
                gl_PointSize = 1.5 * (300.0 / -mvPosition.z);
                gl_Position = projectionMatrix * mvPosition;
            }
        `,
        fragmentShader: `
            varying float vAlpha;
            void main() {
                float dist = length(gl_PointCoord - vec2(0.5));
                if (dist > 0.5) discard;
                float alpha = (1.0 - smoothstep(0.1, 0.5, dist)) * vAlpha;
                gl_FragColor = vec4(0.85, 0.9, 0.8, alpha);
            }
        `,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
    });

    return new THREE.Points(geometry, material);
}

function createRocks() {
    const group = new THREE.Group();
    const rockMat = new THREE.MeshPhongMaterial({
        color: 0x3d3d3d,
        flatShading: true,
        shininess: 15,
    });

    for (let i = 0; i < 8; i++) {
        const scale = 0.08 + Math.random() * 0.18;
        const rock = new THREE.Mesh(
            new THREE.DodecahedronGeometry(scale, 0),
            rockMat
        );
        rock.position.set(
            (Math.random() - 0.5) * 10,
            scale * 0.25,
            (Math.random() - 0.5) * 10
        );
        rock.rotation.set(
            Math.random() * Math.PI,
            Math.random() * Math.PI,
            Math.random() * Math.PI
        );
        rock.castShadow = true;
        rock.receiveShadow = true;
        group.add(rock);
    }

    return group;
}

function createGrassTufts() {
    const group = new THREE.Group();
    const grassColors = [COLORS.foliageMid, COLORS.foliageLight, COLORS.foliageDark];

    for (let i = 0; i < 120; i++) {
        const color = grassColors[Math.floor(Math.random() * grassColors.length)];
        const grassMat = new THREE.MeshPhongMaterial({
            color: color,
            flatShading: true,
            shininess: 0,
        });

        // Petits cônes ou pyramides pour l'herbe
        const height = 0.08 + Math.random() * 0.18;
        const blade = new THREE.Mesh(
            new THREE.ConeGeometry(0.015 + Math.random() * 0.02, height, 4),
            grassMat
        );

        const x = (Math.random() - 0.5) * 14;
        const z = (Math.random() - 0.5) * 14;
        const y = height / 2;

        blade.position.set(x, y, z);
        blade.rotation.y = Math.random() * Math.PI;
        blade.rotation.x = (Math.random() - 0.5) * 0.15;
        blade.rotation.z = (Math.random() - 0.5) * 0.15;

        group.add(blade);
    }

    return group;
}

onMounted(() => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!canvasContainer.value) return;
    startTime = performance.now();

    const width = canvasContainer.value.clientWidth;
    const height = canvasContainer.value.clientHeight;

    // Scene
    scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(COLORS.sky, 0.065);

    // Camera cinématique
    camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 50);
    camera.position.set(0, 2.2, 8);
    camera.lookAt(0, 1.2, 0);

    // Renderer haute qualité
    renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: false,
        powerPreference: 'high-performance',
    });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setClearColor(COLORS.sky, 1);
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.9;
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    canvasContainer.value.appendChild(renderer.domElement);

    // Background solide pour masquer le fond
    scene.background = new THREE.Color(COLORS.sky);

    // ========== ÉCLAIRAGE CINÉMATIQUE ==========

    // 1. Hemisphere light - base naturelle ciel/sol
    const hemiLight = new THREE.HemisphereLight(
        0x6B8E6B,  // ciel vert clair
        0x2a4a2a,  // sol
        0.75
    );
    hemiLight.position.set(0, 20, 0);
    scene.add(hemiLight);

    // 2. Key light (lune principale) - ombres nettes
    const moonLight = new THREE.DirectionalLight(0xE8F0FA, 2.0);
    moonLight.position.set(4, 9, 5);
    moonLight.castShadow = true;
    moonLight.shadow.mapSize.width = 2048;
    moonLight.shadow.mapSize.height = 2048;
    moonLight.shadow.camera.near = 0.5;
    moonLight.shadow.camera.far = 25;
    moonLight.shadow.camera.left = -12;
    moonLight.shadow.camera.right = 12;
    moonLight.shadow.camera.top = 12;
    moonLight.shadow.camera.bottom = -12;
    moonLight.shadow.radius = 3;
    moonLight.shadow.bias = -0.0005;
    moonLight.shadow.normalBias = 0.02;
    scene.add(moonLight);

    // 3. Spot light dramatique - rayon traversant la forêt
    const spotLight = new THREE.SpotLight(0xF0D8B0, 3.5, 18, Math.PI / 8, 0.6, 1.5);
    spotLight.position.set(-3, 6, 2);
    spotLight.target.position.set(0, 0, -1);
    spotLight.castShadow = true;
    spotLight.shadow.mapSize.width = 1024;
    spotLight.shadow.mapSize.height = 1024;
    spotLight.shadow.bias = -0.0001;
    spotLight.shadow.radius = 2;
    scene.add(spotLight);
    scene.add(spotLight.target);

    // 4. Rim light émeraude - contour des arbres
    const rimLight = new THREE.DirectionalLight(0x7CFFBC, 1.0);
    rimLight.position.set(-5, 2, -4);
    scene.add(rimLight);

    // 5. Fill light chaude - ambiance intime
    const fillLight = new THREE.PointLight(0xFAE0C0, 1.3, 12, 1.8);
    fillLight.position.set(2, 1.5, 4);
    scene.add(fillLight);

    // 6. Back light froide - profondeur
    const backLight = new THREE.PointLight(0xA8D0EC, 0.9, 15, 2);
    backLight.position.set(0, 3, -5);
    scene.add(backLight);

    // ========== POST-PROCESSING CINÉMATIQUE ==========

    composer = new EffectComposer(renderer);

    // Pass 1: Render
    const renderPass = new RenderPass(scene, camera);
    composer.addPass(renderPass);

    // Pass 2: Bloom très subtil sur les lucioles uniquement
    const bloomPass = new UnrealBloomPass(
        new THREE.Vector2(width, height),
        0.35,  // strength très faible
        0.4,   // radius
        0.85,  // threshold élevé = moins d'effet
    );
    composer.addPass(bloomPass);

    // Pass 3: Vignette cinématique
    const vignettePass = new ShaderPass(VignetteShader);
    vignettePass.uniforms['offset'].value = 1.3;
    vignettePass.uniforms['darkness'].value = 1.1;
    composer.addPass(vignettePass);

    // ========== SCÈNE ==========

    // Ground
    const ground = createGround();
    scene.add(ground);

    // Rocks
    scene.add(createRocks());

    // Grass tufts
    scene.add(createGrassTufts());

    // Trees
    const treeConfigs = [
        { x: -2.8, z: -1.2, s: 1.3, t: 'pine' },
        { x: -1.0, z: -3.0, s: 1.0, t: 'dark' },
        { x: 2.0, z: -2.5, s: 1.15, t: 'pine' },
        { x: 3.2, z: -0.5, s: 0.75, t: 'round' },
        { x: -0.8, z: -3.8, s: 1.4, t: 'pine' },
        { x: 1.2, z: -4.0, s: 0.85, t: 'dark' },
        { x: -3.5, z: 0.8, s: 0.65, t: 'round' },
        { x: 3.8, z: 1.0, s: 1.05, t: 'pine' },
        { x: 0, z: -0.8, s: 1.6, t: 'pine' },
        { x: -2.0, z: 1.8, s: 0.8, t: 'round' },
        { x: 2.2, z: 2.0, s: 0.9, t: 'dark' },
        { x: -0.5, z: 2.5, s: 0.55, t: 'round' },
    ];

    treeConfigs.forEach(cfg => {
        const tree = createLowPolyTree(cfg.x, cfg.z, cfg.s, cfg.t);
        trees.push(tree);
        scene.add(tree);
    });

    // Fireflies
    fireflies = createFireflies();
    scene.add(fireflies);

    // Dust particles (rayons de lumière)
    dustParticles = createDustParticles();
    scene.add(dustParticles);

    // Mouse tracking
    const onMouseMove = (event) => {
        mouseX = (event.clientX / window.innerWidth) * 2 - 1;
        mouseY = (event.clientY / window.innerHeight) * 2 - 1;
    };
    window.addEventListener('mousemove', onMouseMove);

    // Scroll tracking
    const onScroll = () => {
        const section = canvasContainer.value.closest('[data-parallax-section]');
        if (!section) return;
        const rect = section.getBoundingClientRect();
        const windowHeight = window.innerHeight;
        const sectionHeight = rect.height;
        const rawProgress = (windowHeight - rect.top) / (windowHeight + sectionHeight);
        scrollProgress = Math.max(0, Math.min(1, rawProgress));
    };
    window.addEventListener('scroll', onScroll, { passive: true });

    // Resize handler
    const onResize = () => {
        if (!canvasContainer.value) return;
        const w = canvasContainer.value.clientWidth;
        const h = canvasContainer.value.clientHeight;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
        composer.setSize(w, h);
        bloomPass.resolution.set(w, h);
    };
    window.addEventListener('resize', onResize);

    // Animation loop cinématique
    const animate = () => {
        animationId = requestAnimationFrame(animate);
        const elapsed = (performance.now() - startTime) / 1000;

        // Parallaxe caméra
        targetCameraX = mouseX * 0.6 + (scrollProgress - 0.5) * 1.8;
        targetCameraY = 2.2 - mouseY * 0.25 + scrollProgress * 1.2;

        camera.position.x += (targetCameraX - camera.position.x) * 0.025;
        camera.position.y += (targetCameraY - camera.position.y) * 0.025;
        camera.lookAt(0, 1.1 + scrollProgress * 0.4, 0);

        // Swaying des arbres
        if (!prefersReducedMotion) {
            trees.forEach(tree => {
                const data = tree.userData;
                const sway = Math.sin(elapsed * data.swaySpeed + data.phase) * data.swayAmount;
                tree.rotation.z = sway;
                tree.rotation.x = Math.cos(elapsed * data.swaySpeed * 0.7 + data.phase) * data.swayAmount * 0.5;
            });
        }

        // Animation lucioles
        if (fireflies) {
            fireflies.material.uniforms.uTime.value = elapsed;
        }

        // Animation poussière
        if (dustParticles) {
            dustParticles.material.uniforms.uTime.value = elapsed;
        }

        // Animation lumières
        spotLight.intensity = 2.5 + Math.sin(elapsed * 0.3) * 0.4;
        fillLight.intensity = 0.8 + Math.sin(elapsed * 0.5 + 1) * 0.2;

        // Spot light qui orbite lentement
        spotLight.position.x = -3 + Math.sin(elapsed * 0.08) * 1.5;
        spotLight.position.z = 2 + Math.cos(elapsed * 0.08) * 1.5;

        composer.render();
    };
    animate();

    renderer.userData = { onResize, onMouseMove, onScroll };
});

onUnmounted(() => {
    if (animationId) cancelAnimationFrame(animationId);
    if (renderer) {
        const { onResize, onMouseMove, onScroll } = renderer.userData || {};
        if (onResize) window.removeEventListener('resize', onResize);
        if (onMouseMove) window.removeEventListener('mousemove', onMouseMove);
        if (onScroll) window.removeEventListener('scroll', onScroll);

        trees.forEach(tree => {
            tree.traverse(child => {
                if (child.geometry) child.geometry.dispose();
                if (child.material) {
                    if (Array.isArray(child.material)) {
                        child.material.forEach(m => m.dispose());
                    } else {
                        child.material.dispose();
                    }
                }
            });
        });

        if (fireflies) {
            fireflies.geometry.dispose();
            fireflies.material.dispose();
        }
        if (dustParticles) {
            dustParticles.geometry.dispose();
            dustParticles.material.dispose();
        }

        if (composer) composer.dispose();
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
