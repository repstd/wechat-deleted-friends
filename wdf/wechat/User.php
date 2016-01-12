<?php

namespace wdf\wechat;

/**
 * Class User
 * @author yulw
 */
class User {
    use Misc;

    public $Uin;

    public $UserName;

    public $NickName;

    public $ContactFlag;

    public $MemberCount;

    public $MemberList;

    public $RemarkName;

    public $Sex;

    public $Signature;

    public $VerifyFlag;

    public $Statues;

    public $MemberStatus;

    public $SnsFlag;

    public $DisplayName;

    public function inValid() {
        if(!isset($this->UserName))
            return true;
        $flag=intval($this->VerifyFlag);
        $MAGICBIT=8;
        if(($flag & $MAGICBIT))
            return true;
        if(static::isSpecial($this->UserName)||strpos($this->UserName,'@@')!==false)
            return true;
        return false;
    }
}
trait Misc {
    static function isSpecial($name) {
        static $SpecialUsers = array(
            "newsapp", "fmessage", "filehelper", "weibo", "qqmail",
            "tmessage", "qmessage", "qqsync", "floatbottle", "lbsapp",
            "shakeapp", "medianote", "qqfriend", "readerapp", "blogapp", "facebookapp", "masssendapp",
            "meishiapp", "feedsapp", "voip", "blogappweixin", "weixin", "brandsessionholder", "weixinreminder",
            "wxid_novlwrv3lqwv11", "gh_22b87fa7cb3c", "officialaccounts",
            "notification_messages", "wxitil", "userexperience_alarm"
        );
        return in_array($name,$SpecialUsers);
    }
};
