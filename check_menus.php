<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$items = Modules\MenuBuilder\Models\MenuBuilder::all();
foreach($items as $item){
    echo "ID: " . $item->id . " | Title: " . $item->title . " | Perms: " . json_encode($item->permission) . "\n";
}
