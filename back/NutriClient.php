<?php
declare(strict_types=1);

/**
 * NutriClient
 * - dataCd(P/D/R 또는 '가공식품'/'음식'/'원재료성')에 따라 서로 다른 URL로 라우팅
 * - 요청 파라미터는 세 URL에서 동일(예: pageNo, numOfRows, type, foodNm)
 * - dataCd는 쿼리스트링에서 제외함 (URL만 달라짐)
**/
class NutriClient
{
    private string $serviceKey;
    /** @var array<string, string|array{url:string, default_type?:string, param_overrides?:array<string,string>}> */
    private array $endpoints;
    /** @var array<string,string> */
    private array $paramNames;
    private string $globalDefaultType;

    public function __construct(array $config)
    {
        $this->serviceKey        = $config['serviceKey'] ?? '';
        $this->endpoints         = $config['endpoints']  ?? [];
        $this->paramNames        = $config['param_names'] ?? [];
        $this->globalDefaultType = $config['default_type'] ?? 'json';

        if ($this->serviceKey === '') {
            throw new \InvalidArgumentException('serviceKey가 비어 있습니다.');
        }
        if (empty($this->endpoints)) {
            throw new \InvalidArgumentException('endpoints 설정이 비어 있습니다.');
        }
    }

    /**
     * 검색 실행
     * @param string      $dataCd   'P'|'D'|'R' 또는 '가공식품'|'음식'|'원재료성' (URL 라우팅 전용)
     * @param string      $foodNm   검색어(필수)
     * @param int         $pageNo
     * @param int         $numOfRows
     * @param string|null $type     응답 포맷 지정(json/xml). 생략 시 엔드포인트 기본값 → 전역 기본값 순
     * @param array       $opts     동일 파라미터 체계에서 허용되는 추가 옵션
     * @return array                파싱된 배열(JSON 우선, 실패 시 XML 파싱)
     */
    public function search(
        string $dataCd,
        string $foodNm,
        int $pageNo = 1,
        int $numOfRows = 10,
        ?string $type = null,
        array $opts = []
    ): array {
        $code = $this->normalizeDataCd($dataCd);

        // 1) 엔드포인트/옵션 확정
        [$baseUrl, $endpointDefaultType, $paramOverrides] = $this->resolveEndpoint($code);
        $baseUrl = trim($baseUrl);
        if ($baseUrl === '') {
            throw new \RuntimeException("엔드포인트를 찾을 수 없습니다(dataCd={$code}).");
        }

        // 2) 파라미터 키 구성 (공통)
        $p = $this->paramNames;
        $typeKey      = $p['type']      ?? 'type';
        $pageNoKey    = $p['pageNo']    ?? 'pageNo';
        $numOfRowsKey = $p['numOfRows'] ?? 'numOfRows';
        // 엔드포인트별 foodNm 키 오버라이드 가능
        $foodNmKey    = $paramOverrides['foodNm'] ?? ($p['foodNm'] ?? 'foodNm');

        // 3) 실제 전송 타입 결정 (엔드포인트 기본 → 전역 기본 → 호출자가 준 값 우선 적용)
        $finalType = $type ?? $endpointDefaultType ?? $this->globalDefaultType;

        // 4) 공통 파라미터 준비 (dataCd는 넣지 않음)
        $queryCommon = [
            $typeKey      => $finalType,
            $pageNoKey    => $pageNo,
            $numOfRowsKey => $numOfRows,
            $foodNmKey    => $this->requireNonEmpty($foodNm, 'foodNm'),
        ];
        // 호출자가 준 추가 파라미터 병합
        foreach ($opts as $k => $v) {
            if ($v === null || $v === '') continue;
            $queryCommon[$k] = $v;
        }

        // 5) URL 조합 (serviceKey는 별도 부착; 재인코딩 방지)
        $url = $this->buildUrlWithKey($baseUrl, $this->serviceKey, $queryCommon);

        // 6) 요청 및 파싱
        return $this->requestAndParse($url);
    }

    // ---------------- 내부 유틸 ----------------

    /** dataCd 정규화: 라벨 → 코드 */
    private function normalizeDataCd(string $dataCd): string
    {
        $v = mb_strtoupper(trim($dataCd), 'UTF-8');
        if (in_array($v, ['P','D','R'], true)) return $v;

        $k = trim($dataCd);
        return match ($k) {
            '가공식품' => 'P',
            '음식'     => 'D',
            '원재료성' => 'R',
            default    => throw new \InvalidArgumentException("dataCd는 P/D/R 또는 '가공식품/음식/원재료성' 중 하나여야 합니다."),
        };
    }

    /**
     * 엔드포인트/옵션 해석
     * @return array{0:string,1:?string,2:array<string,string>}
     */
    private function resolveEndpoint(string $code): array
    {
        if (!array_key_exists($code, $this->endpoints)) {
            throw new \RuntimeException("엔드포인트 설정에 {$code}가 없습니다.");
        }
        $e = $this->endpoints[$code];

        if (is_string($e)) {
            return [$e, null, []]; // url, default_type 없음, overrides 없음
        }
        if (is_array($e)) {
            $url            = $e['url'] ?? '';
            $defaultType    = $e['default_type'] ?? null;
            $paramOverrides = $e['param_overrides'] ?? [];
            if (!is_string($url) || $url === '') {
                throw new \RuntimeException("엔드포인트 URL이 비어 있습니다(dataCd={$code}).");
            }
            return [$url, $defaultType, $paramOverrides];
        }
        throw new \RuntimeException("엔드포인트 형식이 올바르지 않습니다(dataCd={$code}).");
    }

    /**
     * 서비스키 안전 부착 + 나머지 쿼리 조합
     * - serviceKey는 이미 인코딩된 경우 그대로 사용, 아니면 rawurlencode 1회
     * - 나머지 파라미터는 RFC3986로 http_build_query
     */
    private function buildUrlWithKey(string $baseUrl, string $serviceKey, array $restParams): string
    {
        $baseUrl = trim($baseUrl);

        // serviceKey 인코딩 여부 판단
        $skIsEncoded = (bool)preg_match('/%[0-9A-Fa-f]{2}/', $serviceKey);
        $skFinal     = $skIsEncoded ? $serviceKey : rawurlencode($serviceKey);

        // 나머지 쿼리
        $restQuery = http_build_query($restParams, arg_separator: '&', encoding_type: PHP_QUERY_RFC3986);

        // baseUrl에 기존 쿼리 유무
        $hasQuery = (parse_url($baseUrl, PHP_URL_QUERY) !== null);
        $sep = $hasQuery ? '&' : '?';

        $url = $baseUrl . $sep . 'serviceKey=' . $skFinal;
        if ($restQuery !== '') {
            $url .= '&' . $restQuery;
        }
        return $url;
    }

    /** HTTP 호출 + JSON→XML 파싱 */
    private function requestAndParse(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json, application/xml;q=0.9, */*;q=0.8',
            ],
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("cURL error: {$err}");
        }
        curl_close($ch);

        if ($code < 200 || $code >= 300) {
            // 서버가 반려한 경우 그대로 본문을 담아 원인 파악 가능
            throw new \RuntimeException("HTTP {$code} 응답: {$body}");
        }

        // JSON 시도
        $data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }

        // XML 폴백
        $xml = @simplexml_load_string($body);
        if ($xml !== false) {
            return json_decode(json_encode($xml), true);
        }

        throw new \RuntimeException("응답 파싱 실패: {$body}");
    }

    /** 빈 값 검증 */
    private function requireNonEmpty(string $value, string $name): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException("{$name}(을)를 입력하세요.");
        }
        return $value;
    }

}