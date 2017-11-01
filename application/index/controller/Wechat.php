<?php
/**
 * Created by PhpStorm.
 * User: speechx-wechat
 * Date: 2017/10/31
 * Time: 9:17
 */

namespace app\index\controller;

use app\index\auth\BasicAuth;
use app\index\model\UserModel;
use app\index\model\UserWxAuthModel;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;
use service\DataService;
use think\Db;
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
                // 'notify_url'         => '默认的订单回调地址',                // 你也可以在下单时单独设置来想覆盖它
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

        if ($request->isGet() && $request->get('goodid')) {
            // 根据用户id 查询改商品有没有购买
            // 得到商品id
            $fee = 1;

            // 填充订单信息、外部订单号
            $order_no = DataService::createSequence(10, 'wechat-pay-test');

            $attributes = [
                'trade_type'       => 'APP',                                                         // JSAPI，NATIVE，APP...
                'body'             => '这里是body',
//                'detail'           => '这里是detail',
                'out_trade_no'     => $order_no,
                'total_fee'        => $fee,                                                             // 单位：分
                'notify_url'       => url('index/wechat/notify', '', true, 'abc.api.zhangruitiana.top'), // 支付结果通知网址，如果不设置则会使用配置里的默认地址
                // 'openid'           => '当前用户的 openid',                                           // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
                // ...;
            ];
            $order = new Order($attributes);
            // 创建订单
            $result = $this->payment->prepare($order);

            // 统一下单
            if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
                $out_trade_no = DataService::createSequence(18, 'WXPAY-OUTER-NO');
                $prepayId = $result->prepay_id;
                $data = [
                    'prepayid'      =>  $prepayId,
                    'order_no'      =>  $order_no,
                    'out_trade_no'  =>  $out_trade_no,
                    'fee'           =>  $fee,
                    'trade_type'    =>  'APP',
                    'from'          =>  'wechat',
                    'expires_in'    =>  time() + 5400   //微信预支付码有效时间1.5小时(最长为2小时)
                ];
                if (Db::name('WechatPayPrepayid')->insert($data) > 0) {
                    Log::notice("内部订单号{$order_no}生成预支付成功");
                }
                // 生成 APP 支付配置
                $config = $this->payment->configForAppPayment($prepayId);
                return $this->sendSuccess($config);
            } else {
                Log::error("内部订单号{$order_no}生成预支付失败");
                return $this->sendError(10006, '订单生成失败');
            }
        }
    }

    public function notify(){
        $response = $this->payment->handleNotify(function($notify, $successful){
            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单 $notify->out_trade_no商户内部订单号
            $order = Db::name('WechatPayPrepayid')->where('order_no',$notify->out_trade_no)->find();
            if (!$order) { // 如果订单不存在
                return 'Order not exist.'; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }
            // 如果订单存在
            // 检查订单是否已经更新过支付状态
            if ($order['pay_at']) { // 假设订单字段“支付时间”不为空代表已经支付
                return true; // 已经支付成功了就不再更新了
            }
            // 用户是否支付成功
            if ($successful) {
                // 记录支付通知数据
                if(!Db::name('WechatPayNotify')->insert($notify->all()) ){

                    return '系统记录微信通知时发生异常!';
                }
                $prepayMap = ['order_no' => $notify['out_trade_no']];
                $prepayData = Db::name('WechatPayPrepayid')->where($prepayMap)->find();
                if (empty($prepayData)) {
                    return '系统中未发现对应的预支付记录!';
                }
                // 不是已经支付状态则修改为已经支付状态
                $prepayUpdateData = ['transaction_id' => $notify['transaction_id'], 'is_pay' => 1, 'pay_at' => date('Y-m-d H:i:s')];
                if (false === Db::name('WechatPayPrepayid')->where($prepayMap)->update($prepayUpdateData)) {
                    return '更新系统预支付记录失败!';
                }
            }

            return true; // 返回处理完成
        });

    }

    public function test(Request $request){
        $auth = new BasicAuth();
        dump($auth->getUser());
    }
}