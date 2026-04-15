<script setup>
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const userMenuOpen = ref(false);
const userMenuRef = ref(null);

const handleClickOutside = (event) => {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        userMenuOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
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
              <Link
                :href="route('admin.dashboard')"
                :class="route().current('admin.dashboard') ? 'border-amber-500 text-white' : 'border-transparent text-slate-300 hover:border-slate-300 hover:text-white'"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
              >
                Panel
              </Link>
              <Link
                :href="route('admin.tenants.index')"
                :class="route().current('admin.tenants.*') ? 'border-amber-500 text-white' : 'border-transparent text-slate-300 hover:border-slate-300 hover:text-white'"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
              >
                Talleres
              </Link>
              <Link
                :href="route('admin.users.index')"
                :class="route().current('admin.users.*') ? 'border-amber-500 text-white' : 'border-transparent text-slate-300 hover:border-slate-300 hover:text-white'"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
              >
                Usuarios
              </Link>
              <Link
                :href="route('admin.plans.index')"
                :class="route().current('admin.plans.*') ? 'border-amber-500 text-white' : 'border-transparent text-slate-300 hover:border-slate-300 hover:text-white'"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
              >
                Planes
              </Link>
              <Link
                :href="route('admin.audit.index')"
                :class="route().current('admin.audit.*') ? 'border-amber-500 text-white' : 'border-transparent text-slate-300 hover:border-slate-300 hover:text-white'"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
              >
                Auditoría
              </Link>
            </div>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:items-center">
            <div class="relative" ref="userMenuRef">
              <button
                @click="userMenuOpen = !userMenuOpen"
                class="flex items-center gap-3 text-sm focus:outline-none hover:opacity-80 transition-opacity"
              >
                <span class="text-slate-300">{{ user?.name }}</span>
                <div class="h-8 w-8 rounded-full bg-amber-500 flex items-center justify-center text-white font-semibold ring-1 ring-white/10">
                  {{ user?.name?.charAt(0).toUpperCase() }}
                </div>
              </button>
              <div
                v-if="userMenuOpen"
                class="absolute right-0 mt-2 w-48 rounded-lg bg-white shadow-lg ring-1 ring-black/5 z-50 overflow-hidden"
              >
                <Link
                  :href="route('admin.profile')"
                  class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                  @click="userMenuOpen = false"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  Mi Perfil
                </Link>
                <div class="border-t border-slate-100"></div>
                <Link
                  :href="route('logout')"
                  method="post"
                  as="button"
                  class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 transition-colors"
                  @click="userMenuOpen = false"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  Salir
                </Link>
              </div>
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
