import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';


export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/sortable.css', 'resources/js/app.js', 'resources/js/sortableJs.js'],
            refresh: true,
        }),

    ],
});
