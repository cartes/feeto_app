<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const page = usePage();
const marketingWhatsApp = computed(() => page.props.marketing_whatsapp ?? {});
const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="relative overflow-hidden bg-slate-50 border-t border-slate-100 pt-16 pb-28 sm:pb-24">
        <!-- Background decorative grid lines -->
        <div class="absolute inset-0 -z-10 opacity-[0.02] pointer-events-none">
            <svg class="h-full w-full" fill="none" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                    <pattern id="footer-grid" width="12" height="12" patternUnits="userSpaceOnUse">
                        <path d="M 12 0 L 0 0 0 12" fill="none" stroke="currentColor" stroke-width="0.8" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#footer-grid)" />
            </svg>
        </div>

        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-10 md:gap-8 pb-12 border-b border-slate-200/60">
                <!-- Column 1: Info/Brand -->
                <div class="md:col-span-5 space-y-4">
                    <div class="flex items-center gap-2.5">
                        <ApplicationLogo class="h-8 w-8 rounded-xl shadow-sm bg-white p-0.5 border border-slate-100" />
                        <span class="text-lg font-black tracking-tight text-slate-900">
                            Taller<span class="text-tech-orange">Flow</span>
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 max-w-sm leading-relaxed font-medium">
                        La plataforma inteligente que automatiza la recepción de vehículos, elimina la fricción administrativa y fideliza a tus clientes.
                    </p>
                </div>

                <!-- Column 2: Navigation Links -->
                <div class="md:col-span-4 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Navegación</h3>
                    <ul class="grid grid-cols-2 gap-x-4 gap-y-3">
                        <li>
                            <Link :href="route('home')" class="text-sm text-slate-500 hover:text-tech-orange transition-colors duration-200 font-medium">Inicio</Link>
                        </li>
                        <li>
                            <Link :href="route('pricing')" class="text-sm text-slate-500 hover:text-tech-orange transition-colors duration-200 font-medium">Precios</Link>
                        </li>
                        <li>
                            <Link :href="route('trial.create')" class="text-sm text-slate-500 hover:text-tech-orange transition-colors duration-200 font-medium">Prueba Gratis</Link>
                        </li>
                        <li>
                            <Link :href="route('talleres.index')" class="text-sm text-slate-500 hover:text-tech-orange transition-colors duration-200 font-medium">Talleres</Link>
                        </li>
                    </ul>
                </div>

                <!-- Column 3: Social & Contact (Separated) -->
                <div class="md:col-span-3 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Contacto & Redes</h3>
                    <div class="flex flex-col gap-3">
                        <!-- Instagram Button -->
                        <a 
                            href="https://www.instagram.com/tallerflow_app/" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            class="group flex items-center gap-3 w-fit text-sm text-slate-600 hover:text-slate-900 transition-colors duration-200"
                        >
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white shadow-sm transition-all duration-300 group-hover:scale-105 group-hover:border-pink-300 group-hover:text-pink-600">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                </svg>
                            </span>
                            <span class="font-semibold text-slate-700 group-hover:text-slate-900 transition-colors">Instagram</span>
                        </a>

                        <!-- Email Button -->
                        <a 
                            href="mailto:contacto@tallerflow.cl" 
                            class="group flex items-center gap-3 w-fit text-sm text-slate-600 hover:text-slate-900 transition-colors duration-200"
                        >
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white shadow-sm transition-all duration-300 group-hover:scale-105 group-hover:border-orange-300 group-hover:text-orange-600">
                                <svg class="w-4 h-4 fill-none stroke-current" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                            </span>
                            <span class="font-semibold text-slate-700 group-hover:text-slate-900 transition-colors">contacto@tallerflow.cl</span>
                        </a>

                        <!-- WhatsApp Button -->
                        <a 
                            v-if="marketingWhatsApp.is_ready && marketingWhatsApp.href"
                            :href="marketingWhatsApp.href" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            class="group flex items-center gap-3 w-fit text-sm text-slate-600 hover:text-slate-900 transition-colors duration-200"
                        >
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white shadow-sm transition-all duration-300 group-hover:scale-105 group-hover:border-emerald-300 group-hover:text-emerald-600">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.249 8.477 3.518 2.266 2.27 3.51 5.278 3.509 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.717-1.458L0 24zm6.075-3.522c1.636.971 3.238 1.483 4.88 1.484 5.542 0 10.051-4.475 10.054-9.982.002-2.667-1.033-5.176-2.915-7.06C16.27 3.036 13.784 2 11.996 2 6.471 2 1.961 6.475 1.958 11.983c-.001 1.767.465 3.493 1.349 5.022l-.995 3.637 3.775-.984zm11.022-7.463c-.3-.149-1.772-.875-2.046-.975-.274-.1-.474-.15-.674.15-.2.3-.773.975-.948 1.175-.175.2-.35.225-.65.075-.3-.15-1.266-.467-2.41-1.485-.89-.794-1.49-1.775-1.665-2.075-.175-.3-.019-.463.13-.61.135-.133.3-.35.45-.525.15-.175.2-.3.3-.5.1-.2.05-.375-.025-.525-.075-.15-.675-1.625-.925-2.225-.244-.589-.491-.51-.674-.52-.175-.008-.375-.01-.575-.01-.2 0-.525.075-.8.375-.275.3-1.05 1.025-1.05 2.5s1.075 2.9 1.225 3.1c.15.2 2.11 3.224 5.116 4.525.715.31 1.273.499 1.71.638.717.228 1.37.196 1.885.12.573-.085 1.772-.725 2.022-1.425.25-.7.25-1.3.175-1.425-.075-.125-.275-.2-.575-.35z"/>
                                </svg>
                            </span>
                            <span class="font-semibold text-slate-700 group-hover:text-slate-900 transition-colors">WhatsApp</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Copyright & Legal links -->
            <div class="mt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-xs text-slate-400 font-semibold md:text-left text-center">
                    &copy; {{ currentYear }} Cirpriano Software. Todos los derechos reservados.
                </div>
                <div class="flex items-center gap-6 text-xs text-slate-400 font-semibold md:justify-end justify-center pr-0 md:pr-12 lg:pr-24">
                    <a href="#" class="hover:text-slate-600 transition-colors">Términos de Servicio</a>
                    <a href="#" class="hover:text-slate-600 transition-colors">Política de Privacidad</a>
                </div>
            </div>
        </div>
    </footer>
</template>
