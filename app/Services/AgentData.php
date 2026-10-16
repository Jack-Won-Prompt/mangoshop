<?php

namespace App\Services;

use App\Models\SrThread;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * 코드가 아니라 '자료' 를 바꿔야 끝나는 요청을 처리한다.
 *
 * 카테고리 그림을 걸어 달라, 공지 문구를 고쳐 달라 같은 요청은 코드를 한 줄도
 * 바꾸지 않는다. 그런데 코드 건이 아니라는 이유로 '안내' 만 하고 접으면 요청은
 * 영영 처리되지 않는다. 실제로 그랬다 — 카테고리 그림을 걸어 달라는 요청이
 * '코드 건 아님' 으로 반영 완료 표시까지 달고 묻혔다.
 *
 * 사람 확인 없이 운영 자료를 바꾸는 일이라 막는 장치를 겹겹이 둔다.
 *  1. 손댈 수 있는 표를 목록으로 제한한다. 회원·주문·결제·정산은 어떤 경우에도 아니다.
 *  2. 돈·비밀번호·열쇠로 보이는 칸은 표가 허용돼도 막는다.
 *  3. 한 번에 한 줄만 바꾼다. 어느 줄인지 정확히 집히지 않으면 아무것도 하지 않는다.
 *  4. 바꾸기 전에 DB 를 통째로 받아 둔다.
 *  5. 바꾼 뒤 다시 읽어 확인한다. 값이 다르면 실패로 돌린다.
 */
class AgentData
{
    /** 손댈 수 있는 표 — 보여 주기용 자료만. 거래·회원 자료는 넣지 않는다 */
    private const ALLOW_TABLES = [
        'categories', 'brands', 'banners', 'notices', 'faqs', 'popups', 'recipe_categories',
    ];

    /** 표가 허용돼도 손대지 않는 칸 (부분 일치, 소문자) */
    private const DENY_COLUMNS = [
        'id', 'created_at', 'updated_at', 'deleted_at',
        'password', 'token', 'secret', 'key', 'hash', 'salt',
        'price', 'amount', 'fee', 'point', 'balance', 'rate', 'commission',
    ];

    /** 한 번에 처리할 수 있는 일의 수 */
    private const MAX_OPS = 5;

    /** 올린 그림을 줄여 둘 한 변 길이 */
    private const IMAGE_EDGE = 512;

    /** 그림을 받는 칸 이름 */
    private const IMAGE_COLUMNS = ['image', 'logo', 'cover', 'thumbnail', 'photo'];

    /**
     * 자료 바꾸기를 실제로 수행한다.
     *
     * @param  array  $ops  [['table'=>..,'match'=>[..],'set'=>[..]], ...]
     * @return array{applied:bool,changed:array,message:string}
     */
    public function __construct(private ?AgentFixer $fixer = null) {}

    public function apply(?SrThread $sr, array $ops): array
    {
        $ops = array_slice(array_values($ops), 0, self::MAX_OPS);
        if (! $ops) {
            return ['applied' => false, 'changed' => [], 'message' => '바꿀 자료가 지정되지 않았습니다.'];
        }

        // 먼저 전부 검사한다. 하나라도 걸리면 한 줄도 바꾸지 않는다.
        $plan = [];
        foreach ($ops as $i => $op) {
            $checked = $this->check($sr, (array) $op);
            if (! $checked['ok']) {
                return ['applied' => false, 'changed' => [],
                    'message' => ($i + 1).'번째 일을 할 수 없습니다: '.$checked['reason']];
            }
            $plan[] = $checked;
        }

        // 되돌릴 수 없는 일 앞에서는 항상 먼저 받아 둔다
        if (! ($this->fixer ?? new AgentFixer)->backupNow()) {
            return ['applied' => false, 'changed' => [],
                'message' => 'DB 를 받아 두지 못해 자료를 바꾸지 않았습니다.'];
        }

        $changed = [];
        foreach ($plan as $p) {
            try {
                DB::table($p['table'])->where('id', $p['id'])->update($p['set']);
            } catch (Throwable $e) {
                Log::error('Agent 자료 바꾸기 실패: '.$e->getMessage());

                return ['applied' => false, 'changed' => $changed,
                    'message' => '자료를 바꾸는 중에 막혔습니다: '.mb_substr($e->getMessage(), 0, 120)];
            }

            // 다시 읽어 확인한다 — 저장했다고 해서 들어간 것은 아니다
            $row = (array) DB::table($p['table'])->where('id', $p['id'])->first();
            foreach ($p['set'] as $col => $want) {
                if ((string) ($row[$col] ?? '') !== (string) $want) {
                    return ['applied' => false, 'changed' => $changed,
                        'message' => "{$p['table']} 의 {$col} 값이 저장 뒤에도 달라 실패로 봅니다."];
                }
            }
            $changed[] = $p['table'].' #'.$p['id'].' — '.implode(', ', array_keys($p['set']));
        }

        return ['applied' => true, 'changed' => $changed,
            'message' => '자료를 바꿨습니다: '.implode(' / ', $changed)];
    }

