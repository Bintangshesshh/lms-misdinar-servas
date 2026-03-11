<?php

$profile = env('EXAM_LOAD_PROFILE', 'normal');
$isCrowded = $profile === 'crowded';
$minPollMs = max(5000, (int) env('EXAM_MIN_POLL_MS', 5000));

$studentPollBaseDefault = $isCrowded ? 18000 : 15000;
$studentPollJitterDefault = $isCrowded ? 6000 : 5000;

$adminLobbyDefault = $isCrowded ? 6000 : 5000;
$adminCountdownDefault = $isCrowded ? 6000 : 5000;
$adminRunningSmallDefault = $isCrowded ? 7000 : 5000;
$adminRunningMediumDefault = $isCrowded ? 8000 : 7000;
$adminRunningLargeDefault = $isCrowded ? 10000 : 9000;
$adminJitterDefault = $isCrowded ? 1500 : 1000;

return [
    'profile' => $profile,
    'min_poll_ms' => $minPollMs,

    'student' => [
        'save_debounce_mc_ms' => (int) env('EXAM_SAVE_DEBOUNCE_MC_MS', 700),
        'save_debounce_essay_ms' => (int) env('EXAM_SAVE_DEBOUNCE_ESSAY_MS', 3000),
        'sync_debounce_ms' => (int) env('EXAM_SYNC_DEBOUNCE_MS', 1800),
        'sync_min_interval_ms' => (int) env('EXAM_SYNC_MIN_INTERVAL_MS', 2500),
        'sync_retry_interval_ms' => (int) env('EXAM_SYNC_RETRY_INTERVAL_MS', 6000),
        'sync_periodic_interval_ms' => (int) env('EXAM_SYNC_PERIODIC_INTERVAL_MS', 20000),
        'poll_base_interval_ms' => max($minPollMs, (int) env('EXAM_STUDENT_POLL_BASE_MS', $studentPollBaseDefault)),
        'poll_jitter_ms' => (int) env('EXAM_STUDENT_POLL_JITTER_MS', $studentPollJitterDefault),
        'reinstate_poll_interval_ms' => max($minPollMs, (int) env('EXAM_STUDENT_REINSTATE_POLL_MS', 10000)),
        'bulk_sync_chunk_size' => (int) env('EXAM_BULK_SYNC_CHUNK_SIZE', 8),
    ],

    'admin' => [
        'poll_lobby_ms' => max($minPollMs, (int) env('EXAM_ADMIN_POLL_LOBBY_MS', $adminLobbyDefault)),
        'poll_countdown_ms' => max($minPollMs, (int) env('EXAM_ADMIN_POLL_COUNTDOWN_MS', $adminCountdownDefault)),
        'poll_running_small_ms' => max($minPollMs, (int) env('EXAM_ADMIN_POLL_RUNNING_SMALL_MS', $adminRunningSmallDefault)),
        'poll_running_medium_ms' => max($minPollMs, (int) env('EXAM_ADMIN_POLL_RUNNING_MEDIUM_MS', $adminRunningMediumDefault)),
        'poll_running_large_ms' => max($minPollMs, (int) env('EXAM_ADMIN_POLL_RUNNING_LARGE_MS', $adminRunningLargeDefault)),
        'poll_jitter_ms' => (int) env('EXAM_ADMIN_POLL_JITTER_MS', $adminJitterDefault),
    ],
];
