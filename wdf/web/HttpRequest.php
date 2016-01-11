<?php

namespace wdf\web;
use \Httpful\Request;
use \Httpful\Http;
use \Httpful\Response;
/**
 * Class HttpRequest
 * @author yulw
 */
#$loader = require __DIR__ . '/../vendor/autoload.php';
class HttpRequest
{
    private $url;
    private $method;
    private $headers;
    private $post_fields;

    private $http_request;
    private $http_response;

    const METH_POST      = 'POST';
    const METH_GET       = 'GET';
    const METH_PUT       = 'PUT';
    const METH_DELETE    = 'DELETE';
    const METH_HEAD      = 'HEAD';
    const METH_PATCH     = 'PATCH';
    const METH_OPTIONS   = 'OPTIONS';
    const METH_TRACE     = 'TRACE';

    function __construct($url, $method) {
        $this->url = $url;
        $this->method = $method;
        $this->headers = array();
        $this->post_fields = null;
    }

    function setHeaders($headers) {
        $this->headers = $headers;
    }

    function addPostFields($post_fields) {
        $this->post_fields = $post_fields;
    }
    function setRequestProperty($method,$para) {
        if($this->http_request===null)
            return false;
        if(!method_exists($this->http_request,$method))
            return false;
        if($para===null)
            call_user_func(array($this->http_request,$method));
        else
            call_user_func(array($this->http_request,$method),$para);
        return true;
    }
    function build() {
        $this->http_request=Request::init($this->getHttpfulMethod());
        $this->http_request=$this->http_request->uri($this->url);
        foreach($this->headers as $header_name=>$value)
            $this->http_request=$this->http_request->addHeader($header_name,$value);
        if ($this->method == HttpRequest::METH_POST && $this->post_fields)
            $this->http_request=$this->http_request->body($this->post_fields);
        return $this->http_request;
    }
    function send() {
        $this->http_response=$this->http_request->send();
        return $this->http_response;
    }
    function getResponseCode() {
        if (!$this->http_response)
            return $this->http_response->code;
        $this->build();
        $this->send();
        return $this->http_response->code;
    }

    function getResponse() {
        if($this->http_response)
            return $this->http_response;
        $this->build();
        return $this->send();
    }
    private function getHttpfulMethod() {
        if($this->method===HttpRequest::METH_POST)
            return Http::POST;
        else if($this->method===HttpRequest::METH_PUT)
            return Http::PUT;
        else if($this->method===HttpRequest::METH_DELETE)
            return Http::DELETE;
        else if($this->method=HttpRequest::METH_HEAD)
            return Http::HEAD;
        else if($this->method=HttpRequest::METH_PATCH)
            return Http::PATCH;
        else if($this->method=HttpRequest::METH_OPTIONS)
            return Http::OPTIONS;
        else
            return Http::GET;
    }
}
#test
$request=new HttpRequest('https://www.baidu.com',HttpRequest::METH_GET);
$response=$request->getResponse();
