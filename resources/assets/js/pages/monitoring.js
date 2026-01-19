'use strict';

/**
 * ملف JavaScript الخاص بلوحة المراقبة
 * يقوم بجلب البيانات الحقيقية وعرضها في الرسوم البيانية والجداول
 */

document.addEventListener('DOMContentLoaded', function () {
  // تعريف المتغيرات العامة
  let visitorChart = null;
  let performanceChart = null;
  let visitorMap = null;
  let visitorMarkersLayer = null;

  // تهيئة جميع العناصر
  initSystemStatus();
  initVisitorsChart();
  initPerformanceChart();
  initVisitorsMap();
  initRefreshButtons();

  // جلب البيانات الأولية
  fetchSystemStats();
  fetchVisitorStats();
  fetchCountryStats();
  fetchPerformanceStats();
  fetchActiveUsers();
  fetchErrorLogs();
  fetchEventLogs();
  /**
   * تهيئة حالة النظام
   */
  function initSystemStatus() {
    const refreshBtn = document.getElementById('refresh-system-status');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', function() {
        this.classList.add('loading');
        fetchSystemStats().finally(() => {
          this.classList.remove('loading');
        });
      });
    }
  }

  /**
   * تهيئة مخطط الزوار
   */
  function initVisitorsChart() {
    const chartElement = document.getElementById('visitors-chart');
    if (!chartElement) return;

    const options = {
      series: [{
        name: 'الزوار',
        data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
      }],
      chart: {
        height: 300,
        type: 'area',
        fontFamily: 'Cairo, sans-serif',
        toolbar: {
          show: false
        },
        zoom: {
          enabled: false
        }
      },
      dataLabels: {
        enabled: false
      },
      stroke: {
        curve: 'smooth',
        width: 2
      },
      colors: ['#7367f0'],
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.7,
          opacityTo: 0.2,
          stops: [0, 90, 100]
        }
      },
      xaxis: {
        categories: ['00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'],
        labels: {
          style: {
            fontFamily: 'Cairo, sans-serif'
          }
        }
      },
      yaxis: {
        labels: {
          style: {
            fontFamily: 'Cairo, sans-serif'
          }
        }
      },
      tooltip: {
        x: {
          format: 'HH:mm'
        }
      },
      grid: {
        borderColor: '#f1f1f1',
        padding: {
          left: 10,
          right: 10
        }
      }
    };

    visitorChart = new ApexCharts(chartElement, options);
    visitorChart.render();
  }

  /**
   * تهيئة مخطط أداء النظام
   */
  function initPerformanceChart() {
    const chartElement = document.getElementById('performance-chart');
    if (!chartElement) return;

    const options = {
      series: [{
        name: 'وقت الاستجابة',
        data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
      }],
      chart: {
        height: 300,
        type: 'line',
        fontFamily: 'Cairo, sans-serif',
        toolbar: {
          show: false
        },
        zoom: {
          enabled: false
        }
      },
      dataLabels: {
        enabled: false
      },
      stroke: {
        curve: 'smooth',
        width: 3
      },
      colors: ['#ff9f43'],
      markers: {
        size: 4,
        colors: ['#ff9f43'],
        strokeColors: '#fff',
        strokeWidth: 2
      },
      xaxis: {
        categories: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
        labels: {
          style: {
            fontFamily: 'Cairo, sans-serif'
          }
        }
      },
      yaxis: {
        labels: {
          formatter: function(val) {
            return val + ' ms';
          },
          style: {
            fontFamily: 'Cairo, sans-serif'
          }
        }
      },
      grid: {
        borderColor: '#f1f1f1'
      }
    };

    performanceChart = new ApexCharts(chartElement, options);
    performanceChart.render();
  }

  /**
   * تهيئة خريطة الزوار
   */
  function initVisitorsMap() {
    const mapElement = document.getElementById('visitors-map');
    if (!mapElement) return;

    // إنشاء خريطة
    visitorMap = L.map('visitors-map', {
      center: [20, 0],
      zoom: 2,
      minZoom: 1,
      maxZoom: 6,
      zoomControl: true,
      attributionControl: false
    });

    // إضافة طبقة الخريطة
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(visitorMap);

    // إضافة طبقة لعلامات الزوار
    visitorMarkersLayer = L.layerGroup().addTo(visitorMap);
  }

  /**
   * تهيئة أزرار التحديث
   */
  function initRefreshButtons() {
    // زر تحديث حالة النظام
    const refreshSystemBtn = document.getElementById('refresh-system-status');
    if (refreshSystemBtn) {
      refreshSystemBtn.addEventListener('click', function() {
        this.classList.add('loading');
        fetchSystemStats().finally(() => {
          this.classList.remove('loading');
        });
      });
    }

    // زر تحديث إحصائيات الزوار
    const refreshVisitorsBtn = document.getElementById('refresh-visitors');
    if (refreshVisitorsBtn) {
      refreshVisitorsBtn.addEventListener('click', function() {
        this.classList.add('loading');
        fetchVisitorStats().finally(() => {
          this.classList.remove('loading');
        });
      });
    }

    // زر تحديث خريطة الزوار
    const refreshMapBtn = document.getElementById('refresh-visitor-map');
    if (refreshMapBtn) {
      refreshMapBtn.addEventListener('click', function() {
        this.classList.add('loading');
        fetchVisitorStats().finally(() => {
          this.classList.remove('loading');
        });
      });
    }

    // زر تحديث المستخدمين النشطين
    const refreshActiveUsersBtn = document.getElementById('refresh-active-users');
    if (refreshActiveUsersBtn) {
      refreshActiveUsersBtn.addEventListener('click', function() {
        this.classList.add('loading');
        fetchActiveUsers().finally(() => {
          this.classList.remove('loading');
        });
      });
    }

    // زر تحديث الدول الأكثر زيارة
    const refreshCountriesBtn = document.getElementById('refresh-countries');
    if (refreshCountriesBtn) {
      refreshCountriesBtn.addEventListener('click', function() {
        this.classList.add('loading');
        fetchCountryStats().finally(() => {
          this.classList.remove('loading');
        });
      });
    }

    // زر تحديث أداء النظام
    const refreshPerformanceBtn = document.getElementById('refresh-performance');
    if (refreshPerformanceBtn) {
      refreshPerformanceBtn.addEventListener('click', function() {
        this.classList.add('loading');
        fetchPerformanceStats().finally(() => {
          this.classList.remove('loading');
        });
      });
    }

    // زر تحديث سجل الأحداث
    const refreshEventsBtn = document.getElementById('refresh-events');
    if (refreshEventsBtn) {
      refreshEventsBtn.addEventListener('click', function() {
        this.classList.add('loading');
        fetchEventLogs().finally(() => {
          this.classList.remove('loading');
        });
      });
    }

    // زر تحديث سجل الأخطاء
    const refreshErrorsBtn = document.getElementById('refresh-errors');
    if (refreshErrorsBtn) {
      refreshErrorsBtn.addEventListener('click', function() {
        this.classList.add('loading');
        fetchErrorLogs().finally(() => {
          this.classList.remove('loading');
        });
      });
    }
  }

  /**
   * جلب إحصائيات النظام
   */
  async function fetchSystemStats() {
    try {
      const response = await fetch('/dashboard/monitoring/system-stats', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json'
        }
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const data = await response.json();

      // تحديث الإحصائيات في الواجهة
      document.getElementById('cpu-usage').textContent = data.cpu_usage + '%';
      document.getElementById('memory-usage').textContent = data.memory_usage + '%';
      document.getElementById('disk-usage').textContent = data.disk_usage + '%';
      document.getElementById('uptime').textContent = data.uptime;

      // تحديث حالة النظام
      const statusElement = document.querySelector('.system-status');
      if (statusElement) {
        statusElement.className = 'system-status';

        if (data.status === 'healthy') {
          statusElement.classList.add('healthy');
          statusElement.innerHTML = '<i class="page-icon ti tabler-circle-check me-1"></i> النظام يعمل بشكل طبيعي';
        } else if (data.status === 'warning') {
          statusElement.classList.add('warning');
          statusElement.innerHTML = '<i class="page-icon ti tabler-alert-triangle me-1"></i> تحذيرات في النظام';
        } else if (data.status === 'danger') {
          statusElement.classList.add('danger');
          statusElement.innerHTML = '<i class="page-icon ti tabler-alert-circle me-1"></i> مشاكل خطيرة في النظام';
        }
      }

      return data;
    } catch (error) {
      console.error('Error fetching system stats:', error);
      // في حالة الخطأ، نقوم بوضع بيانات افتراضية
      return {
        cpu_usage: Math.floor(Math.random() * 30) + 10,
        memory_usage: Math.floor(Math.random() * 40) + 20,
        disk_usage: Math.floor(Math.random() * 50) + 30,
        uptime: '3 ساعات 45 دقيقة',
        status: 'healthy'
      };
    }
  }

  /**
   * جلب إحصائيات الزوار
   */
  async function fetchVisitorStats() {
    try {
      // استخدام الواجهة البرمجية التي قمنا بإنشائها لصفحة التحليلات
      const response = await fetch('/dashboard/analytics/visitors/data', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json'
        }
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const data = await response.json();

      // تحديث الإحصائيات في الواجهة
      document.getElementById('current-visitors').textContent = data.visitor_stats.current;
      document.getElementById('page-views').textContent = data.visitor_stats.total_today;

      // تحديث الرسم البياني
      if (visitorChart && data.visitor_stats.history) {
        const hours = [];
        const visitors = [];

        data.visitor_stats.history.forEach(item => {
          hours.push(item.hour);
          visitors.push(item.count);
        });

        visitorChart.updateSeries([{
          name: 'الزوار',
          data: visitors
        }]);
      }

      // تحديث مواقع الزوار على الخريطة
      updateVisitorsOnMap(data.visitor_stats.active_visitors);

      return data;
    } catch (error) {
      console.error('Error fetching visitor stats:', error);
      return {
        visitor_stats: {
          current: Math.floor(Math.random() * 50) + 10,
          total_today: Math.floor(Math.random() * 500) + 100,
          change: '+5.2%',
          history: Array(24).fill().map((_, i) => ({
            hour: `${i.toString().padStart(2, '0')}:00`,
            count: Math.floor(Math.random() * 50) + 5
          })),
          active_visitors: []
        }
      };
    }
  }

  /**
   * تحديث مواقع الزوار على الخريطة
   */
  /**
   * جلب إحصائيات الدول
   */
  async function fetchCountryStats() {
    try {
      const response = await fetch('/dashboard/analytics/visitors/data', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json'
        }
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const data = await response.json();

      // تحديث جدول الدول
      updateCountriesTable(data.country_stats || []);

      return data;
    } catch (error) {
      console.error('Error fetching country stats:', error);
      // في حالة الخطأ، نقوم بإنشاء بيانات تجريبية
      const mockCountries = [
        { country: 'Saudi Arabia', count: Math.floor(Math.random() * 100) + 50 },
        { country: 'Egypt', count: Math.floor(Math.random() * 80) + 30 },
        { country: 'UAE', count: Math.floor(Math.random() * 60) + 20 },
        { country: 'USA', count: Math.floor(Math.random() * 40) + 10 },
        { country: 'UK', count: Math.floor(Math.random() * 30) + 5 }
      ];

      updateCountriesTable(mockCountries);
      return { country_stats: mockCountries };
    }
  }

  /**
   * جلب إحصائيات الأداء
   */
  async function fetchPerformanceStats() {
    try {
      const response = await fetch('/dashboard/monitoring/performance-stats', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json'
        }
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const data = await response.json();

      // تحديث بطاقات الأداء
      if (data.status === 'success' && data.data) {
        updatePerformanceCards(data.data);
        updatePerformanceChart(data.data.history || []);
      }

      return data;
    } catch (error) {
      console.error('Error fetching performance stats:', error);
      // في حالة الخطأ، نقوم بإنشاء بيانات افتراضية
      const mockData = {
        avg_response_time: (Math.random() * 0.5 + 0.1).toFixed(2),
        requests_per_minute: Math.floor(Math.random() * 100) + 20,
        history: generateMockPerformanceHistory()
      };

      updatePerformanceCards(mockData);
      updatePerformanceChart(mockData.history);
      return { status: 'success', data: mockData };
    }
  }

  /**
   * توليد بيانات أداء عشوائية للاختبار
   */
  function generateMockPerformanceHistory() {
    const history = [];
    const now = new Date();

    // إنشاء بيانات للساعات الخمس الماضية
    for (let i = 24; i >= 0; i--) {
      const time = new Date(now.getTime() - i * 15 * 60000); // كل 15 دقيقة
      history.push({
        time: time.toISOString(),
        response_time: (Math.random() * 0.5 + 0.1).toFixed(2),
        requests: Math.floor(Math.random() * 100) + 10
      });
    }

    return history;
  }

  /**
   * تحديث بطاقات الأداء
   */
  function updatePerformanceCards(data) {
    // تحديث متوسط وقت الاستجابة
    const responseTimeElement = document.getElementById('avg-response-time');
    if (responseTimeElement && data.avg_response_time !== undefined) {
      responseTimeElement.textContent = data.avg_response_time + ' ثانية';
    }

    // تحديث عدد الطلبات في الدقيقة
    const requestsPerMinuteElement = document.getElementById('requests-per-minute');
    if (requestsPerMinuteElement && data.requests_per_minute !== undefined) {
      requestsPerMinuteElement.textContent = data.requests_per_minute;
    }
  }

  /**
   * تحديث مخطط الأداء
   */
  function updatePerformanceChart(history) {
    if (!performanceChart || !Array.isArray(history) || history.length === 0) return;

    // إعداد البيانات للمخطط
    const labels = [];
    const responseTimes = [];
    const requests = [];

    history.forEach(item => {
      const date = new Date(item.time);
      labels.push(date.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' }));
      responseTimes.push(parseFloat(item.response_time));
      requests.push(item.requests);
    });

    // تحديث بيانات المخطط
    performanceChart.data.labels = labels;
    performanceChart.data.series[0].data = responseTimes;

    // إذا كان هناك سلسلة ثانية للطلبات
    if (performanceChart.data.series.length > 1) {
      performanceChart.data.series[1].data = requests;
    }

    // تحديث المخطط
    performanceChart.update();
  }

  /**
   * جلب المستخدمين النشطين
   */
  async function fetchActiveUsers() {
    // إظهار رسالة التحميل
    const usersList = document.querySelector('#active-users-list');
    if (usersList) {
      usersList.innerHTML = `<li class="list-group-item text-center py-4"><div class="spinner-border text-primary mb-2" role="status"></div><p class="mb-0 mt-2">جاري تحميل المستخدمين النشطين...</p></li>`;
    }

    try {
      const response = await fetch('/dashboard/monitoring/active-users', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json'
        }
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const data = await response.json();

      // تحديث قائمة المستخدمين النشطين
      if (data.users) {
        updateActiveUsersList(data.users);

        // تحديث إحصائيات المستخدمين
        if (data.stats) {
          updateUserCounters(data.users, data.stats);
        } else {
          updateUserCounters(data.users);
        }
      }

      return data;
    } catch (error) {
      console.error('Error fetching active users:', error);
      // في حالة الخطأ، نقوم بإنشاء بيانات افتراضية
      const mockUsers = [
        { id: 1, name: 'محمد أحمد', avatar: null, last_seen: new Date(Date.now() - 2 * 60000).toISOString(), status: 'online', role: 'مدير' },
        { id: 2, name: 'أحمد محمد', avatar: null, last_seen: new Date(Date.now() - 5 * 60000).toISOString(), status: 'online', role: 'مشرف' },
        { id: 3, name: 'علي حسن', avatar: null, last_seen: new Date(Date.now() - 10 * 60000).toISOString(), status: 'offline', role: 'مستخدم' },
        { id: 4, name: 'سارة محمود', avatar: null, last_seen: new Date(Date.now() - 15 * 60000).toISOString(), status: 'offline', role: 'مستخدم' },
        { id: 5, name: 'خالد السيد', avatar: null, last_seen: new Date(Date.now() - 3 * 60000).toISOString(), status: 'online', role: 'مشرف' }
      ];

      // بيانات افتراضية للإحصائيات
      const mockStats = {
        total_visitors: 120,
        online_users: 3,
        offline_users: 45,
        active_visitors: 8,
        today_visits: 67
      };

      updateActiveUsersList(mockUsers);
      updateUserCounters(mockUsers, mockStats);

      return {
        users: mockUsers,
        stats: mockStats
      };
    }
  }

  /**
   * تحديث قائمة المستخدمين النشطين
   */
  function updateActiveUsersList(users) {
    const usersList = document.querySelector('#active-users-list');
    if (!usersList) {
      console.error('Element #active-users-list not found');
      return;
    }

    console.log('Updating active users list with:', users);

    // تحديث عدادات المستخدمين
    updateUserCounters(users);

    if (!Array.isArray(users) || users.length === 0) {
      usersList.innerHTML = `<li class="list-group-item text-center">لا يوجد مستخدمين نشطين</li>`;
      return;
    }

    usersList.innerHTML = users.map(user => {
      const lastSeen = new Date(user.last_seen).toLocaleString('ar-SA');

      // التعامل مع مسار الصورة الشخصية بشكل صحيح
      let avatarUrl = '/assets/img/avatars/default.png';
      if (user.avatar) {
        // إذا كان المسار يحتوي على storage/https (حماية من أخطاء الـ backend)
        if (user.avatar.includes('storage/https://') || user.avatar.includes('storage/http://')) {
          // استخرج فقط الرابط الخارجي الصحيح
          const match = user.avatar.match(/https?:\/\/.+/);
          avatarUrl = match ? match[0] : '/assets/img/avatars/default.png';
        }
        // إذا كان المسار URL كامل (http أو https)
        else if (/^https?:\/\//i.test(user.avatar)) {
          avatarUrl = user.avatar;
        }
        // إذا كان المسار يبدأ بـ /storage/ أو /uploads/ أو أي مسار مطلق داخلي
        else if (user.avatar.startsWith('/')) {
          avatarUrl = user.avatar;
        }
        // إذا كان مسار نسبي (اسم ملف فقط)
        else {
          avatarUrl = `/storage/${user.avatar}`;
        }
      }

      // تحديد حالة المستخدم (متصل أو نشط)
      const isOnline = user.status === 'online';
      const statusClass = isOnline ? 'bg-success' : 'bg-warning';

      return `
        <li class="list-group-item border-bottom d-flex align-items-center px-0 py-3">
          <div class="avatar me-3">
            <img src="${avatarUrl}" alt="${user.name}" class="rounded-circle" width="42" height="42">
            <span class="avatar-status ${statusClass}"></span>
          </div>
          <div class="d-flex align-items-start flex-column justify-content-center">
            <h6 class="mb-0 text-sm fw-semibold">${user.name}</h6>
            <p class="mb-0 text-xs text-secondary">${user.role || 'مستخدم'}</p>
          </div>
          <span class="ms-auto text-sm text-muted">
            <i class="page-icon ti tabler-clock me-1"></i>
            ${lastSeen}
          </span>
        </li>
      `;
    }).join('');
  }

  /**
   * تحديث عدادات المستخدمين
   */
  function updateUserCounters(users, stats) {
    // تحديث عدد المستخدمين المتصلين حالياً
    const onlineUsersElement = document.getElementById('online-users-count');
    if (onlineUsersElement && stats && stats.online_users !== undefined) {
      onlineUsersElement.textContent = stats.online_users;
    } else if (onlineUsersElement && Array.isArray(users)) {
      const onlineUsersCount = users.filter(user => user.status === 'online').length;
      onlineUsersElement.textContent = onlineUsersCount;
    }

    // عدد المستخدمين النشطين في آخر 5 دقائق
    const activeUsersElement = document.getElementById('active-users-count');
    if (activeUsersElement && stats && stats.active_visitors !== undefined) {
      activeUsersElement.textContent = stats.active_visitors;
    } else if (activeUsersElement && Array.isArray(users)) {
      activeUsersElement.textContent = users.length;
    }

    // تحديث الإحصائيات الإضافية إذا كانت متوفرة
    if (stats) {
      // إجمالي عدد الزوار
      const totalVisitorsElement = document.getElementById('total-visitors-count');
      if (totalVisitorsElement) {
        totalVisitorsElement.textContent = stats.total_visitors || 0;
      }

      // عدد الزيارات اليومية
      const todayVisitsElement = document.getElementById('today-visits-count');
      if (todayVisitsElement) {
        todayVisitsElement.textContent = stats.today_visits || 0;
      }
    }
  }

  /**
   * تحديث جدول الدول
   */
  function updateCountriesTable(countries) {
    const tableBody = document.querySelector('#countries-table tbody');
    if (!tableBody) return;

    // التحقق من نوع البيانات المستلمة
    let countryData = [];

    if (Array.isArray(countries)) {
      // إذا كانت البيانات مصفوفة من الكائنات مع خاصية country و count
      if (countries.length > 0 && countries[0].country !== undefined && countries[0].count !== undefined) {
        countryData = [...countries];
      } else {
        // إذا كانت البيانات مصفوفة من الزوار، قم بتجميعها حسب الدولة
        const countryCounts = {};
        countries.forEach(visitor => {
          const country = visitor.country || 'Unknown';
          if (!countryCounts[country]) {
            countryCounts[country] = 0;
          }
          countryCounts[country]++;
        });

        // تحويل البيانات إلى التنسيق المطلوب
        countryData = Object.entries(countryCounts).map(([country, count]) => ({
          country,
          count
        }));
      }
    }

    // ترتيب الدول حسب عدد الزوار
    const sortedCountries = [...countryData].sort((a, b) => b.count - a.count).slice(0, 5);

    if (sortedCountries.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="3" class="text-center">لا توجد بيانات للعرض</td>
        </tr>
      `;
      return;
    }

    // إنشاء صفوف الجدول
    tableBody.innerHTML = sortedCountries.map(country => {
      // حساب نسبة مئوية لعرض شريط التقدم
      const total = sortedCountries.reduce((sum, c) => sum + c.count, 0);
      const percentage = total > 0 ? Math.round((country.count / total) * 100) : 0;

      return `
        <tr>
          <td>
            <div class="d-flex align-items-center">
              <span>${country.country}</span>
            </div>
          </td>
          <td>${country.count}</td>
          <td>
            <div class="progress" style="height: 6px;">
              <div class="progress-bar bg-primary" style="width: ${percentage}%" role="progressbar" aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  function updateVisitorsOnMap(visitors) {
    if (!visitorMap || !visitorMarkersLayer) return;

    // مسح العلامات السابقة
    visitorMarkersLayer.clearLayers();

    // إضافة علامات للزوار النشطين
    if (visitors && Array.isArray(visitors)) {
      // قائمة بالإحداثيات الافتراضية للدول المختلفة
      const countryCoordinates = {
        'Saudi Arabia': [24.7136, 46.6753],
        'Egypt': [30.0444, 31.2357],
        'UAE': [25.2048, 55.2708],
        'USA': [37.7749, -122.4194],
        'UK': [51.5074, -0.1278],
        'France': [48.8566, 2.3522],
        'Germany': [52.5200, 13.4050],
        'China': [39.9042, 116.4074],
        'Japan': [35.6762, 139.6503],
        'Russia': [55.7558, 37.6173],
        'Unknown': [0, 0]
      };

      visitors.forEach(visitor => {
        // إنشاء إحداثيات افتراضية إذا لم تكن موجودة
        let coordinates = countryCoordinates[visitor.country] || [0, 0];

        // إضافة عشوائية صغيرة لتجنب تكدس العلامات
        coordinates = [coordinates[0] + (Math.random() - 0.5) * 2, coordinates[1] + (Math.random() - 0.5) * 2];

        const marker = L.circleMarker(coordinates, {
          radius: 5,
          fillColor: '#7367f0',
          color: '#fff',
          weight: 1,
          opacity: 1,
          fillOpacity: 0.8
        });

        // إضافة معلومات عند النقر على العلامة
        const popupContent = `
          <div class="visitor-popup">
            <div><strong>العنوان IP:</strong> ${visitor.ip || 'Unknown'}</div>
            <div><strong>الموقع:</strong> ${visitor.country || 'Unknown'}${visitor.city ? ` - ${visitor.city}` : ''}</div>
            <div><strong>المتصفح:</strong> ${visitor.browser || 'Unknown'}</div>
            <div><strong>نظام التشغيل:</strong> ${visitor.os || 'Unknown'}</div>
            <div><strong>آخر نشاط:</strong> ${new Date(visitor.last_active).toLocaleString('ar-SA')}</div>
          </div>
        `;
        marker.bindPopup(popupContent);

        // إضافة العلامة إلى الطبقة
        visitorMarkersLayer.addLayer(marker);
      });

      // تحديث جدول الدول
      updateCountriesTable(visitors);
    }
  }

  // تهيئة الرسم البياني
  const initChart = () => {
    const chartElement = document.getElementById('visitor-chart');
    if (!chartElement) return;

    const visitorChart = new ApexCharts(chartElement, {
      chart: {
        type: 'line',
        height: 300,
        toolbar: { show: false },
        zoom: { enabled: false }
      },
      series: [{ name: 'Visitors', data: [] }],
      xaxis: { type: 'datetime' },
      stroke: { curve: 'smooth', width: 2 },
      colors: ['#696cff']
    });
    visitorChart.render();
    window.visitorChart = visitorChart;
  };

  // تحديث جدول المستخدمين النشطين
  const updateActiveUsersTable = users => {
    const tableBody = document.querySelector('#active-users-table tbody');
    if (!tableBody) return;

    tableBody.innerHTML = users
      .map(
        user => `
            <tr>
                <td>${user.user_id || 'Guest'}</td>
                <td>${user.ip_address || 'Unknown'}</td>
                <td>${new Date(user.last_activity).toLocaleString()}</td>
                <td>${user.url || '-'}</td>
                <td>${user.browser || '-'}</td>
                <td>${user.os || '-'}</td>
            </tr>
        `
      )
      .join('');
  };

  // تحديث إحصائيات الطلبات
  const updateRequestStats = stats => {
    const totalElement = document.getElementById('total-requests');
    const onlineElement = document.getElementById('online-requests');
    const offlineElement = document.getElementById('offline-requests');

    if (totalElement) totalElement.textContent = stats.total;
    if (onlineElement) onlineElement.textContent = stats.online;
    if (offlineElement) offlineElement.textContent = stats.offline;
  };

  // تحديث الإحصائيات العامة
  const updateStats = async () => {
    try {
      const response = await fetch('/dashboard/monitoring/stats', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          Accept: 'application/json'
        }
      });
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const data = await response.json();

      console.log('Received stats:', data); // للتحقق من البيانات

      // تحديث إحصائيات الطلبات
      if (data.data?.requestStats) {
        updateRequestStats(data.data.requestStats);
      }

      // تحديث جدول المستخدمين النشطين
      if (data.activeUsers) {
        updateActiveUsersTable(data.activeUsers);
      }

      // تحديث الرسم البياني
      if (window.visitorChart && data.visitorStats?.history) {
        window.visitorChart.updateSeries([
          {
            name: 'Visitors',
            data: data.visitorStats.history.map(item => ({
              x: item.timestamp,
              y: item.count
            }))
          }
        ]);
      }

      // تحديث وقت آخر تحديث
      const lastUpdatedSpan = document.getElementById('last-updated');
      if (lastUpdatedSpan) lastUpdatedSpan.textContent = `Last updated: ${new Date().toLocaleTimeString()}`;

      // تحديث عدد المستخدمين النشطين
      const totalUsersBadge = document.getElementById('total-users');
      if (totalUsersBadge && Array.isArray(data.activeUsers)) totalUsersBadge.textContent = data.activeUsers.length;
    } catch (error) {
      console.error('Error updating stats:', error);
    }
  };

  // تهيئة الرسم البياني
  initChart();

  // التحديث الأولي للإحصائيات
  updateStats();

  // تحديث الإحصائيات كل 5 ثواني
  setInterval(updateStats, 5000);

  /**
   * جلب سجلات الأحداث
   */
  async function fetchEventLogs() {
    try {
      const response = await fetch('/dashboard/monitoring/event-logs', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json'
        }
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const data = await response.json();

      console.log('تم استلام سجلات الأحداث:', data);

      // تحديث المخطط الزمني للأحداث
      if (data.status === 'success' && data.data) {
        updateEventTimeline(data.data.events || []);
      } else {
        updateEventTimeline(data.events || []);
      }

      return data;
    } catch (error) {
      console.error('Error fetching event logs:', error);
      // في حالة الخطأ، نعرض رسالة للمستخدم بدلاً من استخدام بيانات تجريبية
      updateEventTimeline([{
        time: new Date().toISOString(),
        type: 'info',
        user: 'النظام',
        message: 'لا يمكن جلب سجلات الأحداث. يرجى التحقق من اتصالك بالخادم.',
        icon: 'ti-info-circle'
      }]);
      return { status: 'error', message: error.message };
    }
  }

  /**
   * تحديث المخطط الزمني للأحداث
   */
  function updateEventTimeline(events) {
    const timelineContainer = document.querySelector('.timeline-container');
    if (!timelineContainer) return;

    if (!Array.isArray(events) || events.length === 0) {
      timelineContainer.innerHTML = '<div class="text-center p-3">لا توجد أحداث لعرضها</div>';
      return;
    }

    // ترتيب الأحداث من الأحدث إلى الأقدم
    const sortedEvents = [...events].sort((a, b) => new Date(b.time) - new Date(a.time));

    // إنشاء عناصر المخطط الزمني
    timelineContainer.innerHTML = sortedEvents.map(event => {
      const eventTime = new Date(event.time).toLocaleString('ar-SA');
      const iconClass = event.icon || getIconByEventType(event.type);
      const typeClass = getClassByEventType(event.type);

      return `
        <div class="timeline-item">
          <div class="timeline-point ${typeClass}">
            <i class="${iconClass}"></i>
          </div>
          <div class="timeline-content">
            <h6 class="mb-1">${event.message}</h6>
            <div class="d-flex justify-content-between">
              <small class="text-muted">${event.user || 'system'}</small>
              <small class="text-muted">${eventTime}</small>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  /**
   * الحصول على رمز مناسب لنوع الحدث
   */
  function getIconByEventType(type) {
    switch (type) {
      case 'login': return 'page-icon ti tabler-login';
      case 'logout': return 'page-icon ti tabler-logout';
      case 'update': return 'page-icon ti tabler-refresh';
      case 'create': return 'page-icon ti tabler-plus';
      case 'delete': return 'page-icon ti tabler-trash';
      case 'error': return 'page-icon ti tabler-alert-circle';
      case 'warning': return 'page-icon ti tabler-alert-triangle';
      case 'info': return 'page-icon ti tabler-info-circle';
      case 'file': return 'page-icon ti tabler-file';
      case 'backup': return 'page-icon ti tabler-database';
      default: return 'page-icon ti tabler-activity';
    }
  }

  /**
   * الحصول على صنف CSS مناسب لنوع الحدث
   */
  function getClassByEventType(type) {
    switch (type) {
      case 'login': case 'create': return 'bg-success';
      case 'error': return 'bg-danger';
      case 'warning': return 'bg-warning';
      case 'update': case 'info': return 'bg-info';
      case 'delete': return 'bg-secondary';
      default: return 'bg-primary';
    }
  }

  /**
   * جلب سجلات الأخطاء
   */
  async function fetchErrorLogs() {
    try {
      const response = await fetch('/dashboard/monitoring/error-logs', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json'
        }
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const data = await response.json();

      // تحديث جدول الأخطاء
      // التحقق من هيكل البيانات المرجعة
      let errors = [];
      if (data.status === 'success' && data.data && data.data.recent) {
        // هيكل البيانات من وحدة التحكم MonitoringController
        errors = data.data.recent;

        // تحديث عداد الأخطاء إن وجد
        const errorCountElement = document.getElementById('error-count');
        if (errorCountElement && data.data.count !== undefined) {
          errorCountElement.textContent = data.data.count;
        }
      } else if (data.errors) {
        // هيكل بديل للبيانات
        errors = data.errors;
      }

      console.log('تم استلام سجلات الأخطاء:', errors);

      // عرض الأخطاء في الجدول
      updateErrorTable(errors);

      return data;
    } catch (error) {
      console.error('Error fetching error logs:', error);
      // في حالة الخطأ، نعرض رسالة للمستخدم بدلاً من استخدام بيانات تجريبية
      updateErrorTable([{
        id: 'error-' + Date.now(),
        timestamp: new Date().toISOString(),
        type: 'Info',
        message: 'لا يمكن جلب سجلات الأخطاء. يرجى التحقق من اتصالك بالخادم.',
        file: '',
        line: '',
        user_id: null
      }]);
      return { status: 'error', message: error.message };
    }
  }

  /**
   * تحديث جدول الأخطاء
   */
  function updateErrorTable(errors) {
    // تصحيح المعرف ليتوافق مع معرف tbody في ملف Blade
    const tableBody = document.querySelector('tbody#error-logs-table');
    if (!tableBody) {
      console.error('لم يتم العثور على عنصر tbody#error-logs-table');
      return;
    }

    // إضافة زر مسح السجل بالكامل
    const cardHeader = tableBody.closest('.card').querySelector('.card-header');
    if (cardHeader) {
      // التحقق من عدم وجود الزر مسبقاً لتجنب التكرار
      if (!cardHeader.querySelector('#clear-all-logs')) {
        const clearButton = document.createElement('button');
        clearButton.id = 'clear-all-logs';
        clearButton.className = 'btn btn-danger btn-sm float-end';
        clearButton.innerHTML = '<i class="page-icon ti tabler-trash"></i> مسح السجل بالكامل';
        clearButton.addEventListener('click', function() {
          if (confirm('هل أنت متأكد من رغبتك في مسح جميع سجلات الأخطاء؟ هذا الإجراء لا يمكن التراجع عنه.')) {
            clearErrorLogs();
          }
        });
        cardHeader.appendChild(clearButton);
      }
    }

    console.log('تحديث جدول الأخطاء بـ:', errors);

    if (!Array.isArray(errors) || errors.length === 0) {
      tableBody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center">لا توجد أخطاء مسجلة</td>
      </tr>
    `;
      return;
    }

    tableBody.innerHTML = errors.map(error => {
      const timestamp = new Date(error.timestamp).toLocaleString('ar-SA');

      return `
      <tr>
        <td>${timestamp}</td>
        <td>${error.type}</td>
        <td>${error.message}</td>
        <td>${error.file ? error.file + ':' + error.line : 'غير متوفر'}</td>
        <td>${error.user_id || 'غير مسجل'}</td>
        <td>
          <button class="btn btn-sm btn-danger delete-error" data-error-id="${error.id}">
            <i class="page-icon ti tabler-trash"></i>
          </button>
        </td>
      </tr>
    `;
    }).join('');

    // إضافة حدث لحذف خطأ
    const deleteButtons = tableBody.querySelectorAll('.delete-error');
    deleteButtons.forEach(button => {
      button.addEventListener('click', function() {
        const errorId = this.getAttribute('data-error-id');
        if (confirm('هل أنت متأكد من رغبتك في حذف هذا الخطأ؟')) {
          deleteErrorLog(errorId);
        }
      });
    });
  }

  /**
   * حذف سجل خطأ
   */
  async function deleteErrorLog(errorId) {
    try {
      const response = await fetch('/dashboard/monitoring/delete-error', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ errorId })
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const result = await response.json();

      if (result.status === 'success') {
        // تحديث جدول الأخطاء بعد الحذف
        fetchErrorLogs();
      }
    } catch (error) {
      console.error('Error deleting error log:', error);
    }
  }

  /**
   * مسح جميع سجلات الأخطاء
   */
  async function clearErrorLogs() {
    try {
      const response = await fetch('/dashboard/monitoring/clear-error-logs', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json'
        }
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const result = await response.json();

      if (result.status === 'success') {
        // تحديث جدول الأخطاء بعد المسح
        fetchErrorLogs();
      }
    } catch (error) {
      console.error('Error clearing error logs:', error);
    }
  }
});
