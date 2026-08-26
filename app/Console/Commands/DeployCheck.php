<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 배포 완료 점검 — 코드만 올라가고 마이그레이션/설정이 덜 끝난 채 넘어가면
 * 화면이 500·404 로 깨지지만 원인이 드러나지 않는다. 그 상태를 배포 단계에서 잡는다.
 *
 * 예) php artisan mangoshop:deploy-check
 * 반환코드: 정상 0, 문제 발견 1 (deploy.sh 가 이 코드로 중단 판단)
 */
class DeployCheck extends Command
{
    protected $signature = 'mangoshop:deploy-check';

    protected $description = '배포 완료 상태 점검(APP_KEY·DB연결·미적용 마이그레이션·핵심 테이블·스토리지 쓰기)';

    public function handle(): int
    {
        $fail = [];
        $warn = [];

        // 1) APP_KEY
        if (empty(config('app.key'))) {
            $fail[] = 'APP_KEY 미설정 — php artisan key:generate 필요';
        }

        // 2) DB 연결
        try {
            DB::connection()->getPdo();
            $this->line('  <info>✓</info> DB 연결 정상');
        } catch (\Throwable $e) {
            $fail[] = 'DB 연결 실패 — .env 의 DB_* 설정 확인 ('.$e->getMessage().')';
        }

        // 3) 미적용 마이그레이션
        try {
            $pending = 0;
            // migrate:status 출력 파싱 대신 repository 로 직접 비교
            $ran = app('migrator')->getRepository()->getRan();
            $files = array_keys(app('migrator')->getMigrationFiles(database_path('migrations')));
            $pending = count(array_diff($files, $ran));
            if ($pending > 0) {
                $fail[] = "미적용 마이그레이션 {$pending}건 — php artisan migrate --force 필요";
            } else {
                $this->line('  <info>✓</info> 마이그레이션 최신');
            }
        } catch (\Throwable $e) {
            $warn[] = '마이그레이션 상태 확인 불가 ('.$e->getMessage().')';
        }

        // 4) 핵심 테이블 존재
        foreach (['users', 'products', 'orders', 'categories'] as $t) {
            if (! Schema::hasTable($t)) {
                $fail[] = "필수 테이블 없음: {$t}";
            }
        }

        // 5) 스토리지 쓰기 권한
        $probe = storage_path('framework/.deploy-check');
        if (@file_put_contents($probe, 'ok') === false) {
            $fail[] = 'storage 쓰기 불가 — 권한(www-data) 확인';
        } else {
            @unlink($probe);
            $this->line('  <info>✓</info> 스토리지 쓰기 정상');
        }

        // 6) 설정 캐시 여부(경고만)
        if (! file_exists(base_path('bootstrap/cache/config.php'))) {
            $warn[] = '설정 캐시 없음 — php artisan optimize 권장(성능)';
        }

        foreach ($warn as $w) {
            $this->warn('  ⚠ '.$w);
        }

        if ($fail) {
            $this->newLine();
            foreach ($fail as $f) {
                $this->error('  ✗ '.$f);
            }

            return self::FAILURE;
        }

        $this->info('배포 점검 통과 ✓');

        return self::SUCCESS;
    }
}
