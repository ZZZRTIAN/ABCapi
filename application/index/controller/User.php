<?php
/**
 * Created by PhpStorm.
 * User: speechx-wechat
 * Date: 2017/10/30
 * Time: 14:30
 */

namespace app\index\controller;

use app\index\auth\BasicAuth;
use app\index\auth\OauthAuth;

class User extends Base
{
    public function test(){
        $oa = new BasicAuth();
        dump($oa->getUser());
    }
}