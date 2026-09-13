#!/usr/bin/env bash
#
# Mangoshop 운영 상태 점검 (읽기 전용)
# ---------------------------------------------------------------------------
# 배포가 실패한 뒤 원인을 보려고 돌리는 스크립트.
# deploy.sh 와 달리 아무것도 바꾸지 않는다:
#   - git fetch/pull/checkout 안 함 (원격은 ls-remote 로만 조회)
#   - artisan down/up, migrate, optimize, composer 안 함
#
# 사용법:   bash health.sh
# 종료코드: 모두 OK 면 0, 하나라도 실패하면 1
#
# 환경변수:
#   PHP=php                               실행할 PHP 바이너리
#   BRANCH=main                           비교할 원격 브랜치
#   SITE_URL=https://www.mangoshop.co.kr/ 응답 확인할 주소
#   DISK_LIMIT=90                         디스크 사용률(%) 이 이 이상이면 실패
#
# ※ mangoshop:deploy-check 는 storage 쓰기 확인을 위해 임시 파일
#   storage/framework/.deploy-check 를 만들었다 바로 지운다(그 외 변경 없음).
# ---------------------------------------------------------------------------
set -u

cd "$(dirname "$0")" || exit 1

PHP="${PHP:-php}"
BRANCH="${BRANCH:-main}"
SITE_URL="${SITE_URL:-https://www.mangoshop.co.kr/}"
DISK_LIMIT="${DISK_LIMIT:-90}"

if [ -t 1 ]; then
    G=$'\033[1;32m'; R=$'\033[1;31m'; Y=$'\033[1;33m'; N=$'\033[0m'
else
    G=''; R=''; Y=''; N=''
fi

FAILS=0
ok()   { printf '  %sOK  %s  %s\n' "$G" "$N" "$*"; }
bad()  { printf '  %s실패%s  %s\n' "$R" "$N" "$*"; FAILS=$((FAILS + 1)); }
note() { printf '        %s\n' "$*"; }   # 실패 사유 보조 줄

# 네트워크 명령이 멈춰 버리지 않도록 (timeout 이 없으면 그냥 실행)
if command -v timeout >/dev/null 2>&1; then T="timeout 20"; else T=""; fi

# PHP 경고(Deprecated 등)가 판정/출력을 흐리지 않게 걸러낸다
php_clean() { grep -vE '^(PHP )?(Deprecated|Warning|Notice):|^$'; }

echo "Mangoshop 상태 점검  $(date '+%Y-%m-%d %H:%M:%S')  $(pwd)"
echo

if [ ! -f artisan ]; then
    bad "artisan 없음 — 프로젝트 루트가 아닙니다"
    exit 1
fi

# --- 5. 점검 모드 (가장 먼저, 눈에 띄게) -----------------------------------------
if [ -e storage/framework/down ]; then
    printf '%s' "$R"
    echo "  ############################################################"
    echo "  ##  점검 모드 켜져 있음 — 손님에게 503 이 보이는 중입니다  ##"
    echo "  ##  (storage/framework/down 존재)                         ##"
    echo "  ############################################################"
    printf '%s\n' "$N"
fi

# --- 1. 커밋 / origin 과의 차이 ---------------------------------------------------
if HEAD_LINE="$(git log -1 --oneline 2>/dev/null)"; then
    LOCAL_REF="$(git rev-parse HEAD)"
    CUR_BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null)"
    DIRTY="$(git --no-optional-locks status --porcelain --untracked-files=no 2>/dev/null | wc -l | tr -d ' ')"
    EXTRA=""
    [ "$CUR_BRANCH" != "$BRANCH" ] && EXTRA="$EXTRA, 현재 브랜치 $CUR_BRANCH"
    [ "$DIRTY" != "0" ] && EXTRA="$EXTRA, 로컬 수정 파일 ${DIRTY}개"

    REMOTE_REF="$(GIT_TERMINAL_PROMPT=0 $T git ls-remote origin "refs/heads/$BRANCH" 2>/dev/null | cut -f1)"
    if [ -z "$REMOTE_REF" ]; then
        bad "커밋 $HEAD_LINE — origin/$BRANCH 조회 실패(네트워크/인증)$EXTRA"
    elif [ "$LOCAL_REF" = "$REMOTE_REF" ]; then
        if [ -z "$EXTRA" ]; then
            ok "커밋 $HEAD_LINE (origin/$BRANCH 과 같음)"
        else
            bad "커밋 $HEAD_LINE (origin/$BRANCH 과 같음$EXTRA)"
        fi
    elif git cat-file -e "$REMOTE_REF^{commit}" 2>/dev/null; then
        read -r AHEAD BEHIND < <(git rev-list --left-right --count "HEAD...$REMOTE_REF")
        bad "커밋 $HEAD_LINE — origin/$BRANCH 보다 ${BEHIND}개 뒤, ${AHEAD}개 앞$EXTRA"
    else
        bad "커밋 $HEAD_LINE — origin/$BRANCH(${REMOTE_REF:0:7}) 에 아직 안 받은 커밋 있음$EXTRA"
    fi
else
    bad "git 정보 없음 — git 저장소가 아닙니다"
