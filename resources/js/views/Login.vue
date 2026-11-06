<script setup lang="ts">
import { reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Field from '../components/Field.vue';
import { firstError } from '../api';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const form = reactive({
    email: '',
    password: '',
});

function errorFor(key: 'email' | 'password'): string {
    return firstError(auth.fieldErrors[key]);
}

async function submit() {
    try {
        await auth.login(form.email, form.password);
        const redirect = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/') && !route.query.redirect.startsWith('//')
            ? route.query.redirect
            : '/listado';
        await router.push(redirect);
    } catch {
        // El store muestra el error.
    }
}
</script>

<template>
    <section class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(18rem,0.8fr)]">
        <form class="rounded-3xl border border-line bg-card p-6 shadow-sm sm:p-8" @submit.prevent="submit">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-gold-600">Acceso interno</p>
            <h1 class="mt-2 font-serif text-4xl text-navy-950">Ingresar</h1>
            <p class="mt-3 text-muted">El listado de cotizaciones es del equipo. La cotización pública no pide cuenta.</p>

            <div class="mt-6 grid gap-4">
                <Field label="Correo" :error="errorFor('email')">
                    <input v-model="form.email" class="field-input" type="email" autocomplete="username" :aria-invalid="Boolean(errorFor('email'))">
                </Field>
                <Field label="Contraseña" :error="errorFor('password')">
                    <input v-model="form.password" class="field-input" type="password" autocomplete="current-password" :aria-invalid="Boolean(errorFor('password'))">
                </Field>
            </div>

            <p v-if="auth.error && !errorFor('email')" class="mt-4 text-sm text-danger" role="alert">{{ auth.error }}</p>

            <button class="btn btn-primary mt-6" type="submit" :disabled="auth.loading">
                {{ auth.loading ? 'Ingresando…' : 'Entrar' }}
            </button>
        </form>

        <aside class="h-fit rounded-3xl border border-line bg-navy-950 p-6 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gold-500">Usuario de demostración</p>
            <h2 class="mt-2 font-serif text-3xl">Administración</h2>
            <p class="mt-3 text-sm text-white/75">Estas credenciales solo sirven para revisar la prueba. No son una cuenta real.</p>
            <dl class="mt-6 space-y-3 text-sm">
                <div>
                    <dt class="text-white/60">Correo</dt>
                    <dd class="font-semibold">admin@gestionsegura.test</dd>
                </div>
                <div>
                    <dt class="text-white/60">Contraseña</dt>
                    <dd class="font-semibold">password</dd>
                </div>
            </dl>
        </aside>
    </section>
</template>
