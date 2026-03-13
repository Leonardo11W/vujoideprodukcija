<?php

use Modules\Logistic\Http\Controllers\Backend\API\LogisticController;
use Modules\Logistic\Http\Controllers\Backend\API\LogisticZoneController;

Route::get('get-logisticzone-list', [LogisticZoneController::class, 'logisticzoneList']);
Route::get('shipping-zone-list', [LogisticZoneController::class, 'shippingZoneList']);
Route::post('add-logistics', [LogisticController::class, 'addLogistics']);
Route::post('add-shipping-zone', [LogisticZoneController::class, 'addShippingZone']);
