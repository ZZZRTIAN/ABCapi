<?php
// +----------------------------------------------------------------------
// | When work is a pleasure, life is a joy!
// +----------------------------------------------------------------------
// | User: ShouKun Liu  |  Email:24147287@qq.com  | Time:2017/3/26 10:01
// +----------------------------------------------------------------------
// | TITLE: 简单的Oauth客户端模式
// +----------------------------------------------------------------------

namespace app\index\auth;

use DawnApi\auth\OAuth;
use DawnApi\exception\UnauthorizedException;
use RandomLib\Factory;
use think\Db;
use think\Request;

class OauthAuth extends OAuth
{

    /**
     * 客户端获取access_token
     * @param Request $request
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     */
    public function accessToken(Request $request)
    {
        //获客户端信息
        try {
            $this->getClient($request);
        } catch (UnauthorizedException $e) {
            //错误则返回给客户端
            return $this->sendError(401, $e->getMessage(), 401, [], $e->getHeaders(),['WWW-Authenticate' => 'Basic']);
        } catch (Exception $e) {
            return $this->sendError(500, $e->getMessage(), 500);
        }

        //校验信息
        if ($this->getClientInfo($this->client_id)->checkSecret()) {
            //通过下放令牌
            $access_token = $this->setAccessToken();
        } else {
            return $this->sendError(401, 'authentication Failed', 401, [], ['WWW-Authenticate' => 'Basic']);
        }
        return $this->sendSuccess([], 'success', 200, [], [
            'access_token' => $access_token, //访问令牌
            'expires' => self::$expires,      //过期时间秒数
        ]);


    }

    /**
     * 返回用户信息
     * @param $client_id
     * @return array
     */
    public static function getUserInfo($client_id)
    {
        $userInfo = Db::name('User')->where('username',$client_id)->find();
        $client['client_id'] = $userInfo['id'];
        $client['secret'] = $userInfo['password'];
        $client['original'] = $userInfo;
        return $client;
    }

    /**
     * 获取客户端所有信息
     * @param $client_id
     * @return mixed
     */
    public function getClientInfo($client_id)
    {
        // 通过客户端$client_id 获取所有信息
        $this->clientInfo = self::getUserInfo($client_id);
        return $this;
    }

    /**
     * 校验密码
     * @return bool
     */
    public function checkSecret()
    {
        if ($this->secret == $this->clientInfo['secret']) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * 设置AccessToken
     * @param $clientInfo
     * @return int
     */
    protected function setAccessToken()
    {
        //生成令牌
        $accessToken = self::buildAccessToken();

        // DB存储令牌
        $Map = ['username' => $this->client_id, 'password' => $this->secret];
        $Update = ['access_token' => $accessToken, 'expires_in' => time() + self::$expires];
        Db::name('User')->where($Map)->update($Update);

        return $accessToken;
    }



    /**
     * 获取用户信息后 验证权限
     * @return mixed
     */
    public function certification()
    {
        if ($this->getAccessTokenInfo($this->access_token) == false) {
            return false;
        } else {
            return true;
        }
    }

    protected function getAccessTokenInfo($accessToken)
    {
        $info = $userInfo = Db::name('User')->where('access_token',$accessToken)->find();

        if ($info == false || $info['expires_in'] < time()) return false;

        return $info;
    }
    /**
     * 获取用户信息
     * @return bool
     */
    public function getUser()
    {

        $info = $this->getAccessTokenInfo($this->access_token);
        if ($info) {
            $this->client_id = $info['username'];
            $this->user = $info;
            return $this->user;
        } else {
            return false;
        }

    }


    /**
     * 生成AccessToken
     * @return string
     */
    protected static function buildAccessToken()
    {
        //生成AccessToken
        $factory = new Factory();
        $generator = $factory->getMediumStrengthGenerator();
        return $generator->generateString(32, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }
}