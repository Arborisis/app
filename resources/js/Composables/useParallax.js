import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Composable pour ajouter un effet de parallaxe sur un élément
 * @param {number} speed - Vitesse du parallaxe (1 = normal, 0.5 = moitié, -0.3 = inverse)
 * @param {string} direction - 'vertical' | 'horizontal'
 * @returns {object} { elementRef, style }
 */
export function useParallax(speed = 0.3, direction = 'vertical') {
    const elementRef = ref(null);
    const style = ref({});
    let rafId = null;
    let lastScrollY = 0;

    const update = () => {
        if (!elementRef.value) return;

        const rect = elementRef.value.getBoundingClientRect();
        const windowHeight = window.innerHeight;

        // Calculer la position relative de l'élément dans le viewport (-1 à 1)
        const centerOffset = (rect.top + rect.height / 2 - windowHeight / 2) / windowHeight;

        const offset = centerOffset * speed * 100;

        if (direction === 'vertical') {
            style.value = {
                transform: `translateY(${offset}px)`,
                willChange: 'transform',
            };
        } else {
            style.value = {
                transform: `translateX(${offset}px)`,
                willChange: 'transform',
            };
        }
    };

    const onScroll = () => {
        if (rafId) return;
        rafId = requestAnimationFrame(() => {
            update();
            rafId = null;
        });
    };

    onMounted(() => {
        window.addEventListener('scroll', onScroll, { passive: true });
        update();
    });

    onUnmounted(() => {
        window.removeEventListener('scroll', onScroll);
        if (rafId) cancelAnimationFrame(rafId);
    });

    return { elementRef, style };
}

/**
 * Parallaxe multi-couches pour un groupe d'éléments enfants
 * @param {Ref} containerRef - Ref du container
 * @param {number} baseSpeed - Vitesse de base
 */
export function useLayeredParallax(containerRef, baseSpeed = 0.2) {
    const layers = ref([]);
    let rafId = null;

    const update = () => {
        if (!containerRef.value) return;

        const containerRect = containerRef.value.getBoundingClientRect();
        const windowHeight = window.innerHeight;
        const progress = (windowHeight - containerRect.top) / (windowHeight + containerRect.height);

        if (progress < -0.2 || progress > 1.2) return;

        layers.value.forEach((layer, index) => {
            if (!layer.el) return;
            const speed = baseSpeed * (index + 1) * (layer.speed || 1);
            const offset = (progress - 0.5) * speed * 200;
            layer.el.style.transform = `translateY(${offset}px)`;
        });
    };

    const onScroll = () => {
        if (rafId) return;
        rafId = requestAnimationFrame(() => {
            update();
            rafId = null;
        });
    };

    onMounted(() => {
        if (!containerRef.value) return;

        // Auto-détecter les enfants avec data-parallax-layer
        const children = containerRef.value.querySelectorAll('[data-parallax-layer]');
        layers.value = Array.from(children).map((el, i) => ({
            el,
            speed: parseFloat(el.dataset.parallaxLayer) || (i + 1),
        }));

        window.addEventListener('scroll', onScroll, { passive: true });
        update();
    });

    onUnmounted(() => {
        window.removeEventListener('scroll', onScroll);
        if (rafId) cancelAnimationFrame(rafId);
    });
}
