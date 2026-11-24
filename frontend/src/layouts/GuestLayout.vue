<template>
    <div class="grid min-h-screen bg-porcelain lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-enamel-950 lg:block" aria-hidden="true">
            <!-- Four team lines meeting at one interchange: the app's mark, at the scale of the panel. -->
            <svg viewBox="0 0 600 800" preserveAspectRatio="xMidYMid slice" class="absolute inset-0 h-full w-full">
                <g fill="none" stroke-width="12" stroke-linejoin="round" stroke-linecap="round">
                    <path d="M-20 120 H140 L300 280 V520 L420 640 H620" stroke="#D21F2B" />
                    <path d="M620 160 H460 L300 320 V500 L160 640 H-20" stroke="#1E5BFF" />
                    <path d="M-20 400 H620" stroke="#F2B100" />
                    <path d="M300 -20 V820" stroke="#1F9D55" />
                </g>
                <g fill="#0B1230" stroke="#fff" stroke-width="6">
                    <circle cx="140" cy="120" r="12" />
                    <circle cx="460" cy="160" r="12" />
                    <circle cx="160" cy="640" r="12" />
                    <circle cx="420" cy="640" r="12" />
                    <circle cx="80" cy="400" r="12" />
                    <circle cx="520" cy="400" r="12" />
                    <circle cx="300" cy="400" r="34" stroke-width="10" />
                </g>
            </svg>
        </div>

        <div class="flex items-center justify-center px-4 py-12">
            <div class="w-full max-w-sm">
                <div class="mb-10 flex items-center gap-3">
                    <svg viewBox="0 0 32 32" class="h-9 w-9" aria-hidden="true">
                        <path d="M2 23 L11 16 H21 L30 9" fill="none" stroke="#D21F2B" stroke-width="3" stroke-linejoin="round" />
                        <path d="M2 9 L11 16 H21 L30 23" fill="none" stroke="#1E5BFF" stroke-width="3" stroke-linejoin="round" />
                        <circle cx="16" cy="16" r="5.5" fill="#fff" stroke="#0B1230" stroke-width="2.5" />
                    </svg>
                    <span class="font-sign text-2xl font-bold uppercase tracking-widest text-enamel-950">CRM</span>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
