<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 도매 회원가입 시 사업자등록증 제출 및 관리자 승인 절차를 위한 컬럼을 추가한다.
     * 이번 마이그레이션은 users 표의 구조 변경만 담당하며, 기존 회원 자료는 전혀 건드리지 않는다.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 업로드된 사업자등록증 파일의 저장 경로(스토리지 상대 경로).
            // 반려 후 재업로드 시 경로가 교체되므로 단일 컬럼으로 충분하다.
            $table->string('business_license_path', 255)
                ->nullable()
                ->after('email')
                ->comment('사업자등록증 파일 경로');

            // 심사 상태: pending(대기) / approved(승인) / rejected(반려).
            // enum 대신 문자열을 쓰는 이유: 추후 상태값이 늘어날 때 컬럼 타입 변경 없이 확장 가능하고,
            // DB 종류에 따라 enum 변경이 까다로워 롤백이 어려워지기 때문이다.
            // 기본값을 두지 않고 nullable 로 선언하는 이유: 이미 가입된 회원들은 제출 대상이 아니므로
            // NULL(해당 없음) 상태로 남겨 두어야 관리자 심사 목록에 섞이지 않는다.
            $table->string('business_license_status', 20)
                ->nullable()
                ->after('business_license_path')
                ->comment('사업자등록증 심사 상태: pending/approved/rejected, NULL은 미제출');

            // 반려 시 관리자가 입력한 사유. 마이페이지에서 회원에게 그대로 노출된다.
            // 사유가 길어질 수 있어 text 로 둔다.
            $table->text('business_license_reject_reason')
                ->nullable()
                ->after('business_license_status')
                ->comment('반려 사유');

            // 제출(또는 재제출) 일시. 관리자 심사 대기열을 오래된 순으로 정렬하는 데 사용한다.
            $table->timestamp('business_license_submitted_at')
                ->nullable()
                ->after('business_license_reject_reason')
                ->comment('사업자등록증 제출 일시');

            // 관리자 화면에서 '대기' 건만 자주 조회하므로 상태 컬럼에 인덱스를 둔다.
            $table->index('business_license_status', 'users_business_license_status_index');
        });
    }

    /**
     * 추가한 컬럼과 인덱스를 되돌린다.
     * 컬럼을 지우기 전에 인덱스를 먼저 제거해야 일부 DB에서 오류가 나지 않는다.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_business_license_status_index');

            $table->dropColumn([
                'business_license_path',
                'business_license_status',
                'business_license_reject_reason',
                'business_license_submitted_at',
            ]);
        });
    }
};
