import { defineConfig, type Plugin } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import laravel from 'laravel-vite-plugin';
import { resolve, join, dirname } from 'node:path';
import { mkdirSync, writeFileSync } from 'node:fs';
import * as sass from 'sass';


function scssBuildPlugin(): Plugin {
    const scssDir = resolve(__dirname, 'resources/scss');
    const cssDir  = resolve(__dirname, 'public/css');
    let isServe = false;

    const entries = [
        { in: 'lightschool-base.scss', out: 'lightschool-base.css' },
        { in: 'lightschool.scss',      out: 'lightschool.css'      },
        { in: 'lightschool-my.scss',   out: 'lightschool-my.css'   },
        { in: 'fra-notifications.scss',out: 'fra-notifications.css'},
        { in: 'fra-context-menu.scss', out: 'fra-context-menu.css' },
        { in: 'menu.scss',             out: 'menu.css'             },
        { in: 'theme/dark.scss',       out: 'theme/dark.css'       },
    ].map(e => ({
        in:  resolve(scssDir, e.in),
        out: resolve(cssDir,  e.out),
    }));

    const compileAll = (minify: boolean) => {
        for (const entry of entries) {
            mkdirSync(dirname(entry.out), { recursive: true });
            const result = sass.compile(entry.in, {
                style: minify ? 'compressed' : 'expanded',
                loadPaths: [scssDir],
            });
            writeFileSync(entry.out, result.css);
        }
    };

    return {
        name: 'scss-build',
        configResolved(config) {
            isServe = config.command === 'serve';
        },
        buildStart() {
            compileAll(!isServe);
        },
        configureServer(server) {
            compileAll(false);
            server.watcher.add(join(scssDir, '**', '*.scss'));
        },
        handleHotUpdate({ file, server }) {
            if (file.startsWith(scssDir) && file.endsWith('.scss')) {
                compileAll(false);
                server.hot.send({ type: 'full-reload' });
                return [];
            }
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/svelte/entries/app.ts'],
            buildDirectory: 'build',
            refresh: false,
        }),
        svelte(),
        scssBuildPlugin(),
    ],
    publicDir: false,
    build: {
        outDir: 'public/build',
        emptyOutDir: false,
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: '[name][extname]',
            },
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
            port: 5173,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
