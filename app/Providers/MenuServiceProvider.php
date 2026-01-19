<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;
use App\Services\Menu;
use Illuminate\Support\Facades\Auth;

class MenuServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    $this->app->singleton('menu', function ($app) {
      return new Menu();
    });
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    // تسجيل حدث عند اكتمال تحميل التطبيق
    $this->app->booted(function () {
      $this->buildMenu();
    });

    // مشاركة بيانات القائمة مع جميع العروض
    View::composer('*', function ($view) {
      $menuData = $this->getMenuData();
      $view->with('menuData', $menuData);
    });
  }

  private function buildMenu()
  {
    try {
      $menuData = $this->getMenuData();
      app('menu')->setMenu($menuData);
    } catch (\Exception $e) {
      // تم إزالة تسجيل الخطأ
    }
  }

  private function filterMenuItems($menuData)
  {
    if (!is_array($menuData) && !is_object($menuData)) {
      return $menuData;
    }

    // استخدام Auth facade مباشرة
    $user = Auth::user();
    $authCheck = Auth::check();

    // نبني القائمة مع دعم إخفاء رؤوس الأقسام عند عدم وجود عناصر مرئية بعدها
    $result = [];
    $pendingHeader = null; // سيُضاف فقط إذا ظهر عنصر مرئي بعده

    foreach ($menuData as $item) {
      // معالجة رؤوس الأقسام menuHeader
      if (isset($item->menuHeader)) {
        // إذا كان للرأس صلاحية، نتحقق منها، وإلا نسمح به مؤقتاً
        $headerAllowed = !isset($item->permissions) || ($authCheck && $user && $user->can($item->permissions));
        $pendingHeader = $headerAllowed ? $item : null; // نخزن الرأس مؤقتاً ليظهر فقط قبل أول عنصر مرئي
        continue;
      }

      // تحقق صلاحيات العنصر
      $visible = true;
      if (isset($item->permissions)) {
        $visible = ($authCheck && $user && $user->can($item->permissions));
      }
      if (!$visible) {
        continue;
      }

      // تصفية عناصر القائمة الفرعية حسب الصلاحيات
      if (isset($item->submenu)) {
        $filteredSubmenu = [];
        foreach ($item->submenu as $subItem) {
          $subVisible = !isset($subItem->permissions) || ($authCheck && $user && $user->can($subItem->permissions));
          if ($subVisible) {
            $filteredSubmenu[] = $subItem;
          }
        }
        // إذا أصبحت القائمة الفرعية فارغة بعد التصفية، نخفي العنصر الرئيسي
        if (empty($filteredSubmenu)) {
          continue;
        }
        $item->submenu = $filteredSubmenu;
      }

      // عند أول عنصر مرئي بعد رأس معلق، نضيف الرأس ثم العنصر
      if ($pendingHeader) {
        $result[] = $pendingHeader;
        $pendingHeader = null;
      }

      $result[] = $item;
    }

    return collect($result)->values();
  }

  public function getMenuData(): array
  {
    try {
      $menuPath = resource_path('menu/verticalMenu.json');
      $verticalMenuJson = json_decode(file_get_contents($menuPath));

      // تحقق من وجود القائمة قبل تصفيتها
      if (isset($verticalMenuJson->menu)) {
        $verticalMenuJson->menu = $this->filterMenuItems($verticalMenuJson->menu);
      } else {
        // تم إزالة تسجيل التحذير
        $verticalMenuJson = new \stdClass();
        $verticalMenuJson->menu = [];
      }

      return [$verticalMenuJson];
    } catch (\Exception $e) {
      // تم إزالة تسجيل الخطأ
      $emptyMenu = new \stdClass();
      $emptyMenu->menu = [];
      return [$emptyMenu];
    }
  }
}
