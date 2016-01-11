<?php
namespace wdf;
$loader = require __DIR__ . '/vendor/autoload.php';
use wdf\web\HttpRequest;
use wdf\wechat\Account;
use wdf\wechat\LoginResponse;
/**
 * Class wdf
 * @author yulw
 */
class wdf {
    public static function getUUID(Account &$acc) {
        $url='https://login.weixin.qq.com/jslogin';
        $params=array('appid'=>"wx782c26e4c19acffb",
            'fun' =>"new",
            'lang' =>"zh_CN",
            '_' => time(),
        );
        $request=new HttpRequest($url,HttpRequest::METH_POST);
        $request->addPostFields(http_build_query($params));
        $response=$request->getResponse();
        if(!$response->hasBody())
            throw new \Exception("Failed to getUUID.");
        $body=$response->_parse($response->body);
        $regx = '/window.QRLogin.code = (\d+); window.QRLogin.uuid = "(\S+?)"/';
        preg_match($regx,$body,$matches);
        if($matches!==null&&sizeof($matches)==3&&intval($matches[1])===200) {
            if($acc!==null)
                $acc->uuid=trim($matches[2]);
            return trim($matches[2]);
        }
        return null;
    }
    public static function showQRImage(Account &$account,$path_to_write) {
        $uuid=$account->uuid;
        $url = "https://login.weixin.qq.com/qrcode/".$uuid;
        $params =array(
            't' => "webwx",
            '_' => time(),
        );
        $request=new HttpRequest($url,HttpRequest::METH_POST);
        $request->addPostFields(http_build_query($params));
        $response=$request->getResponse();
        if($path_to_write!==null) {
            if($path_to_write===null)
                throw new \Exception("Path not found");
            if(!file_exists($path_to_write))
                mkdir($path_to_write);
            $path=__DIR__.$path_to_write.'/'.'wdf.jpg';
            $img=fopen($path,"w");
            fwrite($img,$response->body);
            print('image saved to'.$path."\n");
        }
        #echo "<img src=\"data:image/png;base64,". base64_encode($response->body)."\"/>\n";
        return $path;
    }
    public static function waitForLogin(Account &$account) {
        $uuid=$account->uuid;
        $url = sprintf('https://login.weixin.qq.com/cgi-bin/mmwebwx-bin/login?tip=%s&uuid=%s&_=%s',1, $uuid, time());
        $request=new HttpRequest($url,HttpRequest::METH_POST);
        $request->setRequestProperty('withStrictSSL',null);
        $response=$request->getResponse();
        if(!$response->hasBody())
            throw new \Exception("Failed to request Login.");
        $body=$response->_parse($response->body);
        $regx = '/window.code=(\d+);/';
        preg_match($regx,$body,$matches);
        if($matches!==null&&sizeof($matches)==2&&intval($matches[1])===200) {
            $regx = '/window.redirect_uri="(\S+?)";/';
            preg_match($regx,$body,$matches);
            $account->redirect_url = $matches[1].'&fun=new';
            $index=strrpos($account->redirect_url,"/");
            $account->base_url=substr($account->redirect_url,0,$index);
            return true;
        }
        return false;
    }
    public static function login(Account &$account,LoginResponse &$loginresponse) {
        $request=new HttpRequest($account->redirect_url,HttpRequest::METH_POST);
        $request->setRequestProperty('withStrictSSL',null);
        $response=$request->getResponse();
        if(!$response->hasBody())
            throw new \Exception("Failed to request Login.");
        $body=$response->_parse($response->body);
        static::parse($body,$loginresponse);
    }
    public static function webwxinit(Account $account,LoginResponse $loginresponse) {
        $api='webwxinit';
        $url = sprintf($account->base_url.'/'.$api.'?pass_ticket=%s&skey=%s&r=%d',$loginresponse->pass_ticket,$loginresponse->skey, time());
        $headers=array('ContentType'=>'application/json;charset=UTF-8');
        $BaseRequest=static::getBaseRequest($account,$loginresponse);
        $params=array('BaseRequest'=>$BaseRequest);
        $json=static::request($api,$url,$headers,$params,HttpRequest::METH_POST);
        return $json;
    }
    public static function webwxgetcontact(Account $account,LoginResponse $loginresponse) {
        $api='webwxgetcontact';
        $url = sprintf($account->base_url.'/'.$api.'?pass_ticket=%s&skey=%s&r=%d',$loginresponse->pass_ticket,$loginresponse->skey, time());
        $headers=array('ContentType'=>'application/json;charset=UTF-8');
        $BaseRequest=static::getBaseRequest($account,$loginresponse);
        $params=array('BaseRequest'=>$BaseRequest);
        $json=static::request($api,$url,$headers,$params,HttpRequest::METH_POST);
        return $json;
    }
    public static function createChatRoom(array $namelist,Account $account,LoginResponse $loginresponse) {
        $api='webwxcreatechatroom';
        $url = sprintf($account->base_url.'/'.$api.'?pass_ticket=%s&skey=%s&r=%d',$loginresponse->pass_ticket,$loginresponse->skey, time());
        $headers=array('ContentType'=>'application/json;charset=UTF-8');
        $BaseRequest=static::getBaseRequest($account,$loginresponse);
        $MemberList=array();
        foreach($namelist as $UserName)
            $MemberList[]=array('UserName',$UserName);
        $MemberCount=sizeof($namelist);
        $params=array(
            'BaseRequest'=>$BaseRequest,
            'MemberCount'=> $MemberList,
            'MemberList'=>$MemberCount,
            'Topic'=>'web_wechat_check'
        );
        $json=static::request($api,$url,$headers,$params,HttpRequest::METH_POST);
        return $json;
    }
    public static function deleteMember($chatroom,array $namelist) {
        $api='webwxupdatechatroom';
        $url = sprintf($account->base_url.'/'.$api.'?fun=delmember&pass_ticket=%s',$loginresponse->pass_ticket);
        $headers=array('ContentType'=>'application/json;charset=UTF-8');
        $BaseRequest=static::getBaseRequest($account,$loginresponse);
        $DelMemberList=implode(',',$namelist);
        $params=array(
            'BaseRequest'=>$BaseRequest,
            'ChatRoomName'=>$chatroom,
            'DelMemberList'=>$DelMemberList
        );
        $json=static::request($api,$url,$headers,$params,HttpRequest::METH_POST);
        return $json;
    }
    public static function addMember($chatroom,array $namelist) {
        $api='webwxupdatechatroom';
        $url = sprintf($account->base_url.'/'.$api.'?fun=addmember&pass_ticket=%s',$loginresponse->pass_ticket);
        $headers=array('ContentType'=>'application/json;charset=UTF-8');
        $BaseRequest=static::getBaseRequest($account,$loginresponse);
        $AddMemberList=implode(',',$namelist);
        $params=array(
            'BaseRequest'=>$BaseRequest,
            'ChatRoomName'=>$chatroom,
            'AddMemberList'=>$AddMemberList
        );
        $json=static::request($api,$url,$headers,$params,HttpRequest::METH_POST);
        return $json;
    }
    public static function request($api,$url,$headers,$params,$method){
        $request=new HttpRequest($url,$method);
        $request->setHeaders($headers);
        $request->addPostFields(json_encode($params));
        $response=$request->getResponse();
        $request->setRequestProperty('expectsType','json');
        if(!$response->hasBody())
            throw new \Exception("Failed to request ".$api."\n");
        $file=fopen(__DIR__.'/data/'.$api.'.json','w');
        fprintf($file,"%s",$response->body);
        fclose($file);
        $json=$response->_parse($response->body);
        $BaseResponse='BaseResponse';
        $Ret='Ret';
        if($json->$BaseResponse->$Ret!==0)
            echo 'error in '.$api."\n";
        return $json;
    }

