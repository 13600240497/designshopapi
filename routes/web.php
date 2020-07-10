<?php

// 首页
Route::group(['namespace' => 'Common'], function () {
    Route::any('/', 'IndexController@index');
});

// Native Activity
Route::group(['namespace' => 'Native\Activity', 'prefix' => 'activity'], function () {

    Route::match(['get', 'post'], '/page/asyncInfo', 'PageController@asyncInfo');
});

// 组件PC端接口
Route::group(['namespace' => 'Web\PC', 'prefix' => 'web/pc'], function () {
    Route::match(['get', 'post'], '/goods/getSopGoodsDetail', 'GoodsController@getSopGoodsDetail');
    Route::match(['get', 'post'], '/goods/getAutoRefreshUiGoodsList', 'GoodsController@getAutoRefreshUiGoodsList');
});

// 组件M端接口
Route::group(['namespace' => 'Web\M', 'prefix' => 'web/m'], function () {
    Route::match(['get', 'post'], '/goods/getSopGoodsDetail', 'GoodsController@getSopGoodsDetail');
    Route::match(['get', 'post'], '/goods/getAutoRefreshUiGoodsList', 'GoodsController@getAutoRefreshUiGoodsList');
});


// geshop
Route::group(['namespace' => 'Geshop', 'prefix' => 'geshop'], function () {

    Route::match(['get', 'post'], '/design/goodsInfo', 'DesignController@goodsInfo');
    Route::match(['get', 'post'], '/design/asyncInfo', 'DesignController@asyncInfo');
    Route::match(['get', 'post'], '/design/fallback', 'DesignController@fallback');
    Route::match(['get', 'post'], '/design/esSearchSortByList', 'DesignController@esSearchSortByList');
});

// common
Route::group(['namespace' => 'Common', 'prefix' => 'common'], function () {

    Route::get('/health/check', 'HealthController@check');
});

// admin
Route::group(['namespace' => 'Admin', 'prefix' => 'admin'], function () {

    Route::get('/log/download', 'LogController@download');
    Route::get('/log/list', 'LogController@logList');
    Route::get('/log/view', 'LogController@view');
});

// test
Route::group(['namespace' => 'Test', 'prefix' => 'test'], function () {
    Route::match(['get', 'post'], '/rms/trigger', 'RmsController@trigger');
});
