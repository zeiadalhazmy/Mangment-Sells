<?php
declare(strict_types=1);
require_once DIR . '/lib/auth.php';
auth_logout();
header('Location: /login.php');
exit;
