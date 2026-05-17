<?php
session_start();
session_destroy();

$is_ajax = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (
    isset($_SERVER['HTTP_ACCEPT']) &&
    strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
);

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Fallback for normal requests
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/dotrack/index.php';
if (!preg_match('/^\/dotrack\//', $redirect)) {
    $redirect = '/dotrack/index.php';
}
header("Location: $redirect");
exit;
?>