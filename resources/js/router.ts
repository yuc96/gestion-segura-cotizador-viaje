import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';
import QuoteCreate from './views/QuoteCreate.vue';
import QuoteShow from './views/QuoteShow.vue';
import QuoteIndex from './views/QuoteIndex.vue';
import Login from './views/Login.vue';

const router = createRouter({
    history: createWebHistory(),
    scrollBehavior() {
        return { top: 0 };
    },
    routes: [
        { path: '/', name: 'quote.create', component: QuoteCreate },
        { path: '/cotizaciones/:id', name: 'quote.show', component: QuoteShow, props: true },
        { path: '/ingresar', name: 'login', component: Login },
        { path: '/contrataciones', name: 'quotes.index', component: QuoteIndex, meta: { requiresAuth: true } },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
});

router.beforeEach(async (to) => {
    if (!to.meta.requiresAuth) {
        return true;
    }

    const auth = useAuthStore();

    if (!auth.ready) {
        await auth.fetchUser();
    }

    if (!auth.user) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    return true;
});

export default router;
