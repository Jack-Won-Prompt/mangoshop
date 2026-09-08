<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 1회성 타임존 보정 — 앱 타임존을 UTC→Asia/Seoul 로 바꾼 뒤,
 * 과거 UTC 로 저장된 datetime/timestamp 값에 +9시간을 더해 실제 한국시간으로 맞춘다.
 *
 * ⚠ 반드시 1회만 실행 (두 번 실행 시 18시간 밀림). 재실행 방지 플래그(settings)로 보호.
 * 권장 순서: 운영 배포(git pull + config:cache) 후 실행.
 *
 * 실행:   php artisan mangoshop:fix-timezone --force
 * 되돌리기: php artisan mangoshop:fix-timezone --force --hours=-9 --again
 */
class FixTimezoneShift extends Command
{
    protected $signature = 'mangoshop:fix-timezone {--force : 확인 없이 실행} {--hours=9 : 이동 시간(음수 가능)} {--again : 재실행 방지 무시}';

    protected $description = '과거 데이터 시간을 +9시간(KST) 보정 (1회성)';

    /** 시스템/큐/세션/인증 테이블은 제외 */
    private array $exclude = [
        'migrations', 'jobs', 'job_batches', 'failed_jobs',
        'cache', 'cache_locks', 'sessions',
        'password_reset_tokens', 'personal_access_tokens',
    ];

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $flagKey = 'tz_shift_done';

        // 재실행 방지
        $done = DB::table('settings')->where('key', $flagKey)->exists();
        if ($done && ! $this->option('again')) {
            $this->error('이미 타임존 보정이 적용되었습니다. 재실행하려면 --again 을 붙이세요(주의: 중복 이동).');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("업무 테이블의 모든 datetime/timestamp 값을 {$hours}시간 이동합니다. 계속할까요?")) {
            return self::FAILURE;
        }

        $db = DB::getDatabaseName();
        $cols = DB::table('information_schema.columns')
            ->where('table_schema', $db)
            ->whereIn('data_type', ['datetime', 'timestamp'])
            ->orderBy('table_name')
            ->get(['table_name', 'column_name']);

        $total = 0;
        foreach ($cols as $c) {
            $t = $c->table_name;
            $col = $c->column_name;
            if (in_array($t, $this->exclude, true)) {
                continue;
            }
            $n = DB::table($t)->whereNotNull($col)
                ->update([$col => DB::raw("DATE_ADD(`{$col}`, INTERVAL {$hours} HOUR)")]);
            if ($n > 0) {
                $this->line(sprintf('  %-22s %s  (%d)', $t, $col, $n));
                $total += $n;
            }
        }

        // 플래그 기록(재실행 방지). settings 스키마(key/value)에 맞춰 저장.
        if (! $this->option('again')) {
            DB::table('settings')->updateOrInsert(
                ['key' => $flagKey],
                ['value' => now()->toDateTimeString(), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $this->info("완료: 총 {$total}개 셀을 {$hours}시간 이동했습니다.");

        return self::SUCCESS;
    }
}
