<?php
$tokenFile = '/tmp/webhook-token';
if (!file_exists($tokenFile)) {
    http_response_code(500);
    exit;
}

$expectedToken = trim(file_get_contents($tokenFile));
$receivedToken = $_SERVER['HTTP_X_GOOG_CHANNEL_TOKEN'] ?? '';

if ($receivedToken !== $expectedToken) {
    http_response_code(403);
    exit;
}

// Google sends "sync" when the channel is first created — just acknowledge
$state = $_SERVER['HTTP_X_GOOG_RESOURCE_STATE'] ?? '';
if ($state === 'sync') {
    http_response_code(200);
    exit;
}

// Rate limit: ignore if last sync was less than 30 seconds ago
$lastFile = '/tmp/sync.last';
if (file_exists($lastFile) && (time() - filemtime($lastFile)) < 30) {
    http_response_code(200);
    exit;
}
touch($lastFile);

// Trigger sync in background (sync.py handles its own lock)
exec('/usr/local/bin/run-sync.sh > /proc/1/fd/1 2>&1 &');
http_response_code(200);
