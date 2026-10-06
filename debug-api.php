<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$phanTich = $app->make(App\Services\PhanTichHaTang::class);
// bbox bao trum toan bo tuyen 500kV: minLng,minLat,maxLng,maxLat
$bbox = [106.0, 16.4, 107.5, 18.1];
$features = $phanTich->trongKhungNhin($bbox, 4000);
echo 'so features tra ve: '.count($features)."\n";
foreach ($features as $f) {
    echo '- '.$f['geometry']['type'].' loai='.$f['properties']['loai'].' kv='.var_export($f['properties']['kv'],true)."\n";
}
