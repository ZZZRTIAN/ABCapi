<?php
/**
 * Created by PhpStorm.
 * User: speechx-wechat
 * Date: 2017/10/31
 * Time: 9:17
 */

namespace app\index\controller;

use app\index\model\UserModel;
use app\index\model\UserWxAuthModel;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;
use think\Exception;
use think\Log;
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
            ]
        ];
    }

    public function __construct()
    {
        $this->wechatApp = new Application( $this->options() );
        $this->payment = $this->wechatApp->payment;
    }

    public function oauth(Request $request){
        if ($request->isGet() && $request->get('code')) {
            try{
                $user = $this->wechatApp->oauth->user();
                $table_wx = new UserWxAuthModel();
                $table_user = new UserModel();

                if ( !$table_wx->where('openid',$user->getId())->find() ){
                    $table_user->wx_auth( $user->getOriginal() );

                    // 将uid于openid绑定
                    $table_wx->save(['uid'=>$table_user->id, 'openid'=>$user->getId()]);
                }

                return $this->sendSuccess([
                    'nickname'  =>  $user->getNickname(),
                    'headimgurl'=>  $user->getAvatar(),
                    'sex'       =>  $user->getOriginal()['sex']
                ]);

            }catch (\Exception $e){
                Log::error($e->getMessage());
                // \Exception所有异常的父类
                return $this->sendError(10007, '发生异常');
            }
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