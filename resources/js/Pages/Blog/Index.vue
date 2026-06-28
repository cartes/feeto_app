<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import LoginModal from '@/Components/LoginModal.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    posts: Array,
    seo: Object,
    categories: Array,
    activeCategory: Object,
});

const showLoginModal = ref(false);

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('es-CL', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};
</script>

<template>
    <Head>
        <title>{{ seo.title }}</title>
        <meta name="description" :content="seo.description">
        <meta name="robots" content="index, follow">
        <link rel="canonical" :href="seo.canonical_url">

        <!-- Open Graph -->
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="TallerFlow">
        <meta property="og:locale" content="es_CL">
        <meta property="og:title" :content="seo.title">
        <meta property="og:description" :content="seo.description">
        <meta property="og:url" :content="seo.canonical_url">
        <meta property="og:image" :content="seo.og_image">
        <meta property="og:image:alt" :content="seo.og_image_alt">
        <meta property="og:image:width" :content="String(seo.og_image_width)">
        <meta property="og:image:height" :content="String(seo.og_image_height)">

        <!-- Twitter Card -->
        <meta name="twitter:card" :content="seo.twitter_card">
        <meta name="twitter:site" content="@tallerflow">
        <meta name="twitter:title" :content="seo.title">
        <meta name="twitter:description" :content="seo.description">
        <meta name="twitter:image" :content="seo.og_image">

        <!-- JSON-LD Schema -->
        <component
            v-for="(schema, i) in seo.schema"
            :key="i"
            :is="'script'"
            type="application/ld+json"
            v-text="JSON.stringify(schema)"
        />
    </Head>

    <PublicNav :can-login="true" active-section="blog" />

    <div class="min-h-screen bg-slate-50 font-sans antialiased flex flex-col justify-between">
        <main class="flex-grow pt-28 pb-20">
            <!-- Hero Header -->
            <div class="max-w-5xl mx-auto px-6 lg:px-8 text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-[#FF7A00] mb-3 block">Recursos y Consejos</span>
                
                <template v-if="activeCategory">
                    <h1 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight mb-4">
                        Categoría: <em class="text-[#FF7A00] not-italic">{{ activeCategory.name }}</em>
                    </h1>
                    <p class="text-slate-500 max-w-2xl mx-auto text-base leading-relaxed">
                        Explora recursos, consejos y guías sobre {{ activeCategory.name.toLowerCase() }} para optimizar y hacer crecer tu taller mecánico.
                    </p>
                </template>
                <template v-else>
                    <h1 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight mb-4">
                        Blog de <em class="text-[#FF7A00] not-italic">TallerFlow</em>
                    </h1>
                    <p class="text-slate-500 max-w-2xl mx-auto text-base leading-relaxed">
                        Aprende prácticas para optimizar tiempos, fidelizar clientes y aumentar la rentabilidad de tu taller mecánico en Chile.
                    </p>
                </template>
            </div>

            <!-- Category Filters -->
            <div v-if="categories && categories.length > 0" class="max-w-5xl mx-auto px-6 lg:px-8 mb-12">
                <div class="flex items-center justify-center flex-wrap gap-2">
                    <Link
                        :href="route('blog.index')"
                        class="px-4 py-2 rounded-full text-xs font-bold transition-all shadow-sm duration-200 border"
                        :class="!activeCategory
                            ? 'bg-slate-900 border-slate-900 text-white'
                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300'"
                    >
                        Todos
                    </Link>
                    <Link
                        v-for="cat in categories"
                        :key="cat.id"
                        :href="route('blog.category', cat.slug)"
                        class="px-4 py-2 rounded-full text-xs font-bold transition-all shadow-sm duration-200 border"
                        :class="activeCategory?.id === cat.id
                            ? 'text-white'
                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300'"
                        :style="activeCategory?.id === cat.id ? `background-color: ${cat.color}; border-color: ${cat.color}` : ''"
                    >
                        {{ cat.name }}
                    </Link>
                </div>
            </div>

            <!-- List of posts -->
            <div class="max-w-5xl mx-auto px-6 lg:px-8">
                <div v-if="posts.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <article
                        v-for="post in posts"
                        :key="post.id"
                        class="bg-white rounded-3xl overflow-hidden shadow-sm border border-slate-100 flex flex-col justify-between hover:shadow-md hover:-translate-y-1 transition duration-300 group"
                    >
                        <div>
                            <!-- Post Image -->
                            <div class="aspect-[16/9] overflow-hidden bg-slate-100 relative">
                                <img
                                    v-if="post.featured_image_url"
                                    :src="post.featured_image_url"
                                    :alt="post.featured_media?.alt_text || post.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100">
                                    <svg class="w-12 h-12 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                    </svg>
                                </div>
                            </div>
                            <!-- Post Details -->
                            <div class="p-6">
                                <div class="flex items-center gap-3 text-xs text-slate-400 mb-3 flex-wrap">
                                    <span v-if="!post.is_published" class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-xs font-semibold uppercase tracking-wider">
                                        Borrador
                                    </span>
                                    <span v-if="!post.is_published">•</span>
                                    <span>{{ formatDate(post.published_at ?? post.created_at) }}</span>
                                    <span>•</span>
                                    <span>Por TallerFlow</span>
                                    <template v-if="post.categories?.length">
                                        <span>•</span>
                                        <Link
                                            v-for="cat in post.categories"
                                            :key="cat.id"
                                            :href="route('blog.category', cat.slug)"
                                            class="px-2 py-0.5 rounded-full text-white text-[10px] font-bold tracking-wide transition-opacity hover:opacity-90"
                                            :style="`background-color: ${cat.color}`"
                                        >{{ cat.name }}</Link>
                                    </template>
                                </div>
                                <h2 class="text-lg font-bold text-slate-900 mb-2 line-clamp-2 group-hover:text-[#FF7A00] transition duration-200">
                                    <Link :href="route('blog.show', post.slug)">
                                        {{ post.title }}
                                    </Link>
                                </h2>
                                <p class="text-xs text-slate-500 leading-relaxed line-clamp-3">
                                    {{ post.summary }}
                                </p>
                            </div>
                        </div>
                        <div class="px-6 pb-6 pt-2">
                            <Link
                                :href="route('blog.show', post.slug)"
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#FF7A00] hover:text-[#CC6200] transition"
                            >
                                Leer artículo
                                <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </Link>
                        </div>
                    </article>
                </div>
                <div v-else class="text-center py-20 bg-white rounded-3xl border border-slate-100 shadow-sm">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5-6h7.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/>
                    </svg>
                    <p class="text-slate-500 font-medium">No se encontraron artículos en esta categoría.</p>
                </div>
            </div>
        </main>

        <PublicFooter />

        <LoginModal :show="showLoginModal" @close="showLoginModal = false" />
    </div>
</template>
