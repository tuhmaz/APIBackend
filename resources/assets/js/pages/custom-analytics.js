document.addEventListener('DOMContentLoaded', function() {
  // Initialize Bootstrap tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Initialize ApexCharts for visitors chart
  const visitorsChartEl = document.querySelector('#visitorsChart');
  if (visitorsChartEl) {
    const visitorsData = JSON.parse(visitorsChartEl.dataset.visitors || '[]');
    const visitorsCategories = JSON.parse(visitorsChartEl.dataset.categories || '[]');

    const visitorsChart = new ApexCharts(visitorsChartEl, {
      chart: {
        height: 300,
        type: 'line',
        toolbar: {
          show: false
        }
      },
      series: [{
        name: 'الزوار',
        data: visitorsData
      }],
      xaxis: {
        categories: visitorsCategories,
        labels: {
          style: {
            cssClass: 'text-muted'
          }
        },
        axisBorder: {
          show: false
        },
        axisTicks: {
          show: false
        }
      },
      yaxis: {
        labels: {
          style: {
            cssClass: 'text-muted'
          }
        }
      },
      colors: ['#696cff'],
      stroke: {
        curve: 'smooth',
        width: 3
      },
      grid: {
        borderColor: '#f1f1f1',
        padding: {
          top: 10,
          bottom: -10
        }
      },
      tooltip: {
        shared: true
      },
      legend: {
        show: false
      },
      responsive: [{
        breakpoint: 600,
        options: {
          chart: {
            height: 240
          }
        }
      }]
    });
    visitorsChart.render();
  }

  // Initialize ApexCharts for countries chart
  const countriesChartEl = document.querySelector('#countriesChart');
  if (countriesChartEl) {
    const countriesData = JSON.parse(countriesChartEl.dataset.data || '[]');
    const countriesCategories = JSON.parse(countriesChartEl.dataset.categories || '[]');

    const countriesChart = new ApexCharts(countriesChartEl, {
      chart: {
        height: 300,
        type: 'bar',
        toolbar: {
          show: false
        }
      },
      plotOptions: {
        bar: {
          horizontal: true,
          barHeight: '40%',
          distributed: true
        }
      },
      series: [{
        name: 'الزوار',
        data: countriesData
      }],
      xaxis: {
        categories: countriesCategories,
        labels: {
          style: {
            cssClass: 'text-muted'
          }
        }
      },
      yaxis: {
        labels: {
          style: {
            cssClass: 'text-muted'
          }
        }
      },
      colors: [
        '#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0',
        '#3F51B5', '#546E7A', '#D4526E', '#8D5B4C', '#F86624'
      ],
      legend: {
        show: false
      },
      tooltip: {
        y: {
          formatter: function(val) {
            return val + ' زائر';
          }
        }
      }
    });
    countriesChart.render();
  }
});
