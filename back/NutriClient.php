<?php
declare(strict_types=1);

/**
 * NutriClient
 * - dataCd(P/D/R 또는 한글 이름) 에 따라 서로 다른 URL로 라우팅
 * - 검색 필드(foodNm / foodLv4Nm)를 사용자가 선택할 수 있도록 지원
 */
class NutriClient
{
    private string $serviceKey;
    /** @var array<string,string> dataCd(P/D/R) => endpoint URL */
    private array $endpoints = [];
    private int   $timeout   = 10;
    private string $defaultType = 'json';

    /** 한글/영문 분류 → P/D/R 매핑 */
    private array $dataCdMap = [
        'P'        => 'P',
        'D'        => 'D',
        'R'        => 'R',
        '가공식품'  => 'P',
        '음식'      => 'D',
        '원재료성'  => 'R',
        'processed'=> 'P',
        'meal'     => 'D',
        'raw'      => 'R',
    ];

 
    public function __construct(array $config)
    {
        if (empty($config['serviceKey'])) {
            throw new \InvalidArgumentException('serviceKey가 설정되어 있지 않습니다.');
        }
        $this->serviceKey = $config['serviceKey'];

        if (empty($config['endpoints']) || !is_array($config['endpoints'])) {
            throw new \InvalidArgumentException('endpoints 설정이 필요합니다.');
        }
        // P/D/R 키만 사용
        foreach ($config['endpoints'] as $k => $url) {
            $k = strtoupper((string)$k);
            if (in_array($k, ['P', 'D', 'R'], true)) {
                $this->endpoints[$k] = (string)$url;
            }
        }
        if (empty($this->endpoints)) {
            throw new \InvalidArgumentException('P/D/R용 endpoint가 설정되어 있지 않습니다.');
        }

        if (isset($config['timeout'])) {
            $this->timeout = (int)$config['timeout'];
        }
        if (isset($config['defaultType'])) {
            $this->defaultType = (string)$config['defaultType'];
        }
    }


    public function search(
        string $dataCd,
        string $searchField,
        string $keyword,
        int    $pageNo = 1,
        int    $numOfRows = 10,
        string $type = 'json'
    ): array {
        $dataCd = $this->normalizeDataCd($dataCd);

        if (!isset($this->endpoints[$dataCd])) {
            throw new \InvalidArgumentException("지원하지 않는 dataCd 입니다: {$dataCd}");
        }
        $baseUrl = $this->endpoints[$dataCd];

        // 검색 필드 보정
        $searchField = trim($searchField);
        if (!in_array($searchField, ['foodNm', 'foodLv4Nm'], true)) {
            $searchField = 'foodNm';
        }

        $keyword = trim($keyword);
        if ($keyword === '') {
            throw new \InvalidArgumentException('검색어를 입력하세요.');
        }

        $pageNo    = max(1, $pageNo);
        $numOfRows = max(1, $numOfRows);
        $type      = $type !== '' ? $type : $this->defaultType;

        // 공통 파라미터
        $params = [
            'serviceKey' => $this->serviceKey,
            'type'       => $type,
            'pageNo'     => $pageNo,
            'numOfRows'  => $numOfRows,
            // dataCd는 URL로 분기하므로 쿼리스트링에 포함하지 않는다
        ];

        // ✅ 사용자가 선택한 필드를 그대로 사용
        $params[$searchField] = $keyword;

        $url = $baseUrl . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $body = $this->httpGet($url);

        // JSON 기준으로 파싱 (type이 xml이면 여기서 분기 처리 가능)
        $json = json_decode($body, true);
        if ($json === null) {
            throw new \RuntimeException("응답 파싱 실패: {$body}");
        }
        return $json;
    }

    /** 내부 HTTP GET 호출(cURL) */
    private function httpGet(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        $res = curl_exec($ch);
        if ($res === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("cURL error: {$err}");
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException("HTTP {$code} from API");
        }
        return $res;
    }

    /** dataCd 값을 P/D/R로 정규화 */
    private function normalizeDataCd(string $dataCd): string
    {
        $dataCd = trim($dataCd);
        if ($dataCd === '') {
            throw new \InvalidArgumentException('dataCd(P/D/R)를 선택하세요.');
        }
        if (isset($this->dataCdMap[$dataCd])) {
            return $this->dataCdMap[$dataCd];
        }
        $upper = strtoupper($dataCd);
        if (isset($this->dataCdMap[$upper])) {
            return $this->dataCdMap[$upper];
        }
        throw new \InvalidArgumentException("알 수 없는 dataCd: {$dataCd}");
    }
}
