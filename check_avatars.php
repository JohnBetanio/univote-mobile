<?php
require __DIR__ . '/univote-web/vendor/autoload.php';
$app = require_once __DIR__ . '/univote-web/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$voters = App\Models\Voter::all();
echo "Total voters: " . $voters->count() . "\n";
foreach ($voters as $v) {
    echo "ID: {$v->id}, Name: {$v->name}, Email: {$v->email}\n";
    echo "Avatar in DB: [" . var_export($v->avatar, true) . "]\n";
    echo "avatar_url: [" . var_export($v->avatar_url, true) . "]\n";
}
