<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import * as THREE from 'three';

const canvasContainer = ref(null);
let renderer, scene, camera, animationId;
let trees = [];
let fireflies = [];
let fogParticles = [];
let windStrength = 0;

const props = defineProps({
    scrollProgress: {
        type: Number,
        default: 0,
    },
});

onMounted(() => {
    if (!canvasContainer.value) return;

    // Scene setup
    scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x050A08, 0.02);

    camera = new THREE.PerspectiveCamera(
        60,
        window.innerWidth / window.innerHeight,
        0.1,
        100
    );
    camera.position.set(0, 2, 8);

    renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true,
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setClearColor(0x050A08, 1);
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    canvasContainer.value.appendChild(renderer.domElement);

    // Lighting
    const ambientLight = new THREE.AmbientLight(0x34D399, 0.1);
    scene.add(ambientLight);

    const moonLight = new THREE.DirectionalLight(0xA8C5BB, 0.5);
    moonLight.position.set(5, 10, 5);
    moonLight.castShadow = true;
    moonLight.shadow.mapSize.width = 2048;
    moonLight.shadow.mapSize.height = 2048;
    scene.add(moonLight);

    const fireflyLight = new THREE.PointLight(0x34D399, 0.3, 10);
    fireflyLight.position.set(0, 3, 0);
    scene.add(fireflyLight);

    // Ground
    const groundGeometry = new THREE.PlaneGeometry(50, 50, 64, 64);
    const groundMaterial = new THREE.MeshStandardMaterial({
        color: 0x0B1A15,
        roughness: 0.9,
        metalness: 0.1,
    });
    
    // Add terrain variation
    const positions = groundGeometry.attributes.position;
    for (let i = 0; i < positions.count; i++) {
        const x = positions.getX(i);
        const y = positions.getY(i);
        const z = Math.sin(x * 0.2) * Math.cos(y * 0.2) * 0.5 + Math.random() * 0.1;
        positions.setZ(i, z);
    }
    groundGeometry.computeVertexNormals();
    
    const ground = new THREE.Mesh(groundGeometry, groundMaterial);
    ground.rotation.x = -Math.PI / 2;
    ground.receiveShadow = true;
    scene.add(ground);

    // Procedural trees
    function createTree(x, z, scale = 1) {
        const treeGroup = new THREE.Group();
        
        // Trunk
        const trunkGeometry = new THREE.CylinderGeometry(
            0.1 * scale, 
            0.2 * scale, 
            2 * scale, 
            6
        );
        const trunkMaterial = new THREE.MeshStandardMaterial({
            color: 0x1A2B24,
            roughness: 0.9,
        });
        const trunk = new THREE.Mesh(trunkGeometry, trunkMaterial);
        trunk.position.y = 1 * scale;
        trunk.castShadow = true;
        treeGroup.add(trunk);

        // Foliage layers
        const foliageColors = [0x0D3328, 0x144D3A, 0x1A6348];
        for (let i = 0; i < 3; i++) {
            const foliageGeometry = new THREE.ConeGeometry(
                (0.8 - i * 0.2) * scale,
                1.5 * scale,
                6
            );
            const foliageMaterial = new THREE.MeshStandardMaterial({
                color: foliageColors[i],
                roughness: 0.8,
            });
            const foliage = new THREE.Mesh(foliageGeometry, foliageMaterial);
            foliage.position.y = (2 + i * 0.8) * scale;
            foliage.castShadow = true;
            
            // Store original position for wind animation
            foliage.userData = {
                originalY: foliage.position.y,
                originalRotation: foliage.rotation.clone(),
                windOffset: Math.random() * Math.PI * 2,
            };
            
            treeGroup.add(foliage);
        }

        treeGroup.position.set(x, 0, z);
        
        // Random rotation
        treeGroup.rotation.y = Math.random() * Math.PI * 2;
        
        return treeGroup;
    }

    // Create forest
    for (let i = 0; i < 30; i++) {
        const angle = Math.random() * Math.PI * 2;
        const radius = 3 + Math.random() * 15;
        const x = Math.cos(angle) * radius;
        const z = Math.sin(angle) * radius;
        const scale = 0.5 + Math.random() * 1.5;
        
        const tree = createTree(x, z, scale);
        trees.push(tree);
        scene.add(tree);
    }

    // Fireflies
    const fireflyGeometry = new THREE.SphereGeometry(0.02, 8, 8);
    const fireflyMaterial = new THREE.MeshBasicMaterial({
        color: 0x34D399,
        transparent: true,
        opacity: 0.8,
    });

    for (let i = 0; i < 50; i++) {
        const firefly = new THREE.Mesh(fireflyGeometry, fireflyMaterial.clone());
        firefly.position.set(
            (Math.random() - 0.5) * 20,
            0.5 + Math.random() * 4,
            (Math.random() - 0.5) * 20
        );
        
        firefly.userData = {
            originalPos: firefly.position.clone(),
            speed: 0.2 + Math.random() * 0.5,
            amplitude: 0.5 + Math.random() * 1.5,
            phase: Math.random() * Math.PI * 2,
        };
        
        fireflies.push(firefly);
        scene.add(firefly);
    }

    // Volumetric fog particles
    const fogGeometry = new THREE.PlaneGeometry(0.5, 0.5);
    const fogMaterial = new THREE.MeshBasicMaterial({
        color: 0x34D399,
        transparent: true,
        opacity: 0.03,
        side: THREE.DoubleSide,
        depthWrite: false,
    });

    for (let i = 0; i < 100; i++) {
        const particle = new THREE.Mesh(fogGeometry, fogMaterial.clone());
        particle.position.set(
            (Math.random() - 0.5) * 30,
            Math.random() * 3,
            (Math.random() - 0.5) * 30
        );
        particle.rotation.z = Math.random() * Math.PI;
        
        particle.userData = {
            originalPos: particle.position.clone(),
            driftSpeed: 0.02 + Math.random() * 0.05,
            driftDirection: new THREE.Vector3(
                (Math.random() - 0.5) * 0.1,
                0,
                (Math.random() - 0.5) * 0.1
            ),
        };
        
        fogParticles.push(particle);
        scene.add(particle);
    }

    // Animation loop
    const clock = new THREE.Clock();
    
    function animate() {
        animationId = requestAnimationFrame(animate);
        const elapsed = clock.getElapsedTime();
        
        // Wind effect on trees
        windStrength = Math.sin(elapsed * 0.5) * 0.1 + 0.05;
        
        trees.forEach((tree, index) => {
            tree.children.forEach((child, childIndex) => {
                if (childIndex > 0 && child.userData) { // Skip trunk, animate foliage
                    const windOffset = child.userData.windOffset;
                    child.rotation.z = Math.sin(elapsed * 2 + windOffset) * windStrength;
                    child.rotation.x = Math.cos(elapsed * 1.5 + windOffset) * windStrength * 0.5;
                }
            });
        });

        // Animate fireflies
        fireflies.forEach((firefly) => {
            const data = firefly.userData;
            firefly.position.x = data.originalPos.x + Math.sin(elapsed * data.speed + data.phase) * data.amplitude;
            firefly.position.y = data.originalPos.y + Math.sin(elapsed * data.speed * 0.7 + data.phase) * data.amplitude * 0.3;
            firefly.position.z = data.originalPos.z + Math.cos(elapsed * data.speed + data.phase) * data.amplitude;
            
            // Pulsing glow
            firefly.material.opacity = 0.4 + Math.sin(elapsed * 3 + data.phase) * 0.4;
        });

        // Animate fog particles
        fogParticles.forEach((particle) => {
            const data = particle.userData;
            particle.position.add(data.driftDirection.clone().multiplyScalar(data.driftSpeed));
            
            // Reset if too far
            if (particle.position.distanceTo(data.originalPos) > 5) {
                particle.position.copy(data.originalPos);
            }
            
            // Fade based on height
            particle.material.opacity = 0.02 + (particle.position.y / 3) * 0.03;
        });

        // Camera movement based on scroll
        camera.position.z = 8 - props.scrollProgress * 3;
        camera.position.y = 2 + props.scrollProgress * 2;
        camera.lookAt(0, 1, 0);

        renderer.render(scene, camera);
    }

    animate();

    // Resize handler
    function onResize() {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    }
    window.addEventListener('resize', onResize);

    // Cleanup
    onUnmounted(() => {
        window.removeEventListener('resize', onResize);
        cancelAnimationFrame(animationId);
        renderer.dispose();
    });
});
</script>

<template>
    <div ref="canvasContainer" class="absolute inset-0 z-0" />
</template>
