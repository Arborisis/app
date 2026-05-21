<script setup>
import { computed } from 'vue';

const props = defineProps({
    content: { type: String, required: true },
});

const processedContent = computed(() => {
    let html = props.content;

    // Transforme les liens dynamiques Arborisis en liens Inertia
    html = html.replace(
        /<a[^>]*data-arborisis-type="sound"[^>]*data-arborisis-id="(\d+)"[^>]*>/g,
        '<a data-inertia-link href="/sounds/$1" class="text-arbor-emerald hover:text-arbor-emerald-dark hover:underline transition-colors font-medium">'
    );

    html = html.replace(
        /<a[^>]*data-arborisis-type="creator"[^>]*data-arborisis-id="(\d+)"[^>]*>/g,
        '<a data-inertia-link href="/creators/$1" class="text-arbor-emerald hover:text-arbor-emerald-dark hover:underline transition-colors font-medium">'
    );

    html = html.replace(
        /<a[^>]*data-arborisis-type="map"[^>]*>/g,
        '<a data-inertia-link href="/map" class="text-arbor-emerald hover:text-arbor-emerald-dark hover:underline transition-colors font-medium">'
    );

    html = html.replace(
        /<a[^>]*data-arborisis-type="arborisis-map"[^>]*>/g,
        '<a data-inertia-link href="/arborisis-map" class="text-arbor-emerald hover:text-arbor-emerald-dark hover:underline transition-colors font-medium">'
    );

    return html;
});
</script>

<template>
    <div class="blog-content" v-html="processedContent" />
</template>

<style scoped>
.blog-content {
    font-size: 1.125rem;
    line-height: 1.85;
    color: #8FA68E;
}

.blog-content :deep(h2) {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.875rem;
    font-weight: 700;
    color: #F3F0E7;
    margin-top: 3.5rem;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(52, 211, 153, 0.15);
    letter-spacing: -0.01em;
}

.blog-content :deep(h3) {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: #F3F0E7;
    margin-top: 2.5rem;
    margin-bottom: 1rem;
}

.blog-content :deep(p) {
    margin-bottom: 1.75rem;
    text-align: justify;
    hyphens: auto;
}

.blog-content :deep(p:first-of-type) {
    font-size: 1.25rem;
    line-height: 1.75;
    color: #a8b8a7;
    margin-bottom: 2.5rem;
}

.blog-content :deep(em) {
    color: #d4c4a8;
    font-style: italic;
}

.blog-content :deep(strong) {
    color: #F3F0E7;
    font-weight: 600;
}

.blog-content :deep(blockquote) {
    position: relative;
    margin: 2.5rem 0;
    padding: 1.5rem 2rem;
    background: rgba(255, 255, 255, 0.03);
    border-left: 3px solid rgba(52, 211, 153, 0.4);
    border-radius: 0 12px 12px 0;
    font-style: italic;
}

.blog-content :deep(blockquote::before) {
    content: '"';
    position: absolute;
    top: 0.5rem;
    left: 1rem;
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 3rem;
    color: rgba(52, 211, 153, 0.2);
    line-height: 1;
}

.blog-content :deep(blockquote p) {
    color: #c8b896;
    margin-bottom: 0;
    font-size: 1.125rem;
    line-height: 1.75;
}

.blog-content :deep(ul) {
    margin-bottom: 2rem;
    padding-left: 1.5rem;
}

.blog-content :deep(ul li) {
    position: relative;
    padding-left: 1.25rem;
    margin-bottom: 0.75rem;
    line-height: 1.7;
}

.blog-content :deep(ul li::marker) {
    color: #34D399;
}

.blog-content :deep(a) {
    color: #34D399;
    text-decoration: none;
    border-bottom: 1px solid rgba(52, 211, 153, 0.25);
    transition: all 0.2s ease;
}

.blog-content :deep(a:hover) {
    color: #10B981;
    border-bottom-color: #10B981;
}

.blog-content :deep(hr) {
    border: none;
    height: 1px;
    background: rgba(52, 211, 153, 0.15);
    margin: 3rem auto;
    max-width: 200px;
}

.blog-content :deep(figure) {
    margin: 2.5rem 0;
}

.blog-content :deep(figcaption) {
    text-align: center;
    font-size: 0.875rem;
    color: #6b7a6a;
    margin-top: 0.75rem;
    font-style: italic;
}

/* Lettrine sur le premier paragraphe de l'article */
.blog-content :deep(article > p:first-of-type)::first-letter {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 3.5rem;
    font-weight: 700;
    float: left;
    line-height: 0.8;
    padding-right: 0.75rem;
    padding-top: 0.25rem;
    color: #34D399;
}
</style>
