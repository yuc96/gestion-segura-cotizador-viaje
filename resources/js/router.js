import { createRouter, createWebHistory } from 'vue-router';
import QuoteCreate from './views/QuoteCreate.vue';
import QuoteShow from './views/QuoteShow.vue';
import QuoteIndex from './views/QuoteIndex.vue';

const router = createRouter({
    history: createWebHistory(),
    scrollBehavior() {
        return { top: 0 };
    },
    routes: [
        { path: '/', name: 'quote.create', component: QuoteCreate },
        { path: '/cotizaciones/:id', name: 'quote.show', component: QuoteShow, props: true },
        { path: '/contrataciones', name: 'quotes.index', component: QuoteIndex },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
});

export default router;
