<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PublicNav from '@/Components/PublicNav.vue';
import LoginModal from '@/Components/LoginModal.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    post: Object,
    seo: Object,
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
    </Head>

    <PublicNav :can-login="true" active-section="blog" />

    <div class="min-h-screen bg-slate-50 font-sans antialiased flex flex-col justify-between">
        <main class="flex-grow pt-28 pb-20">
            <article class="max-w-3xl mx-auto px-6 lg:px-8">
                <!-- Back Link -->
                <div class="mb-8">
                    <Link
                        :href="route('blog.index')"
                        class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-slate-900 transition group"
                    >
                        <svg class="w-3.5 h-3.5 transform group-hover:-translate-x-0.5 transition duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                        Volver al blog
                    </Link>
                </div>

                <!-- Header -->
                <header class="mb-10">
                    <div class="flex items-center gap-3 text-xs text-slate-400 mb-4">
                        <span>{{ formatDate(post.published_at ?? post.created_at) }}</span>
                        <span>•</span>
                        <span>Por TallerFlow</span>
                    </div>
                    <h1 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight mb-6">
                        {{ post.title }}
                    </h1>
                    <p class="text-lg text-slate-500 leading-relaxed font-medium">
                        {{ post.summary }}
                    </p>
                </header>

                <!-- Featured Image -->
                <div v-if="post.featured_image" class="aspect-[21/9] rounded-3xl overflow-hidden shadow-sm border border-slate-100 mb-12">
                    <img :src="post.featured_image" :alt="post.title" class="w-full h-full object-cover" />
                </div>

                <!-- Content -->
                <div class="blog-content bg-white p-8 md:p-12 rounded-3xl border border-slate-100 shadow-sm mb-12" v-html="post.content"></div>

                <!-- Footer Call to Action -->
                <div class="bg-gray-900 rounded-3xl p-8 md:p-10 flex flex-col md:flex-row items-center justify-between gap-8">
                    <div>
                        <h3 class="text-xl font-black text-white leading-tight">
                            ¿Aún no eres parte de TallerFlow?
                        </h3>
                        <p class="text-gray-400 mt-2 text-xs">
                            Prueba gratis por 14 días. Digitaliza tu taller hoy.
                        </p>
                    </div>
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="flex-shrink-0 inline-flex items-center gap-2 bg-[#FF7A00] hover:bg-[#CC6200] text-white font-bold px-6 py-3 rounded-2xl shadow-lg shadow-orange-500/20 transition-all active:scale-[0.98] text-sm whitespace-nowrap"
                    >
                        Ir al Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="route('trial.create')"
                        class="flex-shrink-0 inline-flex items-center gap-2 bg-[#FF7A00] hover:bg-[#CC6200] text-white font-bold px-6 py-3 rounded-2xl shadow-lg shadow-orange-500/20 transition-all active:scale-[0.98] text-sm whitespace-nowrap"
                    >
                        Probar Gratis Ahora
                    </Link>
                </div>
            </article>
        </main>

        <footer class="bg-white border-t border-slate-100 py-10 mt-auto">
            <div class="max-w-5xl mx-auto px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-2">
                    <ApplicationLogo class="h-7 w-7 rounded-lg" />
                    <span class="text-base font-black text-slate-900">TallerFlow</span>
                </div>
                <nav class="flex items-center gap-6 flex-wrap justify-center">
                    <a href="#" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Términos de Servicio</a>
                    <a href="#" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Política de Privacidad</a>
                    <a href="#" class="text-xs text-gray-400 hover:text-gray-600 transition-colors font-medium">Contacto</a>
                </nav>
                <p class="text-xs text-slate-400 font-medium">
                    &copy; {{ new Date().getFullYear() }} TallerFlow. Todos los derechos reservados.
                </p>
            </div>
        </footer>

        <LoginModal :show="showLoginModal" @close="showLoginModal = false" />
    </div>
</template>

<style>
.blog-content h1 { font-size: 1.85em; font-weight: 800; margin-top: 1.5em; margin-bottom: 0.6em; color: #0f172a; line-height: 1.25; }
.blog-content h2 { font-size: 1.5em; font-weight: 700; margin-top: 1.5em; margin-bottom: 0.6em; color: #0f172a; line-height: 1.3; }
.blog-content h3 { font-size: 1.25em; font-weight: 600; margin-top: 1.5em; margin-bottom: 0.6em; color: #0f172a; line-height: 1.35; }
.blog-content p { margin-top: 0; margin-bottom: 1.25em; line-height: 1.8; color: #334155; font-size: 1.05rem; }
.blog-content ul { list-style-type: disc; padding-left: 1.5em; margin-bottom: 1.25em; color: #334155; }
.blog-content ol { list-style-type: decimal; padding-left: 1.5em; margin-bottom: 1.25em; color: #334155; }
.blog-content li { margin-bottom: 0.5em; line-height: 1.7; }
.blog-content pre { background-color: #f8fafc; padding: 1.25em; border-radius: 12px; font-family: monospace; overflow-x: auto; margin-bottom: 1.25em; border: 1px solid #f1f5f9; }
.blog-content code { background-color: #f1f5f9; padding: 0.2em 0.4em; border-radius: 6px; font-family: monospace; font-size: 0.9em; color: #0f172a; }
.blog-content blockquote { border-left: 4px solid #FF7A00; padding-left: 1.25em; color: #475569; font-style: italic; margin-bottom: 1.25em; }
.blog-content img { border-radius: 16px; margin: 2em 0; max-width: 100%; height: auto; display: block; }
.blog-content strong { font-weight: 700; color: #0f172a; }
.blog-content a { color: #FF7A00; text-decoration: underline; font-weight: 600; }
.blog-content a:hover { color: #CC6200; }
</style>
