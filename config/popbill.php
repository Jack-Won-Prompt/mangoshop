<?php

return [
    'LinkID'          => env('POPBILL_ID'),
    'SecretKey'       => env('POPBILL_SECRET_KEY'),
    'IsTest'          => env('POPBILL_IS_TEST', true),
    'IPRestrictOnOff' => env('POPBILL_IP_RESTRICT_ON_OFF', true),
    'UseStaticIP'     => env('POPBILL_USE_STATIC_IP', false),
    'UseLocalTimeYN'  => env('POPBILL_USE_LOCAL_TIME_YN', true),

    /*
    | 시뮬레이트 모드: true 면 실제 팝빌 API를 호출하지 않고 발행이력만 생성.
    | 실발행 준비가 되면 .env 에 POPBILL_TAXINVOICE_SIMULATE=false 로 설정.
    | ※ 기본값 true (실계정/실발행 사고 방지)
    */
    'simulate'        => env('POPBILL_TAXINVOICE_SIMULATE', true),

    // 계좌조회(EasyFinBank) — 무통장 입금 자동확인
    'bank' => [
        'simulate'    => env('POPBILL_BANK_SIMULATE', true),          // 기본 시뮬레이트(실계좌 조회 차단)
        'corp_num'    => env('POPBILL_TEST_CORP_NUM', ''),            // 팝빌 회원 사업자번호
        'user_id'     => env('POPBILL_TEST_USER_ID', ''),
        'bank_code'   => env('POPBILL_BANK_CODE', '0020'),            // 은행코드(예: 우리 0020, 국민 0004, 신한 0088, 농협 0011, 기업 0003, 하나 0081)
        'account_num' => env('POPBILL_BANK_ACCOUNT', ''),            // 조회 계좌번호(하이픈 제외)
    ],

    // 문자(SMS/LMS) — 주문 알림(판매자·구매자). SendXMS 로 길이에 따라 SMS/LMS 자동.
    'sms' => [
        'simulate'    => env('POPBILL_SMS_SIMULATE', true),          // 기본 시뮬레이트(실발송 차단, 로그만)
        'corp_num'    => env('POPBILL_TEST_CORP_NUM', ''),           // 팝빌 회원 사업자번호(숫자만)
        'user_id'     => env('POPBILL_TEST_USER_ID', ''),
        'sender'      => env('POPBILL_SMS_SENDER', env('COMPANY_TEL', '')), // 팝빌에 사전등록된 발신번호
        'sender_name' => env('POPBILL_SMS_SENDER_NAME', env('COMPANY_CORP_NAME', '망고샵')),
    ],

    // 공급자(발행자 = 망고샵 / 현재는 .env 값). 팝빌에 등록된 사업자여야 실발행 가능.
    'supplier' => [
        'corp_num'  => env('POPBILL_TEST_CORP_NUM', ''),                 // 사업자등록번호(숫자만)
        'user_id'   => env('POPBILL_TEST_USER_ID', ''),                  // 팝빌 회원 아이디
        'corp_name' => env('COMPANY_CORP_NAME', '망고샵'),
        'ceo_name'  => env('COMPANY_CEO_NAME', '최연아'),
        'addr'      => env('COMPANY_ADDR', '서울특별시 강서구 마곡중앙로 161-8, C동 5층 502호'),
        'biz_type'  => env('COMPANY_BIZ_CLASS', '도매 및 소매업'),        // 업태
        'biz_class' => env('COMPANY_BIZ_TYPE', '전자상거래 소매업, 농수산물 도소매'),  // 종목
        'tel'       => env('COMPANY_TEL', ''),
        'email'     => env('COMPANY_EMAIL', ''),
    ],
];
