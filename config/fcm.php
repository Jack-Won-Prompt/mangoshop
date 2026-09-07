<?php

return [
    // Firebase 프로젝트 ID (Firebase 콘솔 → 프로젝트 설정)
    'project_id' => env('FCM_PROJECT_ID'),

    // 서비스 계정 키(JSON) 절대경로.
    // Firebase 콘솔 → 프로젝트 설정 → 서비스 계정 → 새 비공개 키 생성 → 다운로드
    // 기본 위치: storage/app/fcm-service-account.json (.gitignore 권장)
    'credentials' => env('FCM_CREDENTIALS', storage_path('app/fcm-service-account.json')),

    // 발송 비활성화(키 미설정 시 자동 skip). true 로 강제 비활성 가능.
    'disabled' => env('FCM_DISABLED', false),

    // 웹 푸시(브라우저) — Firebase 콘솔 → Cloud Messaging → 웹 구성 → 웹 푸시 인증서 '키 쌍'
    'vapid_key' => env('FCM_VAPID_KEY'),

    // 웹 푸시 클라이언트 초기화용 Firebase 웹 앱 구성
    // Firebase 콘솔 → 프로젝트 설정 → 일반 → 내 앱(웹) → SDK 설정 및 구성
    'web' => [
        'apiKey'            => env('FCM_WEB_API_KEY'),
        'authDomain'        => env('FCM_WEB_AUTH_DOMAIN'),
        'projectId'         => env('FCM_PROJECT_ID'),
        'storageBucket'     => env('FCM_WEB_STORAGE_BUCKET'),
        'messagingSenderId' => env('FCM_WEB_SENDER_ID'),
        'appId'             => env('FCM_WEB_APP_ID'),
    ],
];
