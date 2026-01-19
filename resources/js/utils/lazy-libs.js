// Lazy-load common page libraries based on DOM presence
// Each init function checks for matching elements and dynamically imports the library only if needed.

function onIdle(cb) {
  if ('requestIdleCallback' in window) {
    // @ts-ignore
    requestIdleCallback(cb, { timeout: 1500 });
  } else {
    setTimeout(cb, 300);
  }
}

export function initBootstrapTooltips() {
  onIdle(async () => {
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltipEls.length === 0) return;
    const { default: Tooltip } = await import('bootstrap/js/dist/tooltip');
    tooltipEls.forEach(el => new Tooltip(el));
  });
}

export function initFlatpickr() {
  onIdle(async () => {
    const inputs = document.querySelectorAll('[data-flatpickr], .flatpickr');
    if (inputs.length === 0) return;
    const [{ default: flatpickr }, _css] = await Promise.all([
      import('flatpickr'),
      import('flatpickr/dist/flatpickr.min.css')
    ]);
    inputs.forEach(el => flatpickr(el));
  });
}

export function initSelect2() {
  onIdle(async () => {
    const selects = document.querySelectorAll('[data-select2], .select2');
    if (selects.length === 0) return;
    const [$] = await Promise.all([
      import('jquery')
    ]);
    const jq = $.default || $;
    await Promise.all([
      import('select2'),
      import('select2/dist/css/select2.min.css')
    ]);
    selects.forEach(el => jq(el).select2());
  });
}

export function initDataTables() {
  onIdle(async () => {
    const tables = document.querySelectorAll('[data-datatable], table.table-dt');
    if (tables.length === 0) return;
    const [$] = await Promise.all([
      import('jquery')
    ]);
    const jq = $.default || $;
    // Core plus Bootstrap 5 styling
    await Promise.all([
      import('datatables.net'),
      import('datatables.net-bs5'),
      import('datatables.net-bs5/css/dataTables.bootstrap5.min.css')
    ]);
    tables.forEach(el => jq(el).DataTable());
  });
}

export function initApexCharts() {
  onIdle(async () => {
    const charts = document.querySelectorAll('[data-apexchart]');
    if (charts.length === 0) return;
    const { default: ApexCharts } = await import('apexcharts');
    charts.forEach(el => {
      const opts = JSON.parse(el.getAttribute('data-options') || '{}');
      const chart = new ApexCharts(el, opts);
      chart.render();
    });
  });
}

export function initLeaflet() {
  onIdle(async () => {
    const maps = document.querySelectorAll('[data-leaflet]');
    if (maps.length === 0) return;
    const [{ default: L }, _css] = await Promise.all([
      import('leaflet'),
      import('leaflet/dist/leaflet.css')
    ]);
    maps.forEach(el => {
      const opts = JSON.parse(el.getAttribute('data-options') || '{}');
      const center = opts.center || [0, 0];
      const zoom = opts.zoom || 2;
      const map = L.map(el).setView(center, zoom);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
    });
  });
}

export function initSweetAlert2() {
  onIdle(async () => {
    const triggers = document.querySelectorAll('[data-swal]');
    if (triggers.length === 0) return;
    const Swal = (await import('sweetalert2')).default;
    triggers.forEach(btn => {
      btn.addEventListener('click', async e => {
        e.preventDefault();
        const cfg = JSON.parse(btn.getAttribute('data-swal') || '{}');
        await Swal.fire(cfg);
      });
    });
  });
}

export function initPageLibs() {
  initBootstrapTooltips();
  initFlatpickr();
  initSelect2();
  initDataTables();
  initApexCharts();
  initLeaflet();
  initSweetAlert2();
}