    /** 일 하나가 규칙에 맞는지 본다. 맞으면 바꿀 줄의 id 까지 집어 돌려준다. */
    private function check(?SrThread $sr, array $op): array
    {
        $table = strtolower(trim((string) ($op['table'] ?? '')));
        $match = (array) ($op['match'] ?? []);
        $set = (array) ($op['set'] ?? []);

        if (! in_array($table, self::ALLOW_TABLES, true)) {
            return ['ok' => false, 'reason' => "'{$table}' 표는 손댈 수 있는 자리가 아닙니다."];
        }
        if (! $match || ! $set) {
            return ['ok' => false, 'reason' => '어느 줄을 어떻게 바꿀지가 비어 있습니다.'];
        }

        // 손대지 않는 칸은 표를 들여다보기 전에 먼저 거른다.
        // 표 구조를 읽어 본 뒤에 거르면, 그 칸이 없는 표에서는 "칸이 없다" 로만 끝나
        // 막는 규칙이 실제로 도는지 확인할 길이 없다.
        foreach (array_keys($set) as $col) {
            $lower = strtolower((string) $col);
            foreach (self::DENY_COLUMNS as $deny) {
                if ($lower === $deny || str_contains($lower, $deny)) {
                    return ['ok' => false, 'reason' => "{$col} 칸은 손대지 않는 자리입니다."];
                }
            }
        }

        $cols = Schema::getColumnListing($table);
        foreach (array_keys($match) as $col) {
            if (! in_array($col, $cols, true)) {
                return ['ok' => false, 'reason' => "{$table} 에 {$col} 칸이 없습니다."];
            }
        }

        $clean = [];
        foreach ($set as $col => $val) {
            if (! in_array($col, $cols, true)) {
                return ['ok' => false, 'reason' => "{$table} 에 {$col} 칸이 없습니다."];
            }
            $resolved = $this->resolve($sr, $table, $col, $val);
            if ($resolved['ok'] === false) {
                return ['ok' => false, 'reason' => $resolved['reason']];
            }
            $clean[$col] = $resolved['value'];
        }

        // 정확히 한 줄만 집혀야 한다
        $q = DB::table($table);
        foreach ($match as $col => $val) {
            $q->where($col, $val);
        }
        $hit = $q->limit(2)->get();
        if ($hit->count() !== 1) {
            return ['ok' => false, 'reason' => $hit->count() === 0
                ? "{$table} 에서 그 줄을 찾지 못했습니다."
                : "{$table} 에서 여러 줄이 집혀 멈춥니다. 한 줄만 집히게 적어야 합니다."];
        }

        return ['ok' => true, 'table' => $table, 'id' => $hit[0]->id, 'set' => $clean];
    }

    /** 값을 실제로 저장할 모양으로 바꾼다. <attachment:1> 이면 첨부 그림을 꺼내 건다. */
    private function resolve(?SrThread $sr, string $table, string $col, $val): array
    {
        if (! is_string($val) || ! preg_match('/^<attachment:(\d+)>$/', trim($val), $m)) {
            return ['ok' => true, 'value' => $val];
        }
        if (! in_array(strtolower($col), self::IMAGE_COLUMNS, true)) {
            return ['ok' => false, 'reason' => "{$col} 칸은 그림을 받는 자리가 아닙니다."];
        }
        if (! $sr) {
            return ['ok' => false, 'reason' => '첨부를 꺼낼 대화가 없습니다.'];
        }

        $files = $this->images($sr);
        $idx = (int) $m[1] - 1;
        if (! isset($files[$idx])) {
            return ['ok' => false, 'reason' => '대화에서 '.($idx + 1).'번째 그림을 찾지 못했습니다.'];
        }

        $saved = $this->store($files[$idx], $table);

        return $saved
            ? ['ok' => true, 'value' => $saved]
            : ['ok' => false, 'reason' => '그림을 옮겨 두지 못했습니다.'];
    }

    /** 대화에 붙은 그림들을 올라온 차례대로 돌려준다 */
    private function images(SrThread $sr): array
    {
        $out = [];
        foreach ($sr->messages()->orderBy('id')->get() as $m) {
            foreach ((array) $m->attachments as $a) {
                if (! str_starts_with((string) ($a['mime'] ?? ''), 'image/')) {
                    continue;
                }
                $p = storage_path('app/private/'.ltrim((string) ($a['path'] ?? ''), '/'));
                if (is_file($p)) {
                    $out[] = $p;
                }
            }
        }

        return $out;
    }

    /** 그림을 공개 자리로 옮기고 화면이 쓸 주소를 돌려준다 */
    private function store(string $src, string $table): ?string
    {
        $info = @getimagesize($src);
        if (! $info) {
            return null;
        }

        $dir = storage_path('app/public/uploads/'.$table);
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true)) {
            return null;
        }
        $name = $table.'-'.date('YmdHis').'-'.substr(md5($src.microtime()), 0, 6).'.png';
        $dest = $dir.'/'.$name;

        try {
            $im = match ($info[2]) {
                IMAGETYPE_PNG => imagecreatefrompng($src),
                IMAGETYPE_JPEG => imagecreatefromjpeg($src),
                IMAGETYPE_GIF => imagecreatefromgif($src),
                IMAGETYPE_WEBP => imagecreatefromwebp($src),
                default => null,
            };
            if (! $im) {
                return null;
            }

            // 아이콘 자리에 들어갈 그림이라 크게 둘 이유가 없다. 긴 변을 맞춰 줄인다.
            $w = imagesx($im);
            $h = imagesy($im);
            $scale = min(1, self::IMAGE_EDGE / max($w, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));

            $out = imagecreatetruecolor($nw, $nh);
            imagealphablending($out, false);
            imagesavealpha($out, true);
            imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
            imagecopyresampled($out, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagepng($out, $dest, 7);
            imagedestroy($im);
            imagedestroy($out);
        } catch (Throwable $e) {
            Log::error('Agent 그림 옮기기 실패: '.$e->getMessage());

            return null;
        }

        @chmod($dest, 0664);

        // 호스트를 박아 두지 않는다. 주소나 http/https 가 바뀌어도 그대로 쓰이게 한다.
        return '/storage/uploads/'.$table.'/'.$name;
    }
}
