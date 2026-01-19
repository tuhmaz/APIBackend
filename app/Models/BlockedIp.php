<?php

namespace App\Models;

/**
 * BlockedIp Model - Deprecated
 *
 * هذا النموذج تم إيقافه ويستخدم الآن فقط للتوافق العكسي.
 * يرجى استخدام BannedIp بدلاً منه.
 *
 * @deprecated استخدم BannedIp بدلاً من هذا النموذج
 * @see \App\Models\BannedIp
 */
class BlockedIp extends BannedIp
{
    // هذا النموذج يرث جميع الوظائف من BannedIp
    // تم إضافة Accessors و Mutators في BannedIp للتوافق
}
