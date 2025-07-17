import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: 'localhost', // IP fija de tu red local
        port: 5173,          // Puerto por defecto de Vite
        strictPort: true     // Lanza error si el puerto ya está en uso
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: [
                'resources/views/**/*.blade.php',
                'app/Http/Livewire/**/*.php',
                'resources/js/**/*.vue'
            ],
        }),
    ],
});
