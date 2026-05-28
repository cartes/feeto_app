<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import LoginModal from '@/Components/LoginModal.vue';

const props = defineProps({
    canLogin: { type: Boolean, default: false },
    activeSection: { type: String, default: null },
});

const page = usePage();
const mobileMenuOpen = ref(false);
const showLoginModal = ref(false);

const currentPath = computed(() => (page.url ?? '').split('?')[0]);
const isHome = computed(() => currentPath.value === '/');
const isPricing = computed(() => currentPath.value === '/precios');
const isTrial = computed(() => currentPath.value === '/trial' || currentPath.value === '/trial/gracias');
const isBlog = computed(() => currentPath.value.startsWith('/blog'));

const isActive = (item) => {
    if (item === 'features') return isHome.value && props.activeSection === 'features';
    if (item === 'benefits') return isHome.value && (props.activeSection === 'benefits' || props.activeSection === 'pricing');
    if (item === 'planes') return isPricing.value;
    if (item === 'blog') return isBlog.value;
    return false;
};

onMounted(() => {
    if (new URLSearchParams(window.location.search).get('login')) {
        showLoginModal.value = true;
    }
});
</script>

<template>
    <div class="pub-nav-wrap">
        <nav class="pub-nav">
            <!-- Logo -->
            <Link :href="route('home')" class="pub-brand">
                <ApplicationLogo class="pub-brand-mark" alt="Taller Flow" />
                Taller<span class="pub-brand-flow">Flow</span>
            </Link>

            <!-- Desktop links -->
            <div class="pub-nav-links">
                <a href="/#features" :class="['pub-link', { 'pub-link-active': isActive('features') }]">Características</a>
                <a href="/#benefits" :class="['pub-link', { 'pub-link-active': isActive('benefits') }]">Beneficios</a>
                <Link :href="route('pricing')" :class="['pub-link', { 'pub-link-active': isActive('planes') }]">Planes</Link>
                <Link :href="route('blog.index')" :class="['pub-link', { 'pub-link-active': isActive('blog') }]">Blog</Link>
            </div>

            <!-- Desktop CTA -->
            <div class="pub-nav-cta">
                <template v-if="$page.props.auth?.user">
                    <Link :href="route('dashboard')" class="pub-btn-ghost">Dashboard</Link>
                </template>
                <template v-else>
                    <button v-if="canLogin" class="pub-btn-ghost" type="button" @click="showLoginModal = true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                        Iniciar sesión
                    </button>
                    <Link v-if="!isTrial" :href="route('trial.create')" class="pub-btn-accent">Probar gratis</Link>
                </template>

                <!-- Mobile toggle -->
                <button class="pub-mobile-toggle" type="button" :aria-expanded="mobileMenuOpen" aria-label="Abrir menú" @click="mobileMenuOpen = !mobileMenuOpen">
                    <svg v-if="!mobileMenuOpen" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/>
                    </svg>
                    <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/>
                    </svg>
                </button>
            </div>
        </nav>

        <!-- Mobile menu -->
        <div v-if="mobileMenuOpen" class="pub-mobile-menu" id="pub-mobile-menu">
            <div class="pub-mobile-links">
                <a href="/#features" class="pub-mobile-link" :class="{ 'pub-mobile-link-active': isActive('features') }" @click="mobileMenuOpen = false">Características</a>
                <a href="/#benefits" class="pub-mobile-link" :class="{ 'pub-mobile-link-active': isActive('benefits') }" @click="mobileMenuOpen = false">Beneficios</a>
                <Link :href="route('pricing')" class="pub-mobile-link" :class="{ 'pub-mobile-link-active': isActive('planes') }" @click="mobileMenuOpen = false">Planes y Precios</Link>
                <Link :href="route('blog.index')" class="pub-mobile-link" :class="{ 'pub-mobile-link-active': isActive('blog') }" @click="mobileMenuOpen = false">Blog</Link>
            </div>
            <div class="pub-mobile-actions">
                <template v-if="$page.props.auth?.user">
                    <Link :href="route('dashboard')" class="pub-mobile-btn pub-mobile-btn-ghost" @click="mobileMenuOpen = false">Dashboard</Link>
                </template>
                <template v-else>
                    <button v-if="canLogin" type="button" class="pub-mobile-btn pub-mobile-btn-ghost" @click="mobileMenuOpen = false; showLoginModal = true">Iniciar sesión</button>
                    <Link v-if="!isTrial" :href="route('trial.create')" class="pub-mobile-btn" @click="mobileMenuOpen = false">Probar gratis</Link>
                </template>
            </div>
        </div>
    </div>

    <LoginModal :show="showLoginModal" @close="showLoginModal = false" />
