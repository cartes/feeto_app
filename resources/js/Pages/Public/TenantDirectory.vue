<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicNav from '@/Components/PublicNav.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    tenants: { type: Array, default: () => [] },
    latestTenants: { type: Array, default: () => [] },
    comunas: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    seo: { type: Object, default: () => ({}) },
});
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

    <PublicNav :can-login="true" />

    <div class="min-h-screen bg-slate-50 font-sans antialiased flex flex-col justify-between">
        <main class="flex-grow pt-28 pb-20">
            <!-- Hero Header -->
            <div class="max-w-5xl mx-auto px-6 lg:px-8 text-center mb-12">
                <span class="text-xs font-bold uppercase tracking-widest text-[#FF7A00] mb-3 block">Directorio TallerFlow</span>
                <h1 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight mb-4">
                    Talleres Mecánicos <em class="text-[#FF7A00] not-italic">en Chile</em>
                </h1>
                <p class="text-slate-500 max-w-2xl mx-auto text-base leading-relaxed">
                    Encuentra talleres mecánicos confiables cerca de ti y agenda tu hora directamente con cada taller.
                </p>
            </div>

            <!-- Comuna Filters -->
            <div v-if="comunas.length > 0" class="max-w-5xl mx-auto px-6 lg:px-8 mb-12">
                <div class="flex items-center justify-center flex-wrap gap-2">
                    <Link
                        :href="route('talleres.index')"
                        class="px-4 py-2 rounded-full text-xs font-bold transition-all shadow-sm duration-200 border"
                        :class="!filters.comuna
                            ? 'bg-slate-900 border-slate-900 text-white'
                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300'"
                    >
                        Todas las comunas
                    </Link>
                    <Link
                        v-for="comuna in comunas"
                        :key="comuna"
                        :href="route('talleres.index', { comuna })"
                        class="px-4 py-2 rounded-full text-xs font-bold transition-all shadow-sm duration-200 border"
                        :class="filters.comuna === comuna
                            ? 'bg-[#FF7A00] border-[#FF7A00] text-white'
                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300'"
                    >
                        {{ comuna }}
                    </Link>
                </div>
            </div>

            <!-- Latest Tenants -->
            <div v-if="!filters.comuna && latestTenants.length > 0" class="max-w-5xl mx-auto px-6 lg:px-8 mb-16">
                <h2 class="text-xl font-bold text-slate-900 mb-6">Talleres recién incorporados</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link
                        v-for="tenant in latestTenants"
                        :key="tenant.id"
                        :href="tenant.landing_url"
                        class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 hover:shadow-md hover:-translate-y-1 transition duration-300 group"
                    >
                        <h3 class="text-base font-bold text-slate-900 mb-1 group-hover:text-[#FF7A00] transition duration-200">
                            {{ tenant.name }}
                        </h3>
                        <p v-if="tenant.comuna" class="text-xs text-slate-400 font-medium mb-2">{{ tenant.comuna }}</p>
                        <p v-if="tenant.address" class="text-xs text-slate-500 leading-relaxed line-clamp-2">{{ tenant.address }}</p>
                    </Link>
                </div>
            </div>

            <!-- Main Listing -->
            <div class="max-w-5xl mx-auto px-6 lg:px-8">
                <h2 class="text-xl font-bold text-slate-900 mb-6">
                    {{ filters.comuna && !filters.fallback_to_all ? `Talleres en ${filters.comuna}` : 'Todos los talleres' }}
                </h2>

                <div
                    v-if="filters.fallback_to_all"
                    class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800"
                >
                    No encontramos talleres con ubicacion verificada en {{ filters.comuna }} por ahora. Te mostramos todos los talleres disponibles.
                </div>

                <div v-if="tenants.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="tenant in tenants"
                        :key="tenant.id"
                        class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 hover:shadow-md hover:-translate-y-1 transition duration-300 group flex flex-col justify-between"
                    >
                        <div>
                            <h3 class="text-base font-bold text-slate-900 mb-1 group-hover:text-[#FF7A00] transition duration-200">
                                <Link :href="tenant.landing_url">{{ tenant.name }}</Link>
                            </h3>
                            <p v-if="tenant.comuna" class="text-xs text-slate-400 font-medium mb-2">{{ tenant.comuna }}</p>
                            <p v-if="tenant.address" class="text-xs text-slate-500 leading-relaxed line-clamp-2 mb-4">{{ tenant.address }}</p>
                        </div>
                        <div class="flex items-center gap-4 pt-2">
                            <Link
                                :href="tenant.landing_url"
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#FF7A00] hover:text-[#CC6200] transition"
                            >
                                Agendar hora
                                <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </Link>
                            <a
                                v-if="tenant.website_url"
                                :href="tenant.website_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-xs font-medium text-slate-400 hover:text-slate-600 transition"
                            >
                                Sitio web
                            </a>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-20 bg-white rounded-3xl border border-slate-100 shadow-sm">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                    <p class="text-slate-500 font-medium">
                        {{ filters.comuna ? 'No encontramos talleres en esta comuna por ahora.' : 'Aún no hay talleres disponibles.' }}
                    </p>
                </div>
            </div>
        </main>

        <PublicFooter />
    </div>
</template>
