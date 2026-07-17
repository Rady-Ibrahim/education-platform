<?php

return [
    'autosave_rate_limit' => (int) env('EXAM_AUTOSAVE_RATE_LIMIT', 60),
    'autosave_rate_decay_seconds' => (int) env('EXAM_AUTOSAVE_RATE_DECAY', 60),
    'submit_rate_limit' => (int) env('EXAM_SUBMIT_RATE_LIMIT', 10),
    'submit_rate_decay_seconds' => (int) env('EXAM_SUBMIT_RATE_DECAY', 60),
];