</template>

<style scoped>
.pub-nav-wrap {
    position: fixed;
    top: 18px;
    left: 18px;
    right: 18px;
    z-index: 9999;
}

.pub-nav {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 8px;
    padding: 8px 8px 8px 18px;
    border: 1px solid rgba(255, 255, 255, 0.24);
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(26, 28, 34, 0.72) 0%, rgba(72, 77, 90, 0.60) 50%, rgba(26, 28, 34, 0.72) 100%);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12), 0 10px 30px rgba(0, 0, 0, 0.28);
    backdrop-filter: blur(20px) saturate(140%);
    -webkit-backdrop-filter: blur(20px) saturate(140%);
}

/* Brand */
.pub-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #fff;
    font-size: 18px;
    font-weight: 900;
    letter-spacing: -0.01em;
    text-decoration: none;
    white-space: nowrap;
}
.pub-brand-flow { color: #FF7A00; }

.pub-brand-mark {
    display: block;
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    border-radius: 10px;
    object-fit: cover;
}

/* Desktop links */
.pub-nav-links {
    display: flex;
    gap: 4px;
    margin: 0 auto;
    padding: 0 12px;
    white-space: nowrap;
}

.pub-link {
    padding: 10px 16px;
    border-radius: 999px;
    color: rgba(255, 255, 255, 0.76);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: color 0.15s ease, background 0.15s ease;
}
.pub-link:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
}
.pub-link-active {
    color: #fff;
    background: rgba(255, 255, 255, 0.12);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
}

/* CTA area */
.pub-nav-cta {
    display: flex;
    margin-left: auto;
    flex-shrink: 0;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.pub-btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 18px;
    border: 1px solid rgba(255, 255, 255, 0.24);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.9);
    cursor: pointer;
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: background 0.15s ease;
}
.pub-btn-ghost:hover { background: rgba(255, 255, 255, 0.09); }

.pub-btn-accent {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 20px;
    border: none;
    border-radius: 999px;
    background: #FF7A00;
    color: #1a0e00;
    cursor: pointer;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.15s ease, transform 0.15s ease;
}
.pub-btn-accent:hover { background: #CC6200; transform: translateY(-1px); }

/* Mobile toggle */
.pub-mobile-toggle {
    display: none;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    padding: 0;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.06);
    color: #fff;
    cursor: pointer;
}

/* Mobile menu */
.pub-mobile-menu {
    width: 100%;
    margin-top: 10px;
    padding: 14px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 24px;
    background: linear-gradient(180deg, rgba(13, 15, 20, 0.97) 0%, rgba(9, 10, 13, 0.95) 100%);
    box-shadow: 0 18px 48px rgba(0, 0, 0, 0.38);
    backdrop-filter: blur(24px) saturate(140%);
    -webkit-backdrop-filter: blur(24px) saturate(140%);
}

.pub-mobile-links,
.pub-mobile-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.pub-mobile-actions {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.pub-mobile-link {
    display: flex;
    align-items: center;
    min-height: 46px;
    padding: 12px 16px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.04);
    color: rgba(255, 255, 255, 0.85);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: background 0.15s;
}
.pub-mobile-link:hover { background: rgba(255, 255, 255, 0.07); }
.pub-mobile-link-active {
    background: rgba(255, 181, 71, 0.18);
    color: #fff;
}

.pub-mobile-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 12px 16px;
    border: none;
    border-radius: 16px;
    background: #FF7A00;
    color: #1a0e00;
    cursor: pointer;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
    text-decoration: none;
    transition: background 0.15s;
}
.pub-mobile-btn:hover { background: #CC6200; }
.pub-mobile-btn-ghost {
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.16);
}
.pub-mobile-btn-ghost:hover { background: rgba(255, 255, 255, 0.10); }

/* Responsive */
@media (max-width: 900px) {
    .pub-nav-wrap {
        top: 12px;
        left: 12px;
        right: 12px;
    }
    .pub-nav {
        justify-content: space-between;
        padding: 10px 10px 10px 14px;
        border-radius: 20px;
    }
    .pub-nav-links,
    .pub-btn-ghost,
    .pub-btn-accent { display: none; }
    .pub-brand { font-size: 16px; }
    .pub-brand-mark { width: 30px; height: 30px; }
    .pub-nav-cta { margin-left: auto; justify-content: flex-end; }
    .pub-mobile-toggle { display: flex; }
}
</style>