fi

# --- 2. 앱 부팅 / 배포 점검 -------------------------------------------------------
if "$PHP" artisan list --raw 2>/dev/null | grep -q '^mangoshop:deploy-check'; then
    if OUT="$("$PHP" artisan mangoshop:deploy-check 2>&1)"; then
        ok "배포 점검(mangoshop:deploy-check) 통과"
    else
        bad "배포 점검(mangoshop:deploy-check) 실패"
        REASON="$(printf '%s\n' "$OUT" | php_clean | grep -E '✗|⚠')"
        # 점검 명령이 예외로 죽으면 ✗ 줄이 없다 — 그때는 첫 줄들을 그대로 보여 준다
        [ -z "$REASON" ] && REASON="$(printf '%s\n' "$OUT" | php_clean | head -2)"
        printf '%s\n' "$REASON" | sed 's/^ */        /' | cut -c1-200
    fi
elif OUT="$("$PHP" artisan about 2>&1)"; then
    ok "앱 부팅(artisan about) 정상"
else
    bad "앱 부팅(artisan about) 실패"
    printf '%s\n' "$OUT" | php_clean | head -3 | sed 's/^ */        /'
fi

# --- 3. DB 연결 / 마이그레이션 ----------------------------------------------------
DB_OUT="$("$PHP" -r '
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    try {
        $c = Illuminate\Support\Facades\DB::connection();
        $c->getPdo();
        echo "OK ", $c->getDriverName(), "/", $c->getDatabaseName();
    } catch (Throwable $e) {
        echo "ERR ", strtok($e->getMessage(), "\n");
    }
' 2>&1 | php_clean | tail -1)"
case "$DB_OUT" in
    OK*)  ok  "DB 연결 정상 (${DB_OUT#OK })"; DB_OK=1 ;;
    *)    bad "DB 연결 실패"; note "${DB_OUT#ERR }"; DB_OK=0 ;;
esac

if [ "$DB_OK" = "1" ]; then
    if MIG_OUT="$("$PHP" artisan migrate:status --no-ansi 2>&1)"; then
        PENDING="$(printf '%s\n' "$MIG_OUT" | grep -c 'Pending')"
        if [ "$PENDING" = "0" ]; then
            ok "마이그레이션 최신"
        else
            bad "마이그레이션 미적용 ${PENDING}건"
            printf '%s\n' "$MIG_OUT" | grep 'Pending' | head -5 | awk '{print "        " $1}'
        fi
    else
        bad "마이그레이션 상태 확인 불가"
        printf '%s\n' "$MIG_OUT" | php_clean | head -2 | sed 's/^ */        /'
    fi
else
    bad "마이그레이션 확인 못 함 (DB 연결 실패)"
fi

# --- 4. storage 쓰기 / 디스크 -----------------------------------------------------
# 파일을 만들어 보지 않고 권한만 본다(현재 사용자 기준: $(whoami))
NOT_W=""
for d in storage storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache; do
    if [ ! -d "$d" ]; then
        NOT_W="$NOT_W $d(없음)"
    elif [ ! -w "$d" ]; then
        NOT_W="$NOT_W $d"
    fi
done
if [ -z "$NOT_W" ]; then
    ok "storage 쓰기 가능 ($(whoami) 기준)"
else
    bad "storage 쓰기 불가:$NOT_W"
fi

DF_LINE="$(df -hP . 2>/dev/null | awk 'NR==2')"
if [ -n "$DF_LINE" ]; then
    USE="$(echo "$DF_LINE" | awk '{gsub("%","",$5); print $5}')"
    AVAIL="$(echo "$DF_LINE" | awk '{print $4}')"
    if [ "$USE" -lt "$DISK_LIMIT" ]; then
        ok "디스크 사용 ${USE}%, 여유 $AVAIL"
    else
        bad "디스크 사용 ${USE}% (기준 ${DISK_LIMIT}%), 여유 $AVAIL"
    fi
else
    bad "디스크 확인 실패 (df)"
fi

# --- 5. 점검 모드 (한 줄 결과) ----------------------------------------------------
if [ -e storage/framework/down ]; then
    bad "점검 모드 켜져 있음 — 해제: php artisan up"
else
    ok "점검 모드 꺼져 있음"
fi

# --- 6. 사이트 응답 ---------------------------------------------------------------
CODE="$(curl -s -o /dev/null -m 15 -w '%{http_code}' "$SITE_URL" 2>/dev/null)"
case "$CODE" in
    2??|3??) ok  "사이트 응답 $CODE ($SITE_URL)" ;;
    000|"")  bad "사이트 응답 없음 ($SITE_URL — 연결/DNS/SSL 실패)" ;;
    *)       bad "사이트 응답 $CODE ($SITE_URL)" ;;
esac

# --- 결과 -------------------------------------------------------------------------
echo
if [ "$FAILS" -eq 0 ]; then
    printf '%s모두 정상%s\n' "$G" "$N"
    exit 0
else
    printf '%s실패 %d건%s — 위 "실패" 줄을 확인하세요\n' "$R" "$FAILS" "$N"
    exit 1
fi
