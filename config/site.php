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
    'shipping_fee'      => 3000,   // 배송 건당 기본 배송비
    'shipping_fee_jeju' => 5000,   // 제주(우편번호 63xxx) 배송 건당

    // 가입 적립금 / 구매 적립률(%)
    'signup_point'   => 3000,
    'point_rate'     => 1,

    'popular_keywords' => ['애플망고', '옐로우망고', '아보카도', '두리안', '망고스틴', '용과', '리치'],

    // 고객 문의 알림 수신 이메일(최대 3) — 관리자 사이트설정에서 입력
    'inquiry_emails' => [],

    // 메인화면 '새로 들어온 과일' 섹션 문구(관리자 사이트설정에서 편집)
    'home_new_title' => '새로 들어온 과일',
    'home_new_sub'   => '이번 주 새롭게 입고된 신상품',
    'home_show_recipe' => true,   // 메인페이지 '최신 등록 레시피' 영역 노출

    // 메인 배너 아래 혜택 안내 스트립(4칸) — 관리자 사이트설정에서 아이콘·제목·설명 편집
    'home_strip' => [
        ['icon' => 'truck',    'title' => '주문 후 순차 발송',   'desc' => '입고 일정에 맞춰 출고'],
        ['icon' => 'building', 'title' => '수입사별 상품 비교', 'desc' => '여러 수입사 과일을 한곳에서'],
        ['icon' => 'coin',     'title' => '수량구간 대량할인', 'desc' => '도매회원 구간별 도매가'],
        ['icon' => 'doc',      'title' => '세금계산서',         'desc' => '사업자 발행 지원'],
    ],

    // 메인화면 피처 밴드(실사진 2블록) — 관리자 사이트설정에서 텍스트·이미지 편집
    //  - title 은 <br><b> 만 허용(관리자 입력 시 서버에서 안전 태그로 제한)
    //  - image/link 는 사이트 기준 상대경로(asset()/url()) 또는 http(s) 절대주소
    'home_features' => [
        [
            'kicker' => 'POINTS & CASHBACK',
            'title'  => '사고, 후기 쓰면<br><b>적립금</b>이 쌓입니다',
            'desc'   => '구매부터 후기까지, 망고샵을 이용할수록 혜택이 커집니다. 앱에서 적립·캐시백 리워드를 바로 확인하세요.',
            'chips'  => ['신규가입 3,000P', '구매 3% 적립', '상품후기 3% 적립'],
            'button' => '후기 쓰고 적립받기',
            'link'   => 'community/reviews',
            'image'  => 'images/main/cashback-points.jpg',
        ],
        [
            'kicker' => 'IMPORT & DISTRIBUTION',
            'title'  => '검증된 수입사와<br><b>직거래</b>합니다',
            'desc'   => '글로벌 파트너십으로 들여온 프리미엄 열대과일을 산지 그대로. 수입사 입점 파트너를 상시 모집합니다.',
            'chips'  => ['품질 · 신뢰 · 글로벌 파트너십', '콜드체인 원스톱 지원'],
            'button' => '입점 문의하기',
            'link'   => 'community/inquiry',
            'image'  => 'images/main/import-distribution.jpg',
        ],
    ],

    // 메인페이지 상품 홍보 팝업(관리자 사이트설정 > 메인 팝업에서 편집)
    //  - image/link 는 사이트 기준 상대경로(asset()/url() 로 변환) 또는 http(s) 절대주소
    //  - start/end(Y-m-d) 비우면 기간 제한 없음
    'popup' => [
        'enabled'    => true,    // 기본 노출 — 관리자 사이트설정 > 메인 팝업에서 끄기
        'title'      => '추석 명절 선물세트',
        'sub'        => '프리미엄 애플망고 선물세트 · 8과/9과 선택',
        'button'     => '선물세트 보러가기',
        'image'      => 'images/giftset/orchard/applemango-main.jpg',
        'link'       => 'product/premium-apple-mango-giftset',
        'new_window' => false,
        'start'      => null,
        'end'        => null,
    ],

    // 메인 팝업 여러 개 동시 노출(설정 시 'popup' 대신 이 목록을 사용)
    'popups' => [
        [
            'enabled'    => true,
            'title'      => '추석 명절 선물세트',
            'button'     => '선물세트 보러가기',
            'image'      => 'images/giftset/orchard/applemango-main.jpg',
            'link'       => 'product/premium-apple-mango-giftset',
            'new_window' => false,
            'start'      => null, 'end' => null,
        ],
        [
            'enabled'    => true,
            'title'      => '프랜차이즈·매장·카페 과일 문의',
            'button'     => '',  // 배너 이미지에 CTA 포함 → 중복 버튼 없음(이미지 클릭 시 문의하기)
            'image'      => 'images/popup/fruit-inquiry.jpg',
            'link'       => 'community/inquiry',
            'new_window' => false,
            'start'      => null, 'end' => null,
        ],
    ],

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
