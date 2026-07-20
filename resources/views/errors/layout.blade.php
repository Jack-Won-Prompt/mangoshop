<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('code') · 망고샵</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%A5%AD%3C/text%3E%3C/svg%3E">
    <style>
        :root { --mg-primary:#ff6b00; --mg-primary-d:#e85d00; --mg-ink:#222; --mg-sub:#666; --mg-muted:#9aa0ab; --mg-line:#ececef; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:"Pretendard","Malgun Gothic","Apple SD Gothic Neo",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            color:var(--mg-ink); background:#fff; min-height:100vh; display:flex; align-items:center; justify-content:center;
            -webkit-font-smoothing:antialiased; letter-spacing:-.01em; padding:24px; position:relative; overflow:hidden; }
        /* 은은한 컬러 블롭 */
        body::before, body::after { content:""; position:fixed; border-radius:50%; filter:blur(80px); opacity:.45; z-index:0; pointer-events:none; }
        body::before { width:480px; height:480px; background:radial-gradient(circle,#ffe0b8,transparent 70%); top:-160px; right:-100px; }
        body::after { width:440px; height:440px; background:radial-gradient(circle,#d8f3d8,transparent 70%); bottom:-180px; left:-80px; }
        .wrap { position:relative; z-index:1; text-align:center; max-width:520px; }
        .emoji { font-size:84px; line-height:1; filter:drop-shadow(0 10px 20px rgba(0,0,0,.12)); animation:float 4s ease-in-out infinite; }
        @keyframes float { 0%,100%{transform:translateY(0) rotate(-4deg);} 50%{transform:translateY(-14px) rotate(4deg);} }
        .code { font-size:96px; font-weight:900; letter-spacing:-4px; line-height:1; margin:14px 0 4px;
            background:linear-gradient(120deg,var(--mg-primary),#ffab00); -webkit-background-clip:text; background-clip:text; color:transparent; }
        h1 { font-size:26px; font-weight:900; letter-spacing:-1px; margin-bottom:12px; }
        p { font-size:15.5px; color:var(--mg-sub); line-height:1.6; margin-bottom:28px; white-space:pre-line; }
        .btns { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }
        .btn { display:inline-flex; align-items:center; gap:7px; font-weight:800; font-size:15px; padding:13px 28px; border-radius:30px; text-decoration:none; white-space:nowrap; transition:transform .16s, box-shadow .16s; }
        .btn.pri { background:var(--mg-primary); color:#fff; box-shadow:0 8px 22px rgba(255,107,0,.3); }
        .btn.pri:hover { background:var(--mg-primary-d); transform:translateY(-2px); box-shadow:0 12px 28px rgba(255,107,0,.4); }
        .btn.gho { background:#fff; color:var(--mg-sub); border:1px solid var(--mg-line); }
        .btn.gho:hover { border-color:var(--mg-primary); color:var(--mg-primary); }
        .brand { margin-top:40px; display:inline-flex; align-items:center; gap:8px; color:var(--mg-muted); font-size:13px; font-weight:600; }
        .brand b { background:linear-gradient(120deg,var(--mg-primary),#ffab00); -webkit-background-clip:text; background-clip:text; color:transparent; font-weight:900; }
        @media (max-width:520px){ .code{font-size:72px;} .emoji{font-size:64px;} h1{font-size:21px;} }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="emoji">@yield('emoji', '🥭')</div>
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="btns">
            <a href="{{ url('/') }}" class="btn pri">홈으로 가기 →</a>
            <a href="javascript:history.back()" class="btn gho">이전 페이지</a>
        </div>
        <div class="brand"><span>🥭</span> <b>망고샵</b> · 수입 과일 오픈마켓</div>
    </div>
</body>
</html>
