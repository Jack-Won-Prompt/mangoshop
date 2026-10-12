{{-- 화면 스크립트 오류 자동 수집(운영 오류 모니터링). 실패해도 페이지엔 영향 없음. --}}
<script>
(function () {
    var ENDPOINT = @json(url('/client-error'));
    var MAX = 8, sent = 0, seen = {};

    function beacon(p) {
        try {
            if (sent >= MAX) return;
            if (!p.message || p.message === 'Script error.' || p.message === 'Script error') return; // 크로스오리진 노이즈 제외
            var key = (p.message || '') + '@' + (p.file || '') + ':' + (p.line || '');
            if (seen[key]) return; seen[key] = 1; sent++;
            p.url = location.href;
            var body = JSON.stringify(p);
            if (window.fetch) {
                fetch(ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true, credentials: 'same-origin' }).catch(function () {});
            } else if (navigator.sendBeacon) {
                navigator.sendBeacon(ENDPOINT, new Blob([body], { type: 'application/json' }));
            }
        } catch (e) {}
    }

    window.addEventListener('error', function (e) {
        if (!e) return;
        beacon({
            name: (e.error && e.error.name) || 'Error',
            message: (e.error && e.error.message) || e.message || '',
            file: e.filename || '', line: e.lineno || 0,
            stack: (e.error && e.error.stack) || ''
        });
    });
    window.addEventListener('unhandledrejection', function (e) {
        var r = e && e.reason;
        beacon({
            name: (r && r.name) || 'UnhandledRejection',
            message: (r && (r.message || r.toString())) || 'unhandledrejection',
            file: '', line: 0, stack: (r && r.stack) || ''
        });
    });
})();
</script>
