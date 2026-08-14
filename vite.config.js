import { defineConfig } from 'vite';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const bannerPlugin = () => ({
    name: 'banner-plugin',
    generateBundle(options, bundle) {
        for (const [fileName, asset] of Object.entries(bundle)) {
            if (fileName.endsWith('.css')) {
                const isFrontend = typeof asset.source === 'string' && asset.source.includes('repeater-field-for-acf-frontend');
                const banner = `/*!\n * Raeen Repeater Field for ACF - ${isFrontend ? 'Frontend Stylesheet' : 'Admin Stylesheet'}\n * Source: src/css/${isFrontend ? 'public/index.css' : 'admin/repeater.css'}\n * Repository: https://github.com/raeenzubair/repeater-field-for-acf\n * Author: Mohammad Zubair Ali\n * License: GPL-2.0-or-later\n * Build: npm run build\n */\n`;
                asset.source = banner + asset.source;
            }
        }
    }
});

export default defineConfig({
    root: path.resolve(__dirname, 'src'),
    plugins: [bannerPlugin()],
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
                comments: /^!|@license|@preserve|Repository/i
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
                banner: (chunk) => {
                    const isPublic = chunk.facadeModuleId && chunk.facadeModuleId.includes('public');
                    const name = isPublic ? 'Raeen Repeater Field for ACF - Frontend Scripts' : 'Raeen Repeater Field for ACF - Admin Scripts';
                    const src = isPublic ? 'src/js/public/index.js' : 'src/js/admin/index.js';
                    return `/*!\n * ${name}\n * Source: ${src}\n * Repository: https://github.com/raeenzubair/repeater-field-for-acf\n * Author: Mohammad Zubair Ali\n * License: GPL-2.0-or-later\n * Build: npm run build\n */\n`;
                },
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