<?php
// CORS가 필요하면 아래 두 줄 주석 해제 (다른 포트/도메인에서 호출 시)
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Content-Type');

header('Content-Type: application/json; charset=utf-8');

try {
    $config = require __DIR__ . '/keys.php';
    require __DIR__ . '/NutriClient.php';
    $client = new NutriClient($config);

    // JSON 바디 받기 (POST)
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {$payload = $_POST;}
    $dataCd    = $payload['dataCd']    ?? '';
    $foodNm     = $payload['foodNm']     ?? '';
    $pageNo     = (int)($payload['pageNo']    ?? 1);
    $numOfRows  = (int)($payload['numOfRows'] ?? 10);
    $type       = $payload['type']       ?? 'json';

    $extra = $payload['extra'] ?? [];

    $result = $client->search($dataCd, $foodNm, $pageNo, $numOfRows, $type);

    echo json_encode(['ok' => true, 'data' => $result], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}