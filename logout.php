<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

logOutUser();
header('Location: /CQMS/login.php');
exit;
