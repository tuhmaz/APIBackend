'use strict';

let chartColors = {
    primary: '#696cff',
    secondary: '#8592a3',
    success: '#71dd37',
    info: '#03c3ec',
    warning: '#ffab00',
    danger: '#ff3e1d'
};

// تهيئة الرسوم البيانية
let cpuChart, memoryChart, diskChart, loadChart;
// KPI sparklines
let kpiCpuSpark, kpiMemSpark, kpiDiskSpark;
let cpuHistory = [], memHistory = [], diskHistory = [];
const HISTORY_MAX = 20;

// Auto refresh controls
let autoRefresh = true;
let refreshIntervalId = null;

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    bindControls();
    updateMetrics();
    startAutoRefresh();
});

function bindControls() {
    const toggle = document.getElementById('autoRefreshToggle');
    if (toggle) {
        toggle.checked = true;
        toggle.addEventListener('change', () => {
            autoRefresh = !!toggle.checked;
            if (autoRefresh) startAutoRefresh(); else stopAutoRefresh();
        });
    }
    const btn = document.getElementById('refreshNow');
    if (btn) btn.addEventListener('click', updateMetrics);
}

function startAutoRefresh() {
    stopAutoRefresh();
    if (autoRefresh) {
        refreshIntervalId = setInterval(updateMetrics, 5000);
    }
}

function stopAutoRefresh() {
    if (refreshIntervalId) {
        clearInterval(refreshIntervalId);
        refreshIntervalId = null;
    }
}

function initCharts() {
    // CPU Chart
    cpuChart = new ApexCharts(document.querySelector('#cpuChart'), {
        chart: {
            height: 200,
            type: 'line',
            zoom: { enabled: false },
            toolbar: { show: false }
        },
        series: [{ name: 'CPU Usage', data: [] }],
        colors: [chartColors.primary],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        grid: {
            borderColor: '#f1f1f1',
            padding: { top: 10, right: 0, bottom: 0, left: 0 }
        },
        xaxis: {
            categories: [],
            labels: { show: false }
        },
        yaxis: {
            max: 100,
            labels: { formatter: val => `${val}%` }
        }
    });
    cpuChart.render();

    // Memory Chart
    memoryChart = new ApexCharts(document.querySelector('#memoryChart'), {
        chart: {
            height: 200,
            type: 'donut'
        },
        series: [0, 0],
        labels: ['Used', 'Free'],
        colors: [chartColors.primary, chartColors.secondary],
        legend: { position: 'bottom' }
    });
    memoryChart.render();

    // Disk Chart
    diskChart = new ApexCharts(document.querySelector('#diskChart'), {
        chart: {
            height: 200,
            type: 'donut'
        },
        series: [0, 0],
        labels: ['Used', 'Free'],
        colors: [chartColors.warning, chartColors.secondary],
        legend: { position: 'bottom' }
    });
    diskChart.render();

    // Load Average Chart
    loadChart = new ApexCharts(document.querySelector('#loadChart'), {
        chart: {
            height: 100,
            type: 'area',
            sparkline: { enabled: true }
        },
        series: [{ name: 'Load', data: [] }],
        colors: [chartColors.info],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3
            }
        }
    });
    loadChart.render();

    // KPI Sparklines (optional)
    if (document.querySelector('#kpiCpuSparkline')) {
        kpiCpuSpark = new ApexCharts(document.querySelector('#kpiCpuSparkline'), {
            chart: { type: 'area', height: 60, sparkline: { enabled: true } },
            series: [{ data: [] }],
            colors: [chartColors.primary],
            stroke: { width: 2, curve: 'smooth' },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.1 } }
        });
        kpiCpuSpark.render();
    }
    if (document.querySelector('#kpiMemorySparkline')) {
        kpiMemSpark = new ApexCharts(document.querySelector('#kpiMemorySparkline'), {
            chart: { type: 'area', height: 60, sparkline: { enabled: true } },
            series: [{ data: [] }],
            colors: [chartColors.info],
            stroke: { width: 2, curve: 'smooth' },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.1 } }
        });
        kpiMemSpark.render();
    }
    if (document.querySelector('#kpiDiskSparkline')) {
        kpiDiskSpark = new ApexCharts(document.querySelector('#kpiDiskSparkline'), {
            chart: { type: 'area', height: 60, sparkline: { enabled: true } },
            series: [{ data: [] }],
            colors: [chartColors.warning],
            stroke: { width: 2, curve: 'smooth' },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.1 } }
        });
        kpiDiskSpark.render();
    }
}

