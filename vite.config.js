import { defineConfig } from 'vite';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    root: path.resolve(__dirname, 'src'),
    build: {
        outDir: '../assets/dist',
        emptyOutDir: true,
        sourcemap: true,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true
            },
            format: {
                comments: false
            }
        },
        rollupOptions: {
            input: {
                'admin/index': path.resolve(__dirname, 'src/js/admin/index.js'),
                'public/index': path.resolve(__dirname, 'src/js/public/index.js')
            },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name].js',
                assetFileNames: (assetInfo) => {
                    const info = assetInfo.name.split('.');
                    const ext = info[info.length - 1];
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(ext)) {
                        return `images/[name].${ext}`;
                    }
                    if (/css/i.test(ext)) {
                        return `css/[name].${ext}`;
                    }
                    return `js/[name].${ext}`;
                }
            }
        }
    },
    css: {
        postcss: path.resolve(__dirname, 'postcss.config.js')
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'src/js'),
            '@modules': path.resolve(__dirname, 'src/modules'),
            '@css': path.resolve(__dirname, 'src/css')
        }
    },
    server: {
        hmr: {
            protocol: 'ws',
            host: 'localhost'
        }
    }
});