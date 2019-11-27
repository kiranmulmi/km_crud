<?php


Route::group([
    'middleware' => [
        'web',
        'auth'
    ],
], function () {

    $genericTaxonomies = config('km_crud.taxanomy');
    foreach ($genericTaxonomies as $key => $item) {

        Route::get('taxonomy/{' . $key . '}', 'KM\KMCrud\KMCrudController@txIndex');
        Route::get('taxonomy/{' . $key . '}/create', 'KM\KMCrud\KMCrudController@txForm');
        Route::get('taxonomy/{' . $key . '}/edit/{id}', 'KM\KMCrud\KMCrudController@txForm');
        Route::post('taxonomy/{' . $key . '}/save/{id?}', 'KM\KMCrud\KMCrudController@txSave');
        Route::get('taxonomy/{' . $key . '}/list-all-json', 'KM\KMCrud\KMCrudController@txListAllJson');
        Route::post('taxonomy/weight/update', 'KM\KMCrud\KMCrudController@weightUpdate');
    }

    //GENERIC CRUD ROUTES
    $genericModules = config('km_crud.modules');
    foreach ($genericModules as $url => $item) {

        Route::get($url, 'KM\KMCrud\KMCrudController@index');
        Route::get($url .'/list-all-json', 'KM\KMCrud\KMCrudController@listAllJson');
        Route::get($url .'/create', 'KM\KMCrud\KMCrudController@form');
        Route::get($url .'/edit/{id}', 'KM\KMCrud\KMCrudController@form');
        Route::get($url .'/view/{id}', 'KM\KMCrud\KMCrudController@view');
        Route::post($url .'/save/{id?}', 'KM\KMCrud\KMCrudController@save');
        Route::get($url .'/delete/{id}', 'KM\KMCrud\KMCrudController@delete');
        Route::get($url .'/list-all-rel-json', 'KM\KMCrud\KMCrudController@listAllRelJson');
    }
});
