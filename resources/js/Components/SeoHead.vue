<script setup>
/**
 * Meta tags SEO / Open Graph / Twitter para páginas públicas.
 *
 * Renderiza exactamente el mismo set que `resources/views/app.blade.php`
 * emite en el servidor (marcado con el atributo `inertia`), de modo que al
 * hidratar, Inertia reemplaza los tags del servidor sin duplicarlos.
 *
 * El JSON-LD se renderiza solo en el servidor (blade) y no se toca aquí.
 */
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    seo: { type: Object, default: () => ({}) },
    fallbackTitle: { type: String, default: 'TallerFlow' },
});

const SITE_NAME = 'TallerFlow';
const LOCALE = 'es_CL';
const TWITTER_SITE = '@tallerflow';
const DEFAULT_ROBOTS = 'index, follow, max-image-preview:large, max-snippet:-1';

const hasSeo = computed(() => props.seo && Object.keys(props.seo).length > 0);
const title = computed(() => props.seo?.title || props.fallbackTitle);
const description = computed(() => props.seo?.description || '');
const canonical = computed(() => props.seo?.canonical_url || null);
const robots = computed(() => props.seo?.robots || DEFAULT_ROBOTS);
const ogType = computed(() => props.seo?.og_type || 'website');
const image = computed(() => props.seo?.og_image || null);
const imageAlt = computed(() => props.seo?.og_image_alt || title.value);
const imageWidth = computed(() => props.seo?.og_image_width ? String(props.seo.og_image_width) : null);
const imageHeight = computed(() => props.seo?.og_image_height ? String(props.seo.og_image_height) : null);
const twitterCard = computed(() => props.seo?.twitter_card || 'summary_large_image');
const publishedTime = computed(() => props.seo?.published_time || null);
const modifiedTime = computed(() => props.seo?.modified_time || null);
</script>

<template>
    <Head>
        <title>{{ title }}</title>
        <template v-if="hasSeo">
            <meta name="robots" :content="robots">
            <meta v-if="description" name="description" :content="description">
            <link v-if="canonical" rel="canonical" :href="canonical">

            <meta property="og:type" :content="ogType">
            <meta property="og:site_name" :content="SITE_NAME">
            <meta property="og:locale" :content="LOCALE">
            <meta property="og:title" :content="title">
            <meta v-if="description" property="og:description" :content="description">
            <meta v-if="canonical" property="og:url" :content="canonical">
            <template v-if="image">
                <meta property="og:image" :content="image">
                <meta property="og:image:alt" :content="imageAlt">
                <template v-if="imageWidth && imageHeight">
                    <meta property="og:image:width" :content="imageWidth">
                    <meta property="og:image:height" :content="imageHeight">
                </template>
            </template>
            <template v-if="ogType === 'article'">
                <meta v-if="publishedTime" property="article:published_time" :content="publishedTime">
                <meta v-if="modifiedTime" property="article:modified_time" :content="modifiedTime">
            </template>

            <meta name="twitter:card" :content="twitterCard">
            <meta name="twitter:site" :content="TWITTER_SITE">
            <meta name="twitter:title" :content="title">
            <meta v-if="description" name="twitter:description" :content="description">
            <template v-if="image">
                <meta name="twitter:image" :content="image">
                <meta name="twitter:image:alt" :content="imageAlt">
            </template>
        </template>
    </Head>
</template>
