import { ref, onMounted, onUnmounted } from 'vue';
import gsap from 'gsap';

export function usePageTransition() {
    const isTransitioning = ref(false);
    const transitionProgress = ref(0);

    const startTransition = (direction = 'out') => {
        isTransitioning.value = true;
        
        return gsap.to(transitionProgress, {
            value: direction === 'out' ? 1 : 0,
            duration: 0.8,
            ease: 'power3.inOut',
            onComplete: () => {
                if (direction === 'in') {
                    isTransitioning.value = false;
                }
            },
        });
    };

    const createWipeTransition = (element, options = {}) => {
        const {
            direction = 'left',
            color = '#050A08',
            duration = 0.8,
        } = options;

        const wipe = document.createElement('div');
        wipe.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: ${color};
            z-index: 9999;
            transform: ${direction === 'left' ? 'translateX(-100%)' : 'translateX(100%)'};
        `;
        document.body.appendChild(wipe);

        const tl = gsap.timeline({
            onComplete: () => wipe.remove(),
        });

        tl.to(wipe, {
            x: '0%',
            duration: duration / 2,
            ease: 'power3.inOut',
        })
        .to(wipe, {
            x: direction === 'left' ? '100%' : '-100%',
            duration: duration / 2,
            ease: 'power3.inOut',
        });

        return tl;
    };

    const createFadeTransition = (element, options = {}) => {
        const {
            duration = 0.5,
        } = options;

        return gsap.to(element, {
            opacity: 0,
            duration,
            ease: 'power2.inOut',
        });
    };

    const createMorphTransition = (fromElement, toElement, options = {}) => {
        const {
            duration = 0.8,
        } = options;

        const tl = gsap.timeline();

        tl.to(fromElement, {
            scale: 0.9,
            opacity: 0,
            filter: 'blur(10px)',
            duration: duration / 2,
            ease: 'power3.inOut',
        })
        .fromTo(toElement, {
            scale: 1.1,
            opacity: 0,
            filter: 'blur(10px)',
        }, {
            scale: 1,
            opacity: 1,
            filter: 'blur(0px)',
            duration: duration / 2,
            ease: 'power3.inOut',
        });

        return tl;
    };

    return {
        isTransitioning,
        transitionProgress,
        startTransition,
        createWipeTransition,
        createFadeTransition,
        createMorphTransition,
    };
}
