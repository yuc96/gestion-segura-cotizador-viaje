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
    <section class="split split-login">
        <form class="card" @submit.prevent="submit">
            <p class="eyebrow">Acceso interno</p>
            <h1 class="page-title">Ingresar</h1>
            <p class="lede">El listado de cotizaciones es del equipo. La cotización pública no pide cuenta.</p>

            <div class="fields">
                <Field label="Correo" :error="errorFor('email')">
                    <input v-model="form.email" class="field-input" type="email" autocomplete="username" :aria-invalid="Boolean(errorFor('email'))">
                </Field>
                <Field label="Contraseña" :error="errorFor('password')">
                    <input v-model="form.password" class="field-input" type="password" autocomplete="current-password" :aria-invalid="Boolean(errorFor('password'))">
                </Field>
            </div>

            <p v-if="auth.error && !errorFor('email')" class="form-error" role="alert">{{ auth.error }}</p>

            <div class="actions is-fill">
                <button class="btn btn-primary" type="submit" :disabled="auth.loading">
                    {{ auth.loading ? 'Ingresando…' : 'Entrar' }}
                </button>
            </div>
        </form>

        <aside class="card-dark">
            <p class="eyebrow">Usuario de demostración</p>
            <h2 class="aside-title">Administración</h2>
            <p class="hint">Estas credenciales solo sirven para revisar la prueba. No son una cuenta real.</p>
            <dl class="creds">
                <div>
                    <dt>Correo</dt>
                    <dd>admin@gestionsegura.test</dd>
                </div>
                <div>
                    <dt>Contraseña</dt>
                    <dd>password</dd>
                </div>
            </dl>
        </aside>
    </section>
</template>
