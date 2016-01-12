<?php
namespace wdf;
$loader = require __DIR__ . '/vendor/autoload.php';
use wdf\web\HttpRequest;
use wdf\wechat\WebRequest;
use wdf\wechat\Account;
use wdf\wechat\User;
use wdf\wechat\LoginResponse;
use wdf\wechat\ChatGroup;
use Httpful;
use Httpful\Mime;
use Httpful\Handlers\JsonHandler;
\Logger::configure("wdf.xml");
/**
 * Class wdf
 * @author yulw
 */
class wdf {
    static $inst;

    private $account;

    private $loginResponse;

    static $logger;

    private $zombie;

    private function __construct($args) {
        foreach($args as $key=>$val) {
            if($keys===null)
                continue;
            if(property_exists($this,$keys))
                $this->$key=$val;
        }
        $this->account=new Account();
        $this->loginResponse=new LoginResponse();
        $this->zombie=array();
        static::$logger=\Logger::getLogger("wdf");
    }
    public static function getInst() {
        if(!isset($inst)||$inst===null)
            $inst=new wdf(array());
        return $inst;
    }
    public function __set($key,$name) {
        if(property_exists(__class__,$key)) {
            $this->$key=$val;
        }
    }
    public function __get($key) {
        if(!property_exists(__class__,$key))
            return null;
        return $this->$key;
    }
    private function getInitInfo(&$json) {
        $MySelf='User';
        $user=new User();
        foreach($json->$MySelf as $key=>$val) {
            if($key!==null&&property_exists($user,$key)) {
                $user->$key=$val;
            }
        }
        $this->account->Self=$user;
    }
    private function getContactList(&$json,&$account) {
        $ContactCount='MemberCount';
        if($account!==null&&property_exists($account,$ContactCount))
            $account->{$ContactCount}=intval($json->$ContactCount);
        $ContactList='MemberList';
        if($account===null||!property_exists($account,$ContactList))
            return;
        $account->{$ContactList}=array();
        $invalidName=array();
        foreach($json->$ContactList as $index=>$list) {
            $user=new User();
            foreach($list as $key=>$val) {
                if($key!==null&&property_exists($user,$key)) {
                    $user->$key=$val;
                }
            }
            if($user->inValid()) {
                $invalidName[]=$user->NickName;
                static::$logger->info($user->NickName);
            }
            else {
                $account->{$ContactList}[]=$user;
            }
        }
    }
    private function getChatGroup(&$json,&$group) {
        $generalProperty=array('Topic','ChatRoomName','BlackList');
        foreach($json as $key=>$val) {
            if($key!==null&&in_array($key,$generalProperty)) {
                $group->$key=$val;
            }
        }
        $this->getContactList($json,$group);
    }
    private function check() {
        $GroupContactList=array();
        $topic="group";
        $index=0;
        foreach($this->account->MemberList as $member) {
            if($member->UserName===$this->account->Self->UserName)
                continue;
            static::$logger->info('Building chatroom.Adding '.$member->NickName);
            $GroupContactList[]=$member->UserName;
            if(sizeof($GroupContactList)===80) {
                $this->checkGroup($topic.'_'.($index++),$GroupContactList);
                $GroupContactList=array();
            }
        }
        if(sizeof($GroupContactList)>=2) {
            $this->checkGroup($topic.'_'.($index++),$GroupContactList);
            $GroupContactList=array();
        }
        return;
    }

    private function checkGroup($topic,$GroupContactList) {
        $group=new ChatGroup();
        $json=WebRequest::createChatRoom($topic,$GroupContactList,$this->account,$this->loginResponse);
        static::info($json);
        $this->getChatGroup($json,$group);
        $DelNameList=array();
        foreach($group->MemberList as $member) {
            $DelMemberList[]=$member->UserName;
            if($member->MemberStatus===4)
                $this->zombie[]=$member->NickName;
        }
        WebRequest::deleteMember($group->ChatRoomName,$DelNameList);
        return;
    }

    public function run() {
        try {
            WebRequest::getUUID($this->account);
            $img_path=WebRequest::showQRImage($this->account,'../data');
            while(!WebRequest::waitForLogin($this->account))
                sleep(1);
            static::$logger->info("success to login");
            WebRequest::login($this->account,$this->loginResponse);

            $json=WebRequest::webwxinit($this->account,$this->loginResponse);

            $this->getInitInfo($json);

            $json=WebRequest::webwxgetcontact($this->account,$this->loginResponse);

            $this->getContactList($json,$this->account);

            $this->check($this->loginResponse);

            $this->logger->info('Zombie Contact List...............');

            $this->logger->info($this->zombie);

        }catch(\Exception $e) {
            static::$logger->error($e);
        }
    }
};
wdf::getInst()->run();
