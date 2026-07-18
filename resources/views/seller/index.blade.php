@extends('layouts.app')
@section('title', '입점 수입사 - 망고샵')

@section('content')
<div class="mg-section">
    <div class="mg-wrap">
        <div class="mg-sec-head">
            <div class="ki">SELLERS</div>
            <h3>입점 수입사</h3>
            <p>망고샵에 입점한 검증된 수입사의 과일을 만나보세요</p>
        </div>
        <div class="mg-grid g5">
            @foreach($sellers as $s)
                <a href="{{ route('seller.show', $s->slug) }}" class="mg-card" style="text-align:center;padding:26px 14px;text-decoration:none">
                    <div style="font-size:44px">🏬</div>
                    <b style="display:block;margin-top:10px;color:var(--mg-ink);font-size:15px">{{ $s->name }}</b>
                    <span style="color:var(--mg-accent-d);font-size:12.5px;font-weight:700">{{ $s->origin_focus }}</span>
                    <span style="display:block;color:var(--mg-muted);font-size:12px;margin-top:6px">상품 {{ $s->products_count }}종 · ⭐ {{ $s->avgRating() ?: '-' }}</span>
                </a>
            @endforeach
        </div>
        @if($sellers->isEmpty())
            <p style="text-align:center;color:var(--mg-muted);padding:40px 0">입점 수입사가 아직 없습니다.</p>
        @endif
    </div>
</div>
@endsection
