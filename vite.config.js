import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [laravel({ input: ['resources/js/institutional.js', 'resources/js/certificate-capture.js', 'resources/css/public.css', 'resources/css/casetas.css', 'resources/js/casetas.js', 'resources/js/parking-maps.js'], refresh: ['resources/views/**'] })],
});
