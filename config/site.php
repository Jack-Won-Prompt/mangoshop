<?php

/*
|--------------------------------------------------------------------------
| 사이트 전역 설정 (회사정보 / 고객센터 / 무통장 입금계좌)
| DB(settings.key='site')로 런타임 오버라이드 — AppServiceProvider 참고
|--------------------------------------------------------------------------
*/

return [
    'name'        => '망고샵',
    'name_en'     => 'MANGOSHOP',
    'tagline'     => '수입 과일 도매·소매 오픈마켓',
    // 사업자(법인) 정보 — 실제 사업자등록증 기준(토스페이먼츠 심사용). 운영법인: 주식회사 링크더랩.
    'company'     => '주식회사 링크더랩',
    'ceo'         => '최연아',
    'biz_no'      => '490-86-01851',
    'mailorder'   => '제2021-서울강서-2026호',      // 통신판매업 신고번호
    'address'     => '서울특별시 영등포구 경인로77길 49, 109동 2층 201-60호 (문래동4가, 리버뷰 신안인스빌)',

    'cs_tel'      => '02-1544-9086',
    'cs_hours'    => '평일 09:00 ~ 18:00 (점심 12:00~13:00) / 주말·공휴일 휴무',
    'email'       => 'admin@colscare.com',

    // 무통장 입금계좌
    'banks' => [
        ['bank' => '국민은행', 'account' => '834701-04-159739', 'holder' => '주식회사 링크더랩'],
    ],

    // 결제 PG (관리자 사이트설정에서 선택): toss | portone
    'payment_pg' => env('PAYMENT_PG', 'toss'),

    // 배송비 정책 (플랫폼 기본값 — 수입사별 정책이 우선)
    'free_ship_over' => 50000,
    'shipping_fee'      => 3000,   // 기본 배송비
    'shipping_fee_jeju' => 5000,   // 제주(우편번호 63xxx)
    'shipping_box_unit'  => 3,     // N박스 단위마다
    'shipping_box_extra' => 2000,  // 추가 배송비

    // 가입 적립금 / 구매 적립률(%)
    'signup_point'   => 3000,
    'point_rate'     => 1,

    'popular_keywords' => ['애플망고', '옐로우망고', '아보카도', '두리안', '망고스틴', '용과', '리치'],

    // 고객 문의 알림 수신 이메일(최대 3) — 관리자 사이트설정에서 입력
    'inquiry_emails' => [],

    // 메인화면 '새로 들어온 과일' 섹션 문구(관리자 사이트설정에서 편집)
    'home_new_title' => '새로 들어온 과일',
    'home_new_sub'   => '이번 주 새롭게 입고된 신상품',

    /*
    | 모바일 앱 버전 관리 (강제/선택 업데이트)
    | - latest_build : 최신 배포 빌드(versionCode). 이보다 낮으면 "업데이트 있음" 안내.
    | - min_build    : 최소 지원 빌드. 이보다 낮으면 강제 업데이트(사용 차단).
    | - store_url    : 스토어 이동 링크.
    | 관리자 사이트설정(DB)에서 platform별로 오버라이드 가능.
    */
    'app' => [
        'android' => [
            'latest_build'   => 1,
            'latest_version' => '1.0.0',
            'min_build'      => 1,
            'store_url'      => 'https://play.google.com/store/apps/details?id=com.mangoshop.mangoshop_app',
        ],
        'ios' => [
            'latest_build'   => 1,
            'latest_version' => '1.0.0',
            'min_build'      => 1,
            'store_url'      => 'https://apps.apple.com/app/id000000000',
        ],
        'update_message' => '더 나은 망고샵을 위해 새로운 버전이 출시되었습니다.\n최신 버전으로 업데이트해 주세요.',
    ],
];
