<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);

const navItems = [
    {
        label: 'Recepción',
        icon: 'car',
        route: 'dashboard',
    },
    {
        label: 'Tablero',
        icon: 'clipboard',
        route: 'dashboard',
    },
    {
        label: 'Inventario',
        icon: 'box',
        route: 'dashboard',
    },
    {
        label: 'Clientes',
        icon: 'users',
        route: 'dashboard',
    },
];

const icons = {
    car: `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8l2-2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 6h2l4 4v6h-6V6z"/></svg>`,
    clipboard: `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>`,
    box: `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>`,
    users: `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>`,
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">

        <!-- ===================== -->
        <!-- SIDEBAR — Desktop     -->
        <!-- ===================== -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r border-gray-200">
            <!-- Branding -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-gray-100">
                <div class="w-9 h-9 rounded-lg bg-orange-500 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-base font-bold text-gray-900 leading-tight">Feeto</p>
                    <p class="text-xs text-gray-500 leading-tight">Gestión de Taller</p>
                </div>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.label"
                    :href="route(item.route)"
                    class="flex items-center gap-3 px-4 min-h-[60px] rounded-xl text-lg font-medium transition-colors duration-150"
                    :class="route().current(item.route)
                        ? 'bg-orange-50 text-orange-600'
                        : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'"
                >
                    <span v-html="icons[item.icon]" class="flex-shrink-0 w-6 h-6" />
                    {{ item.label }}
                </Link>
            </nav>

            <!-- User + Logout -->
            <div class="border-t border-gray-100 p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-bold text-gray-700">{{ user?.name?.charAt(0)?.toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ user?.name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ user?.email }}</p>
                    </div>
                </div>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Cerrar sesión
                </Link>
            </div>
        </aside>

        <!-- ===================== -->
        <!-- MAIN CONTENT AREA    -->
        <!-- ===================== -->
        <div class="flex-1 flex flex-col lg:ml-64">
            <!-- Header mobile -->
            <header class="lg:hidden flex items-center justify-between bg-white border-b border-gray-200 px-4 h-14">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-md bg-orange-500 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <span class="text-base font-bold text-gray-900">Feeto</span>
                </div>
                <div class="text-sm font-medium text-gray-500">{{ user?.name }}</div>
            </header>

            <!-- Page heading slot -->
            <div v-if="$slots.header" class="bg-white border-b border-gray-200 px-4 py-4 lg:px-8">
                <slot name="header" />
            </div>

            <!-- Main content -->
            <main class="flex-1 px-4 py-6 lg:px-8 pb-24 lg:pb-6">
                <slot />
            </main>
        </div>

        <!-- ================================ -->
        <!-- BOTTOM BAR — Mobile only         -->
        <!-- ================================ -->
        <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 z-50">
            <div class="flex">
                <Link
                    v-for="item in navItems"
                    :key="item.label"
                    :href="route(item.route)"
                    class="flex-1 flex flex-col items-center justify-center min-h-[64px] gap-1 text-xs font-medium transition-colors duration-150"
                    :class="route().current(item.route)
                        ? 'text-orange-600 bg-orange-50'
                        : 'text-gray-600 hover:text-gray-900'"
                >
                    <span v-html="icons[item.icon]" class="w-6 h-6" />
                    {{ item.label }}
                </Link>
            </div>
        </nav>

    </div>
</template>
