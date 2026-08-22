<script setup lang="ts">
defineOptions({ layout: GuestLayout });
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { login as adminLogin } from '@/routes/admin';
import { login as psbLogin } from '@/routes/psb';
</script>

<template>
    <Head title="Sistem Penerimaan Santri Baru" />

    <transition name="page" appear>
        <div
            class="custom-scrollbar relative z-10 flex h-full w-full flex-col justify-center overflow-y-auto px-6 py-16 sm:px-10 lg:px-16 lg:py-0 xl:px-24"
        >
            <!-- Header / Logos -->
            <div
                class="absolute top-6 left-6 flex items-center space-x-3 sm:top-8 sm:left-10 sm:space-x-4 lg:top-12 lg:left-16 xl:left-24"
            >
                <div class="flex items-center space-x-3">
                    <img
                        src="/image/logos/logo-1.png"
                        alt="Logo"
                        class="h-10 w-auto sm:h-12"
                    />
                    <div
                        class="hidden h-8 w-px bg-neutral-warm/30 sm:block"
                    ></div>
                    <img
                        src="/image/logos/logo-2.png"
                        alt="Logo 2"
                        class="hidden h-10 w-auto sm:block sm:h-12"
                    />
                </div>
                <div class="flex flex-col">
                    <span
                        class="text-[13px] leading-tight font-extrabold text-primary-dark sm:text-[15px]"
                        >PSB Dalwa</span
                    >
                    <span
                        class="text-[10px] leading-tight font-bold text-neutral-warm sm:text-[11px]"
                        >Perwakilan Kalbar</span
                    >
                </div>
            </div>

            <div class="animate-fade-in-up mt-20 max-w-xl lg:mt-0">
                <div
                    class="mb-8 inline-flex items-center rounded-full border border-primary/10 bg-primary/5 px-4 py-1.5 text-[11px] font-bold tracking-widest text-primary uppercase sm:text-xs"
                >
                    Penerimaan Santri Baru
                </div>

                <h1
                    class="mb-6 text-4xl leading-[1.15] font-extrabold tracking-tight text-primary-dark sm:text-5xl lg:text-5xl xl:text-6xl"
                >
                    Pendaftaran <br class="hidden sm:block" />
                    <span class="text-accent">Dalwa Kalbar</span>
                </h1>

                <p
                    class="mb-10 text-base leading-relaxed font-medium text-neutral-warm sm:text-lg"
                >
                    Bergabunglah bersama kami mewujudkan generasi Islami yang
                    cerdas, berakhlak mulia, dan berwawasan luas.
                </p>

                <div
                    class="flex flex-col space-y-4 sm:flex-row sm:space-y-0 sm:space-x-5"
                >
                    <Link
                        :href="psbLogin.url()"
                        class="inline-flex items-center justify-center rounded-full bg-primary px-8 py-4 text-[15px] font-bold text-white shadow-[0_8px_30px_rgb(39,59,94,0.2)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-primary-dark hover:shadow-[0_8px_30px_rgb(39,59,94,0.4)]"
                    >
                        Mulai Pendaftaran
                        <svg
                            class="ml-2.5 h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"
                            />
                        </svg>
                    </Link>

                    <Link
                        :href="adminLogin.url()"
                        class="inline-flex items-center justify-center rounded-full border border-neutral-warm/30 bg-white px-8 py-4 text-[15px] font-bold text-primary-dark transition-all duration-300 hover:border-primary-dark hover:bg-surface/10"
                    >
                        Portal Admin
                    </Link>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="mt-20 text-[13px] font-semibold text-neutral-warm/60 lg:absolute lg:bottom-12 lg:left-16 xl:left-24"
            >
                &copy; 2026 Darullughah Wadda'wah Perwakilan Kalimantan Barat.
                All Rights Reserved.
            </div>
        </div>
    </transition>
</template>
