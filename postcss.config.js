// ESM PostCSS config for Vite builds
// PurgeCSS enabled only for production builds

import autoprefixer from 'autoprefixer'
import purgecssModule from '@fullhuman/postcss-purgecss'

// Handle different ESM/CJS export shapes across environments
const purgecss = (purgecssModule && (purgecssModule.default || purgecssModule.purgecss)) || purgecssModule

const isProd = process.env.NODE_ENV === 'production'

export default {
  plugins: [
    autoprefixer,
    ...(isProd
      ? [
          purgecss({
            content: [
              './resources/views/**/*.blade.php',
              './resources/js/**/*.js',
              './resources/assets/**/*.js',
              './resources/assets/**/*.scss',
              './resources/assets/**/*.css',
              './resources/css/**/*.css',
              './resources/scss/**/*.scss',
              './resources/**/*.vue',
              './resources/**/*.json',
              './resources/menu/**/*.json',
              './app/View/Components/**/*.php'
            ],
            defaultExtractor: content => content.match(/[\w-/:%.]+(?<!:)/g) || [],
            fontFace: true, // Preserve @font-face rules
            keyframes: true, // Preserve @keyframes rules
            safelist: {
              standard: [
                'show',
                'fade',
                'collapsing',
                'active',
                'focus',
                'disabled',
                'was-validated',
                // Iconify helpers
                'ti', 'ti-xs', 'ti-sm', 'ti-md', 'ti-lg', 'ti-xl'
              ],
              deep: [
                // Bootstrap core utility/components
                /^modal/,
                /^tooltip/,
                /^bs-/,
                /^toast/,
                /^collapse/,
                /^carousel/,
                /^dropdown/,
                /^offcanvas/,
                /^alert/,
                /^btn/,
                /^col-/,
                /^row$/,
                /^g-/,
                /^card/,
                /^nav/,
                /^navbar/,
                /^pagination/,
                /^table/,
                /^form/,
                /^input-group/,
                /^list-group/,
                /^badge/,
                /^progress/,
                /^spinner/,
                /^accordion/,

                // Third-party libraries used in the project
                /^fc-/, // FullCalendar
                /^flatpickr/,
                /^apexcharts-?/,
                /^swiper-?/,
                /^plyr/,
                /^select2/,
                /^tagify/,
                /^notyf/,
                /^dropzone/,
                /^quill/,
                /^leaflet/,
                /^mapbox/,
                /^shepherd/,
                /^nouislider/,
                /^sweetalert2/,
                /^daterangepicker/,

                // Icon classes
                /^ti-/,
                /^tabler-/,

                // Summernote (editor) classes - comprehensive coverage
                /^note-/, // Catch all note-* classes
                /^summernote/, // Font-family and related

                // Specific Summernote classes for better targeting
                /note-editor/,
                /note-toolbar/,
                /note-btn/,
                /note-statusbar/,
                /note-editable/,
                /note-placeholder/,
                /note-popover/,
                /note-hint/,
                /note-icon/,
                /note-dropdown/,
                /note-modal/,
                /note-handle/,
                /note-resizebar/,
                /note-editing-area/,
                /note-codable/,
                /note-frame/,
                /note-airframe/,
                /note-palette/,
                /note-dimension/,
                /note-style/,
                /note-para/,
                /note-table/,
                /note-fontsize/,
                /note-color/,
                /note-float/,
                /note-align/
              ],
              greedy: []
            }
          })
        ]
      : [])
  ]
}
