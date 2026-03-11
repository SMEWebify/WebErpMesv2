<?php

return [
    'menu_enabled' => env('PRE_ORDERS_MENU_ENABLED', false),
    'input_path' => env('PRE_ORDERS_INPUT_PATH', 'pre-orders/input'),
    'output_path' => env('PRE_ORDERS_OUTPUT_PATH', 'output'),
    'file_pattern' => env('PRE_ORDERS_FILE_PATTERN', '*.csv'),
    'done_path' => env('PRE_ORDERS_DONE_PATH', 'output/done'),
    'python_executable' => env('PRE_ORDERS_PYTHON_EXECUTABLE'),
    'python_script' => env('PRE_ORDERS_PYTHON_SCRIPT'),
    'python_timeout' => env('PRE_ORDERS_PYTHON_TIMEOUT', 120),
];
