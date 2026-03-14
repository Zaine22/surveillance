<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/case-managements/get-external-case', 'GET');
$response = $app->handle($request);
echo 'Status: '.$response->getStatusCode()."\n";
echo "Content: \n";
echo $response->getContent()."\n";
