import { ref, onMounted, onUnmounted } from 'vue';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

export function useScrollAnimation() {
    const triggers = [];

    const createParallax = (element, options = {}) => {
        const {
            speed = 0.5,
            direction = 'vertical',
            start = 'top bottom',
            end = 'bottom top',
        } = options;

        const tween = gsap.to(element, {
            y: direction === 'vertical' ? () => speed * 100 : 0,
            x: direction === 'horizontal' ? () => speed * 100 : 0,
            ease: 'none',
            scrollTrigger: {
                trigger: element,
                start,
                end,
                scrub: true,
            },
        });

        triggers.push(tween.scrollTrigger);
        return tween;
    };

    const createPin = (element, options = {}) => {
        const {
            start = 'top top',
            end = '+=100%',
            pinSpacing = true,
        } = options;

        const st = ScrollTrigger.create({
            trigger: element,
            start,
            end,
            pin: true,
            pinSpacing,
        });

        triggers.push(st);
        return st;
    };

    const createReveal = (element, options = {}) => {
        const {
            y = 60,
            opacity = 0,
            duration = 1,
            ease = 'power3.out',
            start = 'top 85%',
        } = options;

        const tween = gsap.from(element, {
            y,
            opacity,
            duration,
            ease,
            scrollTrigger: {
                trigger: element,
                start,
                toggleActions: 'play none none none',
            },
        });

        triggers.push(tween.scrollTrigger);
        return tween;
    };

    const createMorph = (element, options = {}) => {
        const {
            scale = [0.8, 1],
            rotation = [0, 0],
            borderRadius = ['50%', '0%'],
            start = 'top center',
            end = 'bottom center',
            scrub = true,
        } = options;

        const tween = gsap.fromTo(element, 
            { scale: scale[0], rotation: rotation[0], borderRadius: borderRadius[0] },
            {
                scale: scale[1],
                rotation: rotation[1],
                borderRadius: borderRadius[1],
                ease: 'none',
                scrollTrigger: {
                    trigger: element,
                    start,
                    end,
                    scrub,
                },
            }
        );

        triggers.push(tween.scrollTrigger);
        return tween;
    };

    const createTimeline = (trigger, options = {}) => {
        const {
            start = 'top center',
            end = 'bottom center',
            scrub = 1,
        } = options;

        const tl = gsap.timeline({
            scrollTrigger: {
                trigger,
                start,
                end,
                scrub,
            },
        });

        triggers.push(tl.scrollTrigger);
        return tl;
    };

    const cleanup = () => {
        triggers.forEach(st => st.kill());
        triggers.length = 0;
    };

    return {
        createParallax,
        createPin,
        createReveal,
        createMorph,
        createTimeline,
        cleanup,
    };
}
