<?php
/**
 * Created by PhpStorm.
 * User: speechx-wechat
 * Date: 2017/10/30
 * Time: 14:30
 */

namespace app\index\controller;

use app\index\auth\OauthAuth;
use think\Controller;
use think\Db;
use think\Request;

class User extends Base
{

    //跳过鉴权的方法
    protected $skipAuthActionList = ['test'];

    public function test(){
//        $userId = self::$app['auth']->getUser()['id'];
//        dump($userId);
        // 商品id 和 uid
//        $map = ['id'=>$userId, 'cid'=>1];
        $map = ['id'=>2, 'cid'=>1];
        if ( !empty( Db::name('abc_pay')->where($map)->find() ) ) {
            return $this->sendError(10008, '未购买该商品');
        }
        else{
            return $this->sendSuccess();
        }

    }

    public function isPay(Request $request){

        if ( $request->isPost() && empty( $cid = $request->post('cid') ) ) {

            $userId = self::$app['auth']->getUser()['id'];
            $map = ['id'=>$userId, 'cid'=>$cid];
            if ( !empty( Db::name('abc_pay')->where($map)->find() ) ) {
                return $this->sendError(10008, '未购买该商品');
            }
            else{
                return $this->sendSuccess('is Pay');
            }

        }
        else{
            return $this->sendError(10000,'post?');
        }

    }

//    public function test()
//    {
//        $data = [
//            'name'  =>  '数字',     // 名称
//            'fee'   =>  1,     // 单价
//            'body'  =>  '解锁-单词修炼-数字',     // 商品描述
//        ];
//        Db::name('commodity')->insert($data);
//    }
}