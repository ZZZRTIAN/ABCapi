<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;

//Route::any('user','index/User/oauth');

//Route::group('v1',function (){
//    Route::any('user/oauth','index/Index/oauth');
////    Route::resource('user','index/Index');
//});
//
//Route::group('v1',function (){
//    Route::any('user/sendCode','demo/User/sendCode');
//    Route::resource('user','demo/User');
//});
//
Route::any('accessToken','index/auth/accessToken');//Oauth


return [

];
