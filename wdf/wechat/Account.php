<?php
namespace wdf\wechat;
use wdf\wechat\User;
/**
 * Class WechatAccount
 * @author yulw
 */
class Account{
    public $uuid;

    public $base_url;

    public $redirect_url;

    #Myself,fetched with /webwxinit using property 'User';
    public $Self;

    public $MemberCount;

    #An array of User objects;
    public $MemberList;
}
