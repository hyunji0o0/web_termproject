<?php
return [
  'serviceKey' => 'xgC9NeFgwUGQYR0z5+invo1kwi6WBUkaiIc/76gaZjHD9xXEyR1yhV1FrgSAqzwu1t2g8PoQ/CXGzoELuuaubA==',
  'endpoints' => [
    'P' => [
      'url' => 'http://api.data.go.kr/openapi/tn_pubr_public_nutri_process_info_api',
      'default_type' => 'json',
    ],
    'D' => [
      'url' => 'http://api.data.go.kr/openapi/tn_pubr_public_nutri_food_info_api',
      'default_type' => 'json',
    ],
    'R' => [
      'url' => 'http://api.data.go.kr/openapi/tn_pubr_public_nutri_material_info_api',
      'default_type' => 'json',
      //R일 때만 foodNm → foodLv4Nm 로 치환
      'param_overrides' => [
        'foodNm' => 'foodLv4Nm',
      ],
    ],
  ],
  'param_names' => [
    'pageNo'    => 'pageNo',
    'numOfRows' => 'numOfRows',
    'type'      => 'type',
    'foodNm'    => 'foodNm', // 기본 키는 그대로 유지
  ],
  'default_type' => 'json',
];