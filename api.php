<?php
// Endpoint JSON: api.php?id=ID1XR5V779
require __DIR__ . '/scraper.php';

header('Content-Type: application/json; charset=utf-8');

$result = get_invitation(isset($_GET['id']) ? trim($_GET['id']) : '');
if (!$result['ok']) {
    http_response_code(is_valid_invitation_id($result['id']) ? 502 : 400);
} elseif (!$result['found']) {
    http_response_code(404);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
