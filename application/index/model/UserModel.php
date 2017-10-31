<?php
/**
 * Created by PhpStorm.
 * User: speechx-wechat
 * Date: 2017/10/31
 * Time: 14:49
 */

namespace app\index\model;


use think\Model;

class UserModel extends Model
{
    // 确定链接表名
    protected $table = 'user';

    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';

    public function wx_auth($userinfo)
    {

        $this->data([
            'username'  =>  $this->create_wx_auth_user(),
            'password'  =>  $this->create_password(),
            'nickname'  =>  $userinfo['nickname'],
            'headimgurl'=>  $userinfo['headimgurl'],
            'sex'       =>  $userinfo['sex']
        ]);

        $this->save();
    }

    protected function create_wx_auth_user()
    {
        return uniqid('wx_');
    }

    protected function create_password($pw_length = 6){
        $randpwd = '';
        for ($i = 0; $i < $pw_length; $i++) {
            $randpwd .= chr(mt_rand(33, 126));
        }
        return $this->encryptPassword( $randpwd );
    }

    // 密码加密
    protected function encryptPassword($password, $salt = '', $encrypt = 'md5')
    {
        return $encrypt($password . $salt);
    }

}