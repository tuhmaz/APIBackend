// Dashboard Home Page Script
// Requires: ApexCharts loaded globally

(function () {
  document.addEventListener('DOMContentLoaded', function () {
    // Analytics data injected via blade should be present on a data attribute or global
    // We will attempt to read from a data tag if present; fallback to window.__ANALYTICS_DATA__ if set by blade
    const container = document.getElementById('contentChart');
    if (!container || typeof ApexCharts === 'undefined') {
      console.warn('[Dashboard] Chart container or ApexCharts not available. Skipping init.');
      return;
    }

    // Read analytics data from a script tag-generated global if available
    const analyticsData = (window.__ANALYTICS_DATA__ || {});

    // Ensure a valid default locale is available for ApexCharts
    if (!window.Apex) window.Apex = {};
    if (!window.Apex.chart) window.Apex.chart = {};
    const hasValidLocales = Array.isArray(window.Apex.chart.locales) && window.Apex.chart.locales.length > 0 && typeof window.Apex.chart.defaultLocale === 'string';
    if (!hasValidLocales) {
      window.Apex.chart.locales = [
        {
          name: 'en',
          options: {
            months: ['January','February','March','April','May','June','July','August','September','October','November','December'],
            shortMonths: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            days: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
            shortDays: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
            toolbar: {
              exportToSVG: 'Download SVG',
              exportToPNG: 'Download PNG',
              exportToCSV: 'Download CSV',
              menu: 'Menu',
              selection: 'Selection',
              selectionZoom: 'Selection Zoom',
              zoomIn: 'Zoom In',
              zoomOut: 'Zoom Out',
              pan: 'Panning',
              reset: 'Reset Zoom'
            }
          }
        }
      ];
      window.Apex.chart.defaultLocale = 'en';
    }

    // Normalize data to plain arrays (defensive)
    const toArray = v => Array.isArray(v) ? v : (v && typeof v === 'object' && 'length' in v ? Array.from(v) : (v ? Object.values(v) : []));
    // Numeric coercion and sizing helpers (defined once)
    const toNumberArray = a => toArray(a).map(v => (v == null ? 0 : Number(v)));
    const fitToLen = (arr, len) => {
      const out = arr.slice(0, len);
      while (out.length < len) out.push(0);
      return out;
    };
    const categories = toArray(analyticsData.dates);
    const _len = categories.length;
    const seriesArticles = fitToLen(toNumberArray(analyticsData.articles), _len);
    const seriesNews = fitToLen(toNumberArray(analyticsData.news), _len);
    const seriesComments = fitToLen(toNumberArray(analyticsData.comments), _len);
    const seriesViews = fitToLen(toNumberArray(analyticsData.views), _len);

    const options = {
      series: [
        { name: analyticsData.i18nArticles || 'Articles', data: seriesArticles, type: 'line' },
        { name: analyticsData.i18nNews || 'News', data: seriesNews, type: 'line' },
        { name: analyticsData.i18nComments || 'Comments', data: seriesComments, type: 'area' },
        { name: analyticsData.i18nViews || 'Views', data: seriesViews, type: 'area' }
      ],
      chart: {
        height: 350,
        type: 'line',
        toolbar: { show: true, tools: { download: false, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } },
        zoom: { enabled: false }
      },
      dataLabels: { enabled: false },
      stroke: { width: [2, 2, 1, 1], curve: 'smooth' },
      colors: ['#696cff', '#03c3ec', '#ffab00', '#71dd37'],
      fill: {
        opacity: [1, 1, 0.3, 0.3],
        type: ['solid', 'solid', 'gradient', 'gradient'],
        gradient: { shade: 'light', type: 'vertical', opacityFrom: 0.4, opacityTo: 0.1 }
      },
      legend: {
        position: 'top', horizontalAlign: 'right', offsetY: -20,
        labels: { colors: document.documentElement.classList.contains('dark-style') ? '#fff' : undefined }
      },
      markers: { size: 4, hover: { size: 6 } },
      xaxis: {
        categories: categories,
        labels: { style: { colors: document.documentElement.classList.contains('dark-style') ? '#fff' : undefined } },
        axisBorder: { show: false }
      },
      yaxis: { labels: { style: { colors: document.documentElement.classList.contains('dark-style') ? '#fff' : undefined } } },
      grid: { borderColor: document.documentElement.classList.contains('dark-style') ? '#3b3b3b' : '#e7e7e7' },
      tooltip: {
        shared: true, intersect: false,
        y: { formatter: function (y) { if (typeof y !== 'undefined') { return y.toFixed(0); } return y; } }
      }
    };

    if (window._contentChart && typeof window._contentChart.destroy === 'function') {
      try { window._contentChart.destroy(); } catch (e) {}
    }

    const chart = new ApexCharts(container, options);
    window._contentChart = chart;

    requestAnimationFrame(() => {
      try {
        const p = chart.render();
        if (p && typeof p.then === 'function') p.catch(e => console.error('[Dashboard] Chart render promise failed:', e));
      } catch (e) {
        console.error('[Dashboard] Chart render failed:', e);
      }
    });

    // Time range dropdown behavior
    const timeRangeDropdown = document.querySelector('#timeRangeMenu');
    const timeRangeButton = document.getElementById('timeRangeButton');

    if (timeRangeDropdown && timeRangeButton) {
      timeRangeDropdown.addEventListener('click', function (e) {
        if (e.target.classList.contains('dropdown-item')) {
          e.preventDefault();
          const days = e.target.dataset.days;
          timeRangeButton.textContent = e.target.textContent;
          timeRangeDropdown.querySelectorAll('.dropdown-item').forEach(item => item.classList.remove('active'));
          e.target.classList.add('active');
          fetch(`/dashboard/analytics?days=${days}`)
            .then(r => r.json())
            .then(data => {
              try {
                const categories = toArray(data?.dates || []);
                const len = categories.length;
                const art = fitToLen(toNumberArray(data?.articles || []), len);
                const nws = fitToLen(toNumberArray(data?.news || []), len);
                const com = fitToLen(toNumberArray(data?.comments || []), len);
                const vws = fitToLen(toNumberArray(data?.views || []), len);

                chart.updateOptions({ xaxis: { categories } });
                chart.updateSeries([
                  { name: analyticsData.i18nArticles || 'Articles', data: art },
                  { name: analyticsData.i18nNews || 'News', data: nws },
                  { name: analyticsData.i18nComments || 'Comments', data: com },
                  { name: analyticsData.i18nViews || 'Views', data: vws }
                ]);
              } catch (err) {
                console.error('[Dashboard] Chart update failed:', err);
              }
              const sel = s => document.querySelector(s);
              const safeText = (el, v) => { if (el) el.textContent = v; };
              safeText(sel('[data-stat="total-views"]'), data.totalViews);
              safeText(sel('[data-stat="active-authors"]'), data.activeAuthors);
              safeText(sel('[data-stat="total-comments"]'), data.totalComments);
            });
        }
      });
    }
  });
})();
