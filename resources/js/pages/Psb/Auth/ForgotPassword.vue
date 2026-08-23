<script setup lang="ts">
defineOptions({ layout: GuestLayout });
import { Head, Link } from '@inertiajs/vue3';
import BackButton from '@/Components/BackButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { formatWaUrl } from '@/lib/utils';
import { login } from '@/routes/psb';

defineProps<{
    kontak_darurat_wa?: string;
}>();
</script>

<template>
    <div class="h-full w-full">
        <Head title="Lupa Password" />

        <transition name="page" appear>
            <div
                class="custom-scrollbar relative z-10 flex h-full w-full flex-col justify-center overflow-y-auto px-6 py-16 sm:px-10 lg:px-16 lg:py-0 xl:px-24"
            >
                <!-- Header / Logos -->
                <div
                    class="absolute top-6 left-6 flex items-center space-x-3 sm:top-8 sm:left-10 sm:space-x-4 lg:top-12 lg:left-16 xl:left-24"
                >
                    <Link
                        href="/"
                        class="flex items-center space-x-3 transition-opacity hover:opacity-80"
                    >
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
                    </Link>
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

                <div
                    class="animate-fade-in-up mx-auto mt-20 w-full max-w-md lg:mt-0"
                >
                    <div
                        class="mb-8 inline-flex items-center rounded-full border border-orange-100 bg-orange-50 px-4 py-1.5 text-[11px] font-bold tracking-widest text-orange-600 uppercase sm:text-xs"
                    >
                        Bantuan Akses
                    </div>

                    <h2
                        class="mb-4 text-3xl leading-tight font-extrabold tracking-tight text-primary-dark sm:text-4xl"
                    >
                        Lupa Password?
                    </h2>

                    <div
                        class="mb-8 rounded-2xl border border-blue-100 bg-blue-50/50 p-6 leading-relaxed font-medium text-neutral-warm"
                    >
                        <p class="mb-4">
                            Untuk alasan keamanan data santri, penyetelan ulang
                            (reset) password tidak dapat dilakukan secara
                            mandiri.
                        </p>
                        <p>
                            Silakan hubungi admin panitia PSB melalui WhatsApp
                            resmi kami dengan menyertakan format
                            <strong>Nama Lengkap</strong> dan
                            <strong>NIK/No. Pendaftaran</strong> Anda.
                        </p>
                    </div>

                    <a
                        v-if="kontak_darurat_wa"
                        :href="formatWaUrl(kontak_darurat_wa, 'Assalamu\'alaikum Panitia PSB Dalwa Kalbar, saya membutuhkan bantuan reset password akun pendaftaran santri.')"
                        target="_blank"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-green-500 px-8 py-4 text-[15px] font-bold text-white shadow-[0_8px_30px_rgb(34,197,94,0.2)] transition-all duration-300 hover:bg-green-600 hover:shadow-[0_8px_30px_rgb(34,197,94,0.4)]"
                    >
                        <svg
                            class="mr-2 h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M12.031 21.464l-3.328 1.096 1.112-3.232a9.92 9.92 0 112.216 2.136z"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                fill="none"
                            />
                            <path
                                d="M9.197 10.155c-.214.214-.265.513-.122.775 1.186 2.164 2.87 3.848 5.034 5.034.262.143.561.092.775-.122l.53-.53c.27-.27.674-.325 1.002-.136l2.176 1.25c.348.2.493.633.326.992a3 3 0 01-2.91 1.758c-4.468-.344-8.5-2.613-11.458-5.571-2.958-2.958-5.227-6.99-5.571-11.458a3 3 0 011.758-2.91c.36-.167.793-.022.993.326l1.25 2.176c.189.328.134.732-.136 1.002l-.53.53z"
                                fill="currentColor"
                            />
                        </svg>
                        Hubungi Admin PSB
                    </a>

                    <div class="mt-8">
                        <BackButton :href="login.url()">Kembali</BackButton>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
