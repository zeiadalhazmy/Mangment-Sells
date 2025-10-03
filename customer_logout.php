<?php
require_once __DIR__ . '/lib/auth_customer.php';
session_destroy();
header('Location: /');
