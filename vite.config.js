import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/images/logo-breit.png',
                'resources/images/bg.jpg'  // Hintergrundbild hinzufügen
            ],
            refresh: true,
        }),
    ],
});
