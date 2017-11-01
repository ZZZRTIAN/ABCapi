<?php
/**
 * Created by PhpStorm.
 * User: speechx-wechat
 * Date: 2017/10/30
 * Time: 11:08
 */

namespace app\index\controller;

use DawnApi\facade\ApiController;

class Base extends ApiController
{
    //是否开启授权认证
    public    $apiAuth = true;

    protected $type = 'json';
}