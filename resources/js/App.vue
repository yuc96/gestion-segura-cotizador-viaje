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
        <header class="site-header">
            <div class="shell site-bar">
                <RouterLink to="/" class="brand">
                    <span class="brand-mark">G</span>
                    <span class="min-w-0">
                        <span class="brand-name">Gestión Segura</span>
                        <span class="brand-tag">Seguro de viaje</span>
                    </span>
                </RouterLink>
                <nav class="site-nav">
                    <RouterLink to="/" class="nav-link" exact-active-class="is-current">Cotizar</RouterLink>
                    <RouterLink v-if="auth.user" to="/listado" class="nav-link" active-class="is-current">Listado</RouterLink>
                    <button v-if="auth.user" class="nav-link" type="button" @click="logout">Salir</button>
                    <RouterLink v-else to="/ingresar" class="nav-link" active-class="is-current">Ingresar</RouterLink>
                </nav>
            </div>
        </header>

        <main class="shell shell-main">
            <RouterView />
        </main>

        <footer class="shell shell-footer">
            Cotización de referencia. No procesa pagos ni emite una póliza real.
        </footer>
    </div>
</template>
