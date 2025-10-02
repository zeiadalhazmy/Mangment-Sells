<?php
// admin_guard.php
declare(strict_types=1);
require_once DIR . '/lib/auth.php';
auth_require_admin();