async function updateMetrics() {
    try {
        const response = await fetch('/dashboard/performance/metrics/data', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();

        // تحديث الرسوم البيانية
        updateCharts(data);
        // تحديث المعلومات النصية
        updateStats(data);
        // تحديث وقت آخر تحديث
        updateLastUpdated(data.time);

    } catch (error) {
        console.error('Error updating metrics:', error);
    }
}

function updateCharts(data) {
    // تحديث CPU
    if (cpuChart) {
        cpuChart.updateSeries([{
            name: 'CPU Usage',
            data: [data.cpu.usage_percentage]
        }]);
    }

    // Push to histories
    pushHistory(cpuHistory, data.cpu.usage_percentage);

    // تحديث Memory
    if (memoryChart) {
        const totalMem = Number(data.memory.total) || 0;
        const usedMem = Number(data.memory.used) || 0;
        const usedMemory = totalMem > 0 ? Math.min(100, Math.max(0, (usedMem / totalMem) * 100)) : 0;
        const freeMemory = 100 - usedMemory;
        memoryChart.updateSeries([usedMemory, freeMemory]);
    }
    {
        const totalMem = Number(data.memory.total) || 0;
        const usedMem = Number(data.memory.used) || 0;
        const usedPct = totalMem > 0 ? (usedMem / totalMem) * 100 : 0;
        pushHistory(memHistory, usedPct);
    }

    // تحديث Disk
    if (diskChart) {
        const totalDisk = Number(data.disk.total) || 0;
        const usedDiskBytes = Number(data.disk.used) || 0;
        const usedDisk = totalDisk > 0 ? Math.min(100, Math.max(0, (usedDiskBytes / totalDisk) * 100)) : 0;
        const freeDisk = 100 - usedDisk;
        diskChart.updateSeries([usedDisk, freeDisk]);
    }
    {
        const totalDisk = Number(data.disk.total) || 0;
        const usedDiskBytes = Number(data.disk.used) || 0;
        const usedPct = totalDisk > 0 ? (usedDiskBytes / totalDisk) * 100 : 0;
        pushHistory(diskHistory, usedPct);
    }

    // تحديث Load Average
    if (loadChart) {
        loadChart.updateSeries([{
            name: 'Load',
            data: data.cpu.load
        }]);
    }

    // Update KPI sparklines
    if (kpiCpuSpark) kpiCpuSpark.updateSeries([{ data: cpuHistory }]);
    if (kpiMemSpark) kpiMemSpark.updateSeries([{ data: memHistory }]);
    if (kpiDiskSpark) kpiDiskSpark.updateSeries([{ data: diskHistory }]);
}

function updateStats(data) {
    // تحديث معلومات النظام
    updateElement('system-info', `${data.os} (${data.version.system})`);
    updateElement('php-version', data.php_version);
    updateElement('laravel-version', data.version.laravel);
    updateElement('server-software', data.server_software);

    // تحديث معلومات CPU
    updateElement('cpu-cores', data.cpu.cores);
    updateElement('cpu-usage', `${data.cpu.usage_percentage.toFixed(2)}%`);
    updateElement('cpu-load', data.cpu.load.map(l => l.toFixed(2)).join(' | '));
    updateElement('cpu-cores-dup', data.cpu.cores);
    updateElement('kpi-cpu', `${data.cpu.usage_percentage.toFixed(0)}%`);

    // تحديث معلومات الذاكرة
    updateElement('memory-total', formatBytes(data.memory.total));
    updateElement('memory-used', formatBytes(data.memory.used));
    updateElement('memory-free', formatBytes(data.memory.free));
    updateElement('memory-percentage', `${data.memory.usage_percentage}%`);
    updateElement('memory-used-dup', formatBytes(data.memory.used));
    {
        const totalMem = Number(data.memory.total) || 0;
        const usedMem = Number(data.memory.used) || 0;
        const usedPct = totalMem > 0 ? (usedMem / totalMem) * 100 : 0;
        updateElement('kpi-memory', `${usedPct.toFixed(0)}%`);
    }

    // تحديث معلومات القرص
    updateElement('disk-total', formatBytes(data.disk.total));
    updateElement('disk-used', formatBytes(data.disk.used));
    updateElement('disk-free', formatBytes(data.disk.free));
    updateElement('disk-percentage', `${data.disk.usage_percentage}%`);
    updateElement('kpi-disk', `${data.disk.usage_percentage}%`);
    const diskProgress = document.getElementById('disk-progress');
    if (diskProgress) {
        diskProgress.style.width = `${data.disk.usage_percentage}%`;
        diskProgress.setAttribute('aria-valuenow', `${data.disk.usage_percentage}`);
    }

    // تحديث معلومات قاعدة البيانات
    updateElement('db-type', `${data.database.type} (${data.database.version})`);
    updateElement('db-name', data.database.name);
    updateElement('db-size', data.database.size);
    updateElement('kpi-db-size', data.database.size);
    if (typeof data.uptime !== 'undefined') updateElement('uptime', data.uptime);
}

function updateLastUpdated(timestamp) {
    const date = new Date(timestamp * 1000);
    const el = document.getElementById('last-updated');
    if (!el) return;
    const span = el.querySelector('span');
    if (span) span.textContent = date.toLocaleString();
    else el.textContent = date.toLocaleString();
}

function updateElement(id, value) {
    const element = document.getElementById(id);
    if (element) element.textContent = value;
}

function formatBytes(bytes) {
if (bytes === 0) return '0 B';
const k = 1024;
const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
const i = Math.floor(Math.log(bytes) / Math.log(k));
return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function pushHistory(arr, val) {
arr.push(parseFloat(val.toFixed ? val.toFixed(2) : val));
if (arr.length > HISTORY_MAX) arr.shift();
}