<?php

// App 接口
Route::group(['namespace' => 'App\Activity', 'prefix' => 'api'], function () {
    Route::post('/promotion/{id}', 'PageController@show');
});

// App 活动页面接口(拆分后)
Route::group(['namespace' => 'App\Activity', 'prefix' => 'app/activity'], function () {
    Route::post('/page/detail', 'PageController@detail');
    Route::post('/page/asyncInfo', 'PageController@asyncInfo');
});
