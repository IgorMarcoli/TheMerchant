<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$disk = Illuminate\Support\Facades\Storage::disk('s3');
echo "URL: " . $disk->url('test-s3.txt') . PHP_EOL;
echo "EXISTS: " . ($disk->exists('test-s3.txt') ? 'YES' : 'NO') . PHP_EOL;
echo "DELETE: " . ($disk->delete('test-s3.txt') ? 'YES' : 'NO') . PHP_EOL;
