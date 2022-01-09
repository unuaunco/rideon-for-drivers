// test vue app

import 'bootstrap';

import { createApp } from 'vue';

import TrackingApp from './TrackingApp.vue';

createApp({
    components: {
        TrackingApp,
    }
}).mount('#tracking-app');