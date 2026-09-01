<script setup>
import { computed } from 'vue';

const props = defineProps({
    country: {
        type: String,
        required: true,
    },
    className: {
        type: String,
        default: 'w-6 h-4',
    },
});

const code = computed(() => (props.country || '').toUpperCase().trim());
</script>

<template>
    <div
        :class="[
            'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-[4px] shadow-sm ring-1 ring-black/10 transition-transform duration-200 group-hover:scale-105',
            className
        ]"
        aria-hidden="true"
    >
        <!-- Chile (CL) -->
        <svg
            v-if="code === 'CL' || code === 'CHILE'"
            viewBox="0 0 640 480"
            class="h-full w-full object-cover"
        >
            <rect width="640" height="240" fill="#ffffff" />
            <rect y="240" width="640" height="240" fill="#d52b1e" />
            <rect width="240" height="240" fill="#0039a6" />
            <polygon
                fill="#ffffff"
                points="120,54 139,112 200,112 151,148 170,206 120,170 70,206 89,148 40,112 101,112"
            />
        </svg>

        <!-- Argentina (AR) -->
        <svg
            v-else-if="code === 'AR' || code === 'ARGENTINA'"
            viewBox="0 0 640 480"
            class="h-full w-full object-cover"
        >
            <rect width="640" height="160" fill="#74acdf" />
            <rect y="160" width="640" height="160" fill="#ffffff" />
            <rect y="320" width="640" height="160" fill="#74acdf" />
            <!-- Sol de Mayo -->
            <g transform="translate(320, 240) scale(0.65)">
                <circle r="36" fill="#f6b40e" stroke="#85340a" stroke-width="2" />
                <path
                    fill="#f6b40e"
                    stroke="#85340a"
                    stroke-width="1.5"
                    d="M0,-65 L8,-40 L0,-34 L-8,-40 Z M0,65 L8,40 L0,34 L-8,40 Z M-65,0 L-40,8 L-34,0 L-40,-8 Z M65,0 L40,8 L34,0 L40,-8 Z M-46,-46 L-23,-33 L-24,-24 L-33,-23 Z M46,46 L23,33 L24,24 L33,23 Z M-46,46 L-33,23 L-24,24 L-23,33 Z M46,-46 L33,-23 L24,-24 L23,-33 Z"
                />
            </g>
        </svg>

        <!-- Brasil (BR) -->
        <svg
            v-else-if="code === 'BR' || code === 'BRAZIL' || code === 'BRASIL'"
            viewBox="0 0 640 480"
            class="h-full w-full object-cover"
        >
            <rect width="640" height="480" fill="#009739" />
            <polygon fill="#fedd00" points="320,40 600,240 320,440 40,240" />
            <circle cx="320" cy="240" r="105" fill="#012169" />
            <!-- Faixa branca -->
            <path
                d="M 218 250 A 130 130 0 0 1 422 225 A 145 145 0 0 0 218 250 Z"
                fill="#ffffff"
            />
            <!-- Estrelas simplificadas -->
            <circle cx="320" cy="285" r="3.5" fill="#ffffff" />
            <circle cx="280" cy="270" r="3" fill="#ffffff" />
            <circle cx="350" cy="275" r="3" fill="#ffffff" />
            <circle cx="300" cy="295" r="2.5" fill="#ffffff" />
            <circle cx="335" cy="305" r="2.5" fill="#ffffff" />
        </svg>

        <!-- Colombia (CO) -->
        <svg
            v-else-if="code === 'CO' || code === 'COLOMBIA'"
            viewBox="0 0 640 480"
            class="h-full w-full object-cover"
        >
            <rect width="640" height="240" fill="#fcd116" />
            <rect y="240" width="640" height="120" fill="#003893" />
            <rect y="360" width="640" height="120" fill="#ce1126" />
        </svg>

        <!-- Perú (PE) -->
        <svg
            v-else-if="code === 'PE' || code === 'PERU' || code === 'PERÚ'"
            viewBox="0 0 640 480"
            class="h-full w-full object-cover"
        >
            <rect width="213.3" height="480" fill="#d91023" />
            <rect x="213.3" width="213.4" height="480" fill="#ffffff" />
            <rect x="426.7" width="213.3" height="480" fill="#d91023" />
            <!-- Escudo cívico simplificado -->
            <g transform="translate(320, 240) scale(0.6)">
                <path
                    d="M-28,-36 L28,-36 L28,10 C28,34 0,44 0,44 C0,44 -28,34 -28,10 Z"
                    fill="#ffffff"
                    stroke="#1e3d14"
                    stroke-width="3"
                />
                <path d="M-28,2 L28,2" stroke="#1e3d14" stroke-width="2" />
                <path d="M0,-36 L0,2" stroke="#1e3d14" stroke-width="2" />
                <rect x="-26" y="-34" width="24" height="34" fill="#69b3e7" />
                <rect x="2" y="-34" width="24" height="34" fill="#ffffff" />
                <rect x="-26" y="4" width="52" height="30" fill="#ce1126" />
            </g>
        </svg>

        <!-- Bolivia (BO) -->
        <svg
            v-else-if="code === 'BO' || code === 'BOLIVIA'"
            viewBox="0 0 640 480"
            class="h-full w-full object-cover"
        >
            <rect width="640" height="160" fill="#d52b1e" />
            <rect y="160" width="640" height="160" fill="#fcd116" />
            <rect y="320" width="640" height="160" fill="#007934" />
            <!-- Escudo boliviano -->
            <circle cx="320" cy="240" r="28" fill="#034ea2" stroke="#fcd116" stroke-width="2" />
            <circle cx="320" cy="240" r="18" fill="#fcd116" />
        </svg>

        <!-- Uruguay (UY) -->
        <svg
            v-else-if="code === 'UY' || code === 'URUGUAY'"
            viewBox="0 0 640 480"
            class="h-full w-full object-cover"
        >
            <rect width="640" height="480" fill="#ffffff" />
            <rect y="53.3" width="640" height="53.3" fill="#0038a8" />
            <rect y="160" width="640" height="53.3" fill="#0038a8" />
            <rect y="266.6" width="640" height="53.3" fill="#0038a8" />
            <rect y="373.3" width="640" height="53.3" fill="#0038a8" />
            <rect width="213.3" height="213.3" fill="#ffffff" />
            <!-- Sol uruguayo -->
            <g transform="translate(106.6, 106.6) scale(0.6)">
                <circle r="30" fill="#fcd116" stroke="#85340a" stroke-width="2" />
                <path
                    fill="#fcd116"
                    stroke="#85340a"
                    stroke-width="1.5"
                    d="M0,-52 L6,-32 L0,-28 L-6,-32 Z M0,52 L6,32 L0,28 L-6,32 Z M-52,0 L-32,6 L-28,0 L-32,-6 Z M52,0 L32,6 L28,0 L32,-6 Z"
                />
            </g>
        </svg>

        <!-- Paraguay (PY) -->
        <svg
            v-else-if="code === 'PY' || code === 'PARAGUAY'"
            viewBox="0 0 640 480"
            class="h-full w-full object-cover"
        >
            <rect width="640" height="160" fill="#d52b1e" />
            <rect y="160" width="640" height="160" fill="#ffffff" />
            <rect y="320" width="640" height="160" fill="#0038a8" />
            <circle cx="320" cy="240" r="32" fill="#ffffff" stroke="#007934" stroke-width="3" />
            <polygon
                fill="#fcd116"
                stroke="#85340a"
                stroke-width="0.5"
                points="320,222 325,236 340,236 328,245 332,259 320,250 308,259 312,245 300,236 315,236"
            />
        </svg>

        <!-- Ecuador (EC) -->
        <svg
            v-else-if="code === 'EC' || code === 'ECUADOR'"
            viewBox="0 0 640 480"
            class="h-full w-full object-cover"
        >
            <rect width="640" height="240" fill="#ffdd00" />
            <rect y="240" width="640" height="120" fill="#034ea2" />
            <rect y="360" width="640" height="120" fill="#ed1c24" />
            <!-- Escudo ecuatoriano -->
            <g transform="translate(320, 240) scale(0.55)">
                <ellipse cx="0" cy="0" rx="36" ry="46" fill="#69b3e7" stroke="#034ea2" stroke-width="2" />
                <path d="M-36,0 A36,46 0 0 0 36,0 L36,46 L-36,46 Z" fill="#007934" />
                <circle cx="0" cy="-14" r="12" fill="#ffdd00" />
                <!-- Cóndor superior -->
                <path d="M-28,-48 L0,-38 L28,-48 L0,-28 Z" fill="#4a2511" />
            </g>
        </svg>

        <!-- México (MX) -->
        <svg
            v-else-if="code === 'MX' || code === 'MEXICO' || code === 'MÉXICO'"
            viewBox="0 0 640 480"
            class="h-full w-full object-cover"
        >
            <rect width="213.3" height="480" fill="#006847" />
            <rect x="213.3" width="213.4" height="480" fill="#ffffff" />
            <rect x="426.7" width="213.3" height="480" fill="#ce1126" />
            <!-- Escudo mexicano (águila sobre nopal simplificado) -->
            <g transform="translate(320, 240) scale(0.65)">
                <ellipse cx="0" cy="8" rx="20" ry="10" fill="#007934" />
                <path
                    d="M -10,0 C -15,-20 0,-30 0,-30 C 0,-30 15,-20 10,0 C 15,10 -15,10 -10,0 Z"
                    fill="#663e14"
                />
                <circle cx="0" cy="-24" r="5" fill="#fcd116" />
            </g>
        </svg>

        <!-- Fallback genérico con código ISO -->
        <div
            v-else
            class="flex h-full w-full items-center justify-center bg-slate-200 font-mono text-[9px] font-black uppercase text-slate-700"
        >
            {{ code.slice(0, 2) }}
        </div>
    </div>
</template>
