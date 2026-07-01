<?php

return [
    'image_upload_max_kb' => (int) env('RECEPTION_IMAGE_UPLOAD_MAX_KB', 5120),
    'ai_timeout_seconds' => (int) env('RECEPTION_AI_TIMEOUT_SECONDS', 20),
];
