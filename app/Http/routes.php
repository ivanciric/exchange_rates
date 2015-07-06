<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

$app->get('/', function() use ($app) {
    return $app->welcome();
});

//$app->get('/test', function() use ($app) {
//    return $app->test_worker();
//});

$app->get('/get_rate/{from}/{to}/{type}/{provider_code}/{date}', function($from, $to, $type, $provider_code, $date) use ($app) {

    return $app->get_rate($from, $to, $type, $provider_code, $date);
});