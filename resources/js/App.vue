<script setup lang="ts">
import { onMounted } from 'vue';
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

onMounted(() => {
    auth.fetchUser();
});

async function logout() {
    await auth.logout();

    if (route.meta.requiresAuth) {
        await router.push({ name: 'login' });
    }
}
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-line bg-navy-950 text-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <RouterLink to="/" class="flex min-w-0 items-center gap-3 text-white no-underline">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-gold-500 font-serif text-xl font-semibold text-navy-950">G</span>
                    <span class="min-w-0">
                        <span class="block truncate font-serif text-lg leading-none tracking-wide">Gestión Segura</span>
                        <span class="mt-1 block text-xs uppercase tracking-[0.18em] text-gold-100">Seguro de viaje</span>
                    </span>
                </RouterLink>
                <nav class="flex flex-wrap items-center gap-2 text-sm font-semibold">
                    <RouterLink to="/" class="rounded-full px-3 py-2 text-white/80 no-underline" exact-active-class="bg-white/10 text-white">Cotizar</RouterLink>
                    <RouterLink to="/contrataciones" class="rounded-full px-3 py-2 text-white/80 no-underline" active-class="bg-white/10 text-white">Contrataciones</RouterLink>
                    <button v-if="auth.user" class="rounded-full px-3 py-2 text-white/80" type="button" @click="logout">Salir</button>
                    <RouterLink v-else to="/ingresar" class="rounded-full px-3 py-2 text-white/80 no-underline" active-class="bg-white/10 text-white">Ingresar</RouterLink>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6">
            <RouterView />
        </main>

        <footer class="mx-auto max-w-6xl px-4 pb-10 text-sm text-muted sm:px-6">
            Cotización de referencia. No procesa pagos ni emite una póliza real.
        </footer>
    </div>
</template>
