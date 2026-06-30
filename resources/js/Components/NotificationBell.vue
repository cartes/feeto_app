<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useTenantRouting } from '@/composables/useTenantRouting';

const { tenantRouteParams } = useTenantRouting();

const notifications = ref([]);
const unreadCount = ref(0);
const open = ref(false);

const bellRef = ref(null);

async function fetchNotifications() {
    if (!tenantRouteParams.value.tenantBySlug) return;
    try {
        const res = await fetch(route('notifications.index', tenantRouteParams.value));
        const data = await res.json();
        notifications.value = data.notifications ?? [];
        unreadCount.value = data.unread_count ?? 0;
    } catch {}
}

async function markRead(notification) {
    if (notification.read_at) return;
    try {
        await fetch(route('notifications.read', { ...tenantRouteParams.value, notification: notification.id }), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
        });
        notification.read_at = new Date().toISOString();
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch {}
}

async function markAllRead() {
    try {
        await fetch(route('notifications.read-all', tenantRouteParams.value), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
        });
        notifications.value.forEach(n => { n.read_at = new Date().toISOString(); });
        unreadCount.value = 0;
    } catch {}
}

function handleClickOutside(e) {
    if (bellRef.value && !bellRef.value.contains(e.target)) {
        open.value = false;
    }
}

function toggleOpen() {
    open.value = !open.value;
    if (open.value) fetchNotifications();
}

function formatTime(ts) {
    if (!ts) return '';
    const date = new Date(ts);
    const diff = Math.floor((Date.now() - date.getTime()) / 1000);
    if (diff < 60) return 'hace un momento';
    if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `hace ${Math.floor(diff / 3600)} h`;
    return date.toLocaleDateString('es-CL', { day: 'numeric', month: 'short' });
}

const notificationIconColor = (type) => {
    if (type === 'whatsapp_inquiry') return '#25D366';
    if (type === 'stock_depleted') return '#EF4444';
    if (type === 'minimum_margin_warning') return '#EF4444';
    if (type === 'safety_stock_reached') return '#F97316';
    return '#FF7A00';
};

let interval = null;

onMounted(() => {
    fetchNotifications();
    interval = setInterval(fetchNotifications, 30000);
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    clearInterval(interval);
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="bellRef" class="relative">
        <button
            @click="toggleOpen"
            class="relative w-10 h-10 lg:w-12 lg:h-12 rounded-full bg-white flex items-center justify-center shadow-sm hover:shadow-md transition-shadow"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-rose-500 text-white text-[10px] font-black rounded-full flex items-center justify-center px-1 shadow"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 scale-95 translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1"
        >
            <div
                v-if="open"
                class="absolute right-0 mt-3 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 z-50 overflow-hidden"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-50">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-500">Notificaciones</p>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllRead"
                        class="text-[10px] font-bold text-[#FF7A00] hover:underline"
                    >
                        Marcar todo leído
                    </button>
                </div>

                <!-- List -->
                <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                    <div
                        v-for="n in notifications"
                        :key="n.id"
                        @click="markRead(n)"
                        class="flex items-start gap-3 px-4 py-3 cursor-pointer transition-colors"
                        :class="n.read_at ? 'bg-white' : 'bg-emerald-50/40 hover:bg-emerald-50'"
                    >
                        <div class="mt-0.5 w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                            :style="`background-color: ${notificationIconColor(n.type)}20`">
                            <!-- WhatsApp -->
                            <svg v-if="n.type === 'whatsapp_inquiry'" class="w-4 h-4" viewBox="0 0 24 24" fill="#25D366">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <!-- Stock agotado / margen bajo (rojo) -->
                            <svg v-else-if="n.type === 'stock_depleted' || n.type === 'minimum_margin_warning'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="#EF4444" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <!-- Stock mínimo (naranja) -->
                            <svg v-else-if="n.type === 'safety_stock_reached'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="#F97316" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <!-- Nueva OT -->
                            <svg v-else-if="n.type === 'work_order_created'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="#FF7A00" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <!-- Nueva cita -->
                            <svg v-else-if="n.type === 'new_appointment'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="#FF7A00" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <!-- Default -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#FF7A00]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-bold text-gray-800 truncate">{{ n.title }}</p>
                                <span v-if="!n.read_at" class="w-2 h-2 rounded-full bg-rose-500 flex-shrink-0"></span>
                            </div>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-relaxed">{{ n.body }}</p>
                            <p class="text-[10px] text-gray-400 mt-1 font-medium">{{ formatTime(n.created_at) }}</p>
                        </div>
                    </div>

                    <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-xs text-gray-400 font-medium">
                        No hay notificaciones.
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
