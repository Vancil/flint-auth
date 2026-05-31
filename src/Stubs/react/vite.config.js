import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        outDir: 'public/build',
        rollupOptions: {
            input: 'resources/js/main.jsx',
        },
    },
    server: {
        proxy: {
            '/api': 'http://localhost:8000',
        },
    },
})
