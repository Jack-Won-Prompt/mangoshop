{{-- 카테고리 아이콘 한 자리. 올린 이미지 > 슬러그 기본 아이콘 > icon 칸 > 기본 모양 순으로 고른다 --}}
@props(['category', 'size' => 19])
@php
    $__slugIcons = ['mango' => 'cat-mango', 'avocado' => 'cat-avocado', 'tropical' => 'cat-pineapple',
        'citrus' => 'cat-citrus', 'berry' => 'cat-berry', 'giftset' => 'cat-gift'];
    $__img  = $category->image ?? null;
    $__key  = $__slugIcons[$category->slug] ?? null;
    $__icon = trim((string) ($category->icon ?? ''));
    // 영문·숫자·하이픈이면 아이콘 이름, 그 밖(이모지)이면 글자 그대로
    $__isKey = $__icon !== '' && preg_match('/^[a-z0-9-]+$/i', $__icon);
@endphp
@if($__img)
    <img src="{{ $__img }}" alt="" width="{{ $size }}" height="{{ $size }}"
         style="width:{{ $size }}px;height:{{ $size }}px;object-fit:cover;border-radius:50%;display:inline-block;vertical-align:middle">
@elseif($__key)
    <x-icon :name="$__key" :size="$size"/>
@elseif($__isKey)
    <x-icon :name="$__icon" :size="$size"/>
@elseif($__icon !== '')
    <span style="font-size:{{ $size }}px;line-height:1;display:inline-block;vertical-align:middle">{{ $__icon }}</span>
@else
    <x-icon name="grid" :size="$size"/>
@endif
