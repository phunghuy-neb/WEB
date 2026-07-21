<?php
// ======== BACKEND: xử lý dữ liệu ========
require __DIR__ . '/includes/functions.php';

session_unset();
session_destroy();

header('Location: ' . BASE_URL . 'login.php');
exit;
