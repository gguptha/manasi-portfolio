<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
logout_admin();
redirect('admin/index.php');
