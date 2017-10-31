<?php
/**
 * Created by PhpStorm.
 * User: speechx-wechat
 * Date: 2017/10/31
 * Time: 9:17
 */

namespace app\index\controller;

use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;
use think\Request;

class Wechat extends Base
{
    public $wechatApp;

    public $payment;

    protected function options(){
        return [
            // 前面的appid什么的也得保留哦
            'app_id'                 => sysconf('wechat_appid'),                     // AppSecret
            'secret'                 => sysconf('wechat_appsecret'),                // AppSecret
            // ...
            // payment
            'payment' => [
                'merchant_id'        => sysconf('wechat_mch_id'),
                'key'                => sysconf('wechat_partnerkey'),
                'cert_path'          => 'path/to/your/cert.pem',            // XXX: 绝对路径！！！！
                'key_path'           => 'path/to/your/key',                 // XXX: 绝对路径！！！！
                'notify_url'         => '默认的订单回调地址',                // 你也可以在下单时单独设置来想覆盖它
                // 'device_info'     => '013467007045764',
                // 'sub_app_id'      => '',
                // 'sub_merchant_id' => '',
                // ...
            ],
            'guzzle' => [
                'timeout' => 3.0, // 超时时间（秒）
                'verify' => false, // 关掉 SSL 认证（强烈不建议！！！）
            ],
        ];
    }

    public function __construct()
    {
        $this->wechatApp = new Application( $this->options() );
        $this->payment = $this->wechatApp->payment;
    }

    public function oauth(Request $request){
        if ($request->isGet() && $request->get('code')) {
            $token = $this->wechatApp->oauth->getAccessToken( $request->get('code') );
            dump( $token );
        }
        else{
            return $this->sendError(10006, '授权登录失败');
        }
    }

    public function pay(Request $request){

        if ($request->isGet() && $request->get('')) {
            // 创建订单
            $order = new Order($this->createOrder());
            $result = $this->payment->prepare($order);

            // 统一下单
            if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
                $prepayId = $result->prepay_id;

                // 生成 APP 支付配置
                $config = $this->payment->configForAppPayment($prepayId);
                return $this->sendSuccess($config);
            } else {
                return $this->sendError(10006, '订单生成失败');
            }
        }
    }

    public function paynotify(){

    }

    protected function createOrder(){

        $attributes = [
            'trade_type'       => 'APP', // JSAPI，NATIVE，APP...
            'body'             => 'iPad mini 16G 白色',
            'detail'           => 'iPad mini 16G 白色',
            'out_trade_no'     => '1217752501201407033233368018',
            'total_fee'        => 1, // 单位：分
            'notify_url'       => 'http://xxx.com/order-notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid'           => '当前用户的 openid', // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
            // ...
        ];
        return $attributes;
    }

}