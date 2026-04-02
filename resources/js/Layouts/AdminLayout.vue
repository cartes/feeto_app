<script setup>
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
  <div class="min-h-screen bg-slate-50 font-sans text-slate-900">
    <!-- Top Navigation -->
    <nav class="bg-slate-900 border-b border-slate-800 shrink-0">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <Link :href="route('admin.dashboard')" class="flex items-center gap-3">
              <div class="h-8 w-8 bg-amber-500 rounded-lg flex items-center justify-center font-bold text-white shadow-sm ring-1 ring-white/10">S</div>
              <span class="text-xl font-bold tracking-tight text-white">SuperAdmin Panel</span>
            </Link>
            <div class="hidden sm:-my-px sm:ml-8 sm:flex sm:gap-x-6">
              <Link :href="route('admin.dashboard')" :class="route().current('admin.dashboard') ? 'border-amber-500 text-white' : 'border-transparent text-slate-300 hover:border-slate-300 hover:text-white'" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors">
                Panel
              </Link>
              <Link :href="route('admin.tenants.index')" :class="route().current('admin.tenants.*') ? 'border-amber-500 text-white' : 'border-transparent text-slate-300 hover:border-slate-300 hover:text-white'" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors">
                Talleres
              </Link>
              <Link :href="route('admin.users.index')" :class="route().current('admin.users.*') ? 'border-amber-500 text-white' : 'border-transparent text-slate-300 hover:border-slate-300 hover:text-white'" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors">
                Usuarios
              </Link>
            </div>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:items-center">
            <div class="flex items-center gap-4 text-sm">
              <span class="text-slate-300">{{ user?.name }}</span>
              <div class="h-8 w-8 rounded-full bg-slate-800 flex items-center justify-center text-white ring-1 ring-white/10">
                {{ user?.name?.charAt(0).toUpperCase() }}
              </div>
              <div class="h-6 w-px bg-slate-700"></div>
              <Link 
                  :href="route('logout')" 
                  method="post" 
                  as="button" 
                  class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-300 hover:text-rose-400 hover:bg-slate-800 rounded-md transition-colors"
              >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  Salir
              </Link>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <main class="py-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <slot />
      </div>
    </main>
  </div>
</template>
