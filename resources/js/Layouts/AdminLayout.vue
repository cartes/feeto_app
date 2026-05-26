<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import Toast from '@/Components/Toast.vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash ?? {});

const userMenuOpen = ref(false);
const showingNavigationDropdown = ref(false);
const userMenuRef = ref(null);
const toast = ref({ message: '', type: 'success' });

const handleClickOutside = (event) => {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        userMenuOpen.value = false;
    }
};

const showToast = (message, type = 'success') => {
    if (!message) {
        return;
    }

    toast.value = { message: '', type };

    requestAnimationFrame(() => {
        toast.value = { message, type };
    });
};

watch(
    () => [
        flash.value.success,
        flash.value.error,
        flash.value.warning,
        flash.value.info,
        flash.value.status,
    ],
    ([success, error, warning, info, status]) => {
        if (error) {
            showToast(error, 'error');

            return;
        }

        if (warning) {
            showToast(warning, 'warning');

            return;
        }

        if (success) {
            showToast(success, 'success');

            return;
        }

        if (info) {
            showToast(info, 'info');

            return;
        }

        if (status) {
            showToast(status, 'info');
        }
    },
    { immediate: true }
);

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
              <ApplicationLogo class="h-10 w-10 rounded-xl shadow-sm ring-1 ring-white/10" />
              <div class="flex items-center gap-2 text-2xl font-bold tracking-tight">
                <span class="text-white">Taller</span>
                <span class="text-amber-400">Flow</span>
              </div>
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
                :href="route('admin.payments.index')"
                :class="route().current('admin.payments.*') ? 'border-amber-500 text-white' : 'border-transparent text-slate-300 hover:border-slate-300 hover:text-white'"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
              >
                Pagos
              </Link>
              <Link
                :href="route('admin.audit.index')"
                :class="route().current('admin.audit.*') ? 'border-amber-500 text-white' : 'border-transparent text-slate-300 hover:border-slate-300 hover:text-white'"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
              >
                Auditoría
              </Link>
              <Link
                :href="route('admin.trial-requests.index')"
                :class="route().current('admin.trial-requests.*') ? 'border-amber-500 text-white' : 'border-transparent text-slate-300 hover:border-slate-300 hover:text-white'"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
              >
                Pruebas
              </Link>
              <Link
                :href="route('admin.landing-seo.index')"
                :class="route().current('admin.landing-seo.*') ? 'border-amber-500 text-white' : 'border-transparent text-slate-300 hover:border-slate-300 hover:text-white'"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
              >
                SEO
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

          <!-- Hamburger -->
          <div class="-mr-2 flex items-center sm:hidden">
            <button
              @click="showingNavigationDropdown = !showingNavigationDropdown"
              class="inline-flex items-center justify-center rounded-md p-2 text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-500 transition-colors"
            >
              <span class="sr-only">Abrir menú principal</span>
              <svg
                :class="{'hidden': showingNavigationDropdown, 'block': !showingNavigationDropdown}"
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
              </svg>
              <svg
                :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}"
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Responsive Navigation Menu -->
      <div
        :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}"
        class="sm:hidden bg-slate-900 border-t border-slate-800"
      >
        <div class="space-y-1 pb-3 pt-2 px-2">
          <Link
            :href="route('admin.dashboard')"
            :class="route().current('admin.dashboard') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            class="block rounded-md px-3 py-2 text-base font-medium transition-colors"
            @click="showingNavigationDropdown = false"
          >
            Panel
          </Link>
          <Link
            :href="route('admin.tenants.index')"
            :class="route().current('admin.tenants.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            class="block rounded-md px-3 py-2 text-base font-medium transition-colors"
            @click="showingNavigationDropdown = false"
          >
            Talleres
          </Link>
          <Link
            :href="route('admin.users.index')"
            :class="route().current('admin.users.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            class="block rounded-md px-3 py-2 text-base font-medium transition-colors"
            @click="showingNavigationDropdown = false"
          >
            Usuarios
          </Link>
          <Link
            :href="route('admin.plans.index')"
            :class="route().current('admin.plans.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            class="block rounded-md px-3 py-2 text-base font-medium transition-colors"
            @click="showingNavigationDropdown = false"
          >
            Planes
          </Link>
          <Link
            :href="route('admin.payments.index')"
            :class="route().current('admin.payments.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            class="block rounded-md px-3 py-2 text-base font-medium transition-colors"
            @click="showingNavigationDropdown = false"
          >
            Pagos
          </Link>
          <Link
            :href="route('admin.audit.index')"
            :class="route().current('admin.audit.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            class="block rounded-md px-3 py-2 text-base font-medium transition-colors"
            @click="showingNavigationDropdown = false"
          >
            Auditoría
          </Link>
          <Link
            :href="route('admin.trial-requests.index')"
            :class="route().current('admin.trial-requests.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            class="block rounded-md px-3 py-2 text-base font-medium transition-colors"
            @click="showingNavigationDropdown = false"
          >
            Pruebas
          </Link>
          <Link
            :href="route('admin.landing-seo.index')"
            :class="route().current('admin.landing-seo.*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            class="block rounded-md px-3 py-2 text-base font-medium transition-colors"
            @click="showingNavigationDropdown = false"
          >
            SEO
          </Link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-slate-800 pb-3 pt-4 px-4">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-amber-500 flex items-center justify-center text-white font-semibold ring-1 ring-white/10">
              {{ user?.name?.charAt(0).toUpperCase() }}
            </div>
            <div>
              <div class="text-base font-medium text-white">{{ user?.name }}</div>
              <div class="text-sm font-medium text-slate-400">{{ user?.email }}</div>
            </div>
          </div>

          <div class="mt-3 space-y-1">
            <Link
              :href="route('admin.profile')"
              class="block rounded-md px-3 py-2 text-base font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors"
              @click="showingNavigationDropdown = false"
            >
              Mi Perfil
            </Link>
            <Link
              :href="route('logout')"
              method="post"
              as="button"
              class="block w-full text-left rounded-md px-3 py-2 text-base font-medium text-rose-400 hover:bg-slate-800 hover:text-white transition-colors"
              @click="showingNavigationDropdown = false"
            >
              Salir
            </Link>
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

    <Toast
      :message="toast.message"
      :type="toast.type"
      @dismiss="toast.message = ''"
    />
  </div>
</template>
