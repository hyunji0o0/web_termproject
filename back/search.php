<?php
// CORS (필요시 조정)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

try {
    $config = require __DIR__ . '/keys.php';
    require __DIR__ . '/NutriClient.php';
    $client = new NutriClient($config);

    // JSON 바디 받기 (POST)
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        $payload = $_POST; // 혹시 폼 전송이면
    }

    $dataCd      = $payload['dataCd']      ?? '';   // P / D / R
    $searchField = $payload['searchField'] ?? 'foodNm'; // foodNm / foodLv4Nm
    $keyword     = $payload['keyword']     ?? '';

    $pageNo      = (int)($payload['pageNo']    ?? 1);
    $numOfRows   = (int)($payload['numOfRows'] ?? 10);
    $type        = $payload['type']           ?? 'json';

    // 사용자가 엉뚱한 값을 보내면 기본값으로 보정
    $allowedFields = ['foodNm', 'foodLv4Nm'];
    if (!in_array($searchField, $allowedFields, true)) {
        $searchField = 'foodNm';
    }

    $result = $client->search($dataCd, $searchField, $keyword, $pageNo, $numOfRows, $type);

    echo json_encode(
        ['ok' => true, 'data' => $result],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(
        ['ok' => false, 'error' => $e->getMessage()],
        JSON_UNESCAPED_UNICODE
    );
}