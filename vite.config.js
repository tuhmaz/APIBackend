import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import html from '@rollup/plugin-html';
import { glob } from 'glob';
import path from 'path';
import iconsPlugin from './vite.icons.plugin.js';

/**
 * Get Files from a directory
 * @param {string} query
 * @returns array
 */
function GetFilesArray(...queries) {
  return glob.sync(queries);
}

// Page JS Files
const pageJsFiles = GetFilesArray('resources/assets/js/*.js', 'resources/assets/js/pages/*.js');

// Processing Vendor JS Files
const vendorJsFiles = GetFilesArray('resources/assets/vendor/js/*.js');

// Processing Libs JS Files
const LibsJsFiles = GetFilesArray('resources/assets/vendor/libs/**/*.js');

// Processing Libs Scss & Css Files
const LibsScssFiles = GetFilesArray('resources/assets/vendor/libs/**/!(_)*.scss');
const LibsCssFiles = GetFilesArray('resources/assets/vendor/libs/**/*.css');

// Processing Core, Themes & Pages Scss Files
const CoreScssFiles = GetFilesArray('resources/assets/vendor/scss/**/!(_)*.scss');

// Processing Fonts Scss & JS Files
const FontsScssFiles = GetFilesArray('resources/assets/vendor/fonts/!(_)*.scss');
const FontsJsFiles = GetFilesArray('resources/assets/vendor/fonts/**/!(_)*.js');
const FontsCssFiles = GetFilesArray('resources/assets/vendor/fonts/**/!(_)*.css','resources/assets/css//**/!(_)*.css',);

// Processing Window Assignment for Libs like jKanban, pdfMake
function libsWindowAssignment() {
  return {
    name: 'libsWindowAssignment',

    transform(src, id) {
      if (id.includes('jkanban.js')) {
        return src.replace('this.jKanban', 'window.jKanban');
      } else if (id.includes('vfs_fonts')) {
        return src.replaceAll('this.pdfMake', 'window.pdfMake');
      }
    }
  };
}

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/assets/js/pages/security/blocked-ips.js',
        'resources/assets/js/pages/security/trusted-ips.js',
        'resources/scss/base/pages/news.scss',
        'resources/css/cookie-consent.css',
        'resources/css/footer-front.css',
        'resources/css/navbar-front.css',
        'resources/assets/js/monitoring/monitoring.js',
        'resources/assets/js/monitoring/security.js',
        'resources/assets/js/monitoring/bans.js',
        'resources/js/school-classes.js',
        'resources/js/subjects.js',
        'resources/js/articles.js',
        'resources/js/sitemap-manager.js',
        'resources/js/articles-form.js',
        'resources/js/articles-show.js',
        'resources/assets/js/pages/dashboard-home.js',
        'resources/assets/js/articles/articles-management.js',
        'resources/assets/js/articles/article-details.js',
        'resources/assets/js/chat/chat.js',
		    'resources/assets/js/notifications/bell.js',
        'resources/js/banner-professional.js',
        'resources/css/banner-professional.css',


        ...pageJsFiles,
        ...vendorJsFiles,
        ...LibsJsFiles,
        'resources/js/laravel-user-management.js', // Processing Laravel User Management CRUD JS File
        ...CoreScssFiles,
        ...LibsScssFiles,
        ...LibsCssFiles,
        ...FontsScssFiles,
        ...FontsJsFiles,
        ...FontsCssFiles
      ],
      refresh: true
    }),
    html(),
    libsWindowAssignment(),
    iconsPlugin()
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources')
    }
  },
  json: {
    stringify: true // Helps with JSON import compatibility
  },
  build: {
    commonjsOptions: {
      include: [/node_modules/] // Helps with importing CommonJS modules
    }
  }
});
