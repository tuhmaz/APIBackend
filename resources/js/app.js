import Alpine from 'alpinejs'
import csp from '@alpinejs/csp'
import './bootstrap';
/*
  Add custom scripts here
*/
// Alpine.js (CSP compatible with Livewire)
// Ensure Alpine waits for Livewire and uses CSP-safe evaluator
window.deferLoadingAlpine = (callback) => {
  window.addEventListener('livewire:load', callback)
}

// Prevent multiple Alpine instances
if (!window.Alpine) {
  if (typeof csp === 'function') {
    Alpine.plugin(csp)
  }
  window.Alpine = Alpine
  Alpine.start()
}
// Expose jQuery globally for plugins that expect window.$ / window.jQuery
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

// Toastr notifications
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';
window.toastr = toastr;
// Optional defaults
window.toastr.options = {
  closeButton: true,
  progressBar: true,
  newestOnTop: true,
  timeOut: 3000,
  positionClass: 'toast-top-right'
};

// Summernote is provided via vendor wrapper (BS5 build) and styles via SCSS inputs

import.meta.glob([
  '../assets/img/**',
  // '../assets/json/**',
  '../assets/vendor/fonts/**'
]);