    private static function getBaseRequest(&$accoumt,&$loginresponse) {
        $deviceId = 'e000000000000000';
        $BaseRequest = array(
            'Uin'=>$loginresponse->wxuin,
            'Sid'=>$loginresponse->wxsid,
            'Skey'=>$loginresponse->skey,
            'DeviceID'=>$deviceId,
        );
        return $BaseRequest;
    }
    private static function parse($body,LoginResponse &$loginresponse) {
        $doc=new \DOMDocument();
        $doc->loadXML($body);
        if($doc->childNodes!==null) {
            foreach($doc->childNodes as $child)
                static::dfs($child,$loginresponse);
        }
    }
    private static function dfs(\DOMNode &$node,LoginResponse &$loginresponse) {
        if($node->nodeName!==null&&property_exists($loginresponse,$node->nodeName))
            $loginresponse->{$node->nodeName}=$node->nodeValue;
        if($node->childNodes!==null) {
            foreach($node->childNodes as $child)
                static::dfs($child,$loginresponse);
        }
    }
    public static function run() {
        try {
            $account=new Account();
            $loginPara=new LoginResponse();
            static::getUUID($account);
            $img_path=static::showQRImage($account,'./data');
            while(1) {
                if(static::waitForLogin($account)) {
                    echo "successed to login\n";
                    break;
                }
                sleep(1);
            }
            static::login($account,$loginPara);

            static::webwxinit($account,$loginPara);

            static::webwxgetcontact($account,$loginPara);

        }catch(\Exception $e) {
            var_dump($e);
        }
    }
};
wdf::run();
