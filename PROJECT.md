# 망고샵(MANGOSHOP) — 멀티벤더 수입과일 B2B 오픈마켓

여러 **수입사(입점 판매자)** 가 입점하는 멀티벤더(오픈마켓형) B2B+B2C 쇼핑몰.
**Laravel 11 + MariaDB(XAMPP)** 기반. [medisell] 골격을 복사해 멀티벤더로 확장 중.
UI/UX 레퍼런스: **exfresh.co.kr** (청과 B2B몰 레이아웃/구성/톤 재현, 브랜드·사진은 자체 콘텐츠).

## 접속 / 실행
- XAMPP Apache: **http://localhost/mangoshop/**  (물리 폴더는 `mangoshop`, junction `mangoshop`으로 접속)
  - 물리 폴더 최종 rename: 편집기/서버 종료 후 `mangoshop` → `mangoshop` 으로 변경하면 junction 불필요
    (rename 시 `.htaccess`·`.env`는 이미 mangoshop 기준이라 그대로 동작)
- 개발 서버: `php artisan serve` → http://127.0.0.1:8000
- 관리자: `/admin` · 판매자 콘솔(예정): `/seller`
- DB: `mangoshop` (utf8mb4), `root` / 비밀번호 없음
- 초기화: `php artisan migrate:fresh --seed`
- 참고: PHP 8.5에서 `PDO::MYSQL_ATTR_SSL_CA` deprecation 경고가 출력되나 무해.
  CLI는 `php -d error_reporting="E_ALL & ~E_DEPRECATED" artisan ...` 로 억제 가능.

## 체험 계정 (비밀번호)
| 구분 | 이메일 | 비밀번호 |
|---|---|---|
| 플랫폼 관리자 | admin@mangoshop.co.kr | mango!2026 |
| 도매회원(승인·여신 500만) | buyer@test.com | test1234 |
| 소매회원 | user@test.com | test1234 |
| 도매회원(승인대기) | pending@test.com | test1234 |
| 수입사 계정 | {slug}@seller.com (tropical-import / viet-fresh / ph-gold / zest-farm) | test1234 |

## 핵심 컨셉 — 멀티벤더 + 등급별 가격
- **수입사(Seller)** 가 상품을 등록, 주문/정산/배송비가 수입사 단위로 분리.
- 회원 구분: **도매(wholesale)** / **소매(retail)** — `users.member_type`.
  - 도매: 사업자 인증(국세청 API) + 관리자 승인(`biz_status=approved`) → **도매가**·**여신** 이용.
  - 소매: 승인 없이 **소매가(정가)** 로 구매.
  - 비로그인/미승인 도매: **가격 숨김** (`Product::priceVisibleFor()`).
- 가격 우선순위 `Product::priceFor($user)`:
  1. 회원별 개별 계약가 (레거시 `hospital_prices` 재활용)
  2. 도매 승인회원 → `products.wholesale_price`
  3. 소매/정가 → `products.price`
- **수량구간 할인** `Product::unitPriceFor($user,$qty)` — `products.price_tiers` `[{min_qty,price}]`.
- **MOQ** `products.moq` — 주문 시 최소수량 검증(예정).

## 데이터 모델 (멀티벤더 확장)
`database/migrations/2026_07_16_000001_create_multivendor_tables.php`
- **sellers** — 입점 수입사(대표 로그인계정 user_id, 상태, 수수료율, 배송비/무료기준, 콜드체인, 평점)
- **products** 확장 — seller_id, origin(원산지)/variety(품종)/grade(등급)/box_spec/weight_kg,
  inbound_date/expiry_date/storage_method/lot_no, wholesale_price/moq/price_tiers,
  sale_status(on_sale/soldout/closed/inbound)/expected_inbound_date/sales_count
- **orders** 확장 — order_group_no(결제 묶음), seller_id(주문=수입사 단위), desired_delivery_date, is_credit
- **order_items** 확장 — seller_id
- **user_addresses** — 다중 배송지
- **restock_alerts** — 재입고 알림
- **quotes / quote_items** — 견적(RFQ): 요청→회신→수락→주문
- **credit_accounts / credit_transactions** — 도매 여신(한도/사용/상환, net30·monthly)
- **seller_settlements** — 판매자 정산(판매액/수수료/정산액)

모델: `App\Models\{Seller, UserAddress, RestockAlert, Quote, QuoteItem, CreditAccount,
CreditTransaction, SellerSettlement}` + 확장된 `Product/Order/OrderItem/User`.

## 진행 상태 (Phase 1)
- [x] **기반**: medisell 스캐폴드 복사 · .env/DB/마이그레이션 · 부팅(153 라우트) 검증
- [x] **멀티벤더 도메인**: 스키마 + 모델 + 관계 + 등급/수량구간 가격 로직
- [x] **데모 시드**: 수입사 4 · 상품 15 · 카테고리(과일 대분류+망고 품종) · 계정 · 여신 · 배너
- [x] 홈/카탈로그/카테고리/상품상세/로그인/가입 렌더 200 검증
- [ ] **UI/UX**: exfresh 스타일 레이아웃·풀와이드 히어로·망고 디자인 시스템으로 재구성
- [ ] **회원/사업자 인증**: 도매/소매 가입 폼, 국세청 사업자 진위확인, 등록증 업로드, 승인 플로우
- [ ] **카탈로그**: 원산지/품종/수입사/등급/규격/가격 필터·정렬, 수입사 스토어 페이지, 재입고알림, 상품비교
- [ ] **장바구니/주문/결제**: 수입사별 묶음, 그룹 결제, MOQ 검증, 희망배송일, 여신 결제수단
- [ ] **B2B**: 견적(RFQ) 요청→회신→주문, 엑셀 대량주문/업로드, 여신 후불/월결제
- [ ] **판매자 콘솔**(`/seller`): 상품·주문·정산·문의 관리
- [ ] **운영자 어드민**: 수입사 입점심사, 상품/주문 통합관리, 정산

## 아키텍처 메모
- **주문 분할 전략**: 한 번의 체크아웃 → 수입사별 `orders` 레코드 N개 생성, `order_group_no` 로 묶음.
  기존 medisell 주문/상태/배송/취소/결제 로직을 수입사 단위로 그대로 재사용.
- **레거시 정리(2026-07-18 완료)**: 라이브 앱 소스 전반의 브랜드/도메인 문구 정리 완료.
  - `메디셀/MEDISELL→망고샵/MANGOSHOP`, `의료소모품/의료기기→수입 과일/농수산물`,
    회원 구분 `병원→도매·사업자`, stale 데모계정(clinic@/admin@medisell)→실계정, `logo.svg`→망고 로고.
  - `hospital_prices` 테이블/`HospitalPrice` 모델명은 "회원별 개별 계약가"로 의미 재정의해 **유지**(주석만 정리).
  - 휴면 레거시(미등록·미사용)로 **보존**: `ColsImportSeeder`, `database/data/sames_*·mulpum_*.json`,
    CoupangController/CoupangSearchService(운영자 가격비교 도구). 추후 제거 검토.
