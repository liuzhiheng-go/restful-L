<?php

header('content-type:text/html;charset=utf-8');

$pdo=require_once './lib/db.php';
require_once './lib/error.php';
require_once './lib/user.php';
require_once './lib/article.php';
class restful
{
	private $user;
	
	private $article;
	private $requestMethod;
	private $resourceName;
	private $id;
	private $allowResource=['article','user'];
	private $allowRequest=['GET','POST','DELETE','OPTION','PUT'];
	private $statusCode=[
			// Informational 1xx
			100 => 'Continue',
			101 => 'Switching Protocols',
			// Success 2xx
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			// Redirection 3xx
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',  // 1.1
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			// 306 is deprecated but reserved
			307 => 'Temporary Redirect',
			// Client Error 4xx
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			422 => 'Unprocessable Entity',
			// Server Error 5xx
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			509 => 'Bandwidth Limit Exceeded'			
	];
	function __construct($user,$article)
	{
		$this->user=$user;
		$this->article=$article;
	}
	function run()
	{
		try {
			$this->setupRequestMethod();
			$this->setupResource();
			
			if($this->resourceName=='user')
			{				
				return $this->jason($this->handlerUser());	
			}
			elseif ($this->resourceName=='article')
			{
				return $this->jason($this->handleArticle());
			}
		}
		catch (Exception $e)
		{
			echo $this->jason(['error'=>$e->getMessage()],$e->getCode());
		}
		
		
	}
	
	private function handlerUser()
	{
		if($_SERVER["REQUEST_METHOD"]!='POST')
		{
			throw new Exception('请求方法不被允许', 405);
		}
		$body=$this->getBodyParams();
		if(empty($body['username']))
		{
			throw new Exception('用户名不能为空', 400);
		}
		if(empty($body['password']))
		{
			throw new Exception('密码不能为空', 400);
		}
		
		return $this->user->regist($body['username'],$body['password']);
		
		 
	}
	private function handleArticle()
	{	
		$user = $this->requestAuth();
		if ($this->requestMethod == 'GET')
		{			
			if(empty($this->id))
			{
				return $this->jason($this->article->getList($user['user_id'],isset($_GET['page'])?$_GET['page']:1,isset($_GET['size'])?$_GET['size']:1));
			}
			
			return $this->jason($this->article->view($this->id));
		}
		if ($this->requestMethod == 'POST')
		{
			$body=$this->getBodyParams();	
			if(empty($body['title']))
			{
				throw new Exception('文章标题没有', 400);
			}
			if(empty($body['content']))
			{
				throw new Exception('文章内容没有', 400);
			}
			return $this->jason($this->article->add($body['title'],$body['content'],$user['user_id']));
		}
		if ($this->requestMethod == 'DELETE')
		{
			return $this->article->delete($this->id,$user['user_id']);
		}
		if ($this->requestMethod == 'PUT')
		{
			$body=$this->getBodyParams();
			return $this->article->edit($this->id,$body['title'],$body['content'],$user['user_id']);
		}
	}
	private function requestAuth()
	{		
		if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW']))
		{			
			try
			{
				$user = $this->user->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
				return $user;
			}
			catch (Exception $e)
			{
				echo $this->jason(['error'=>$e->getMessage()],$e->getCode());
				die;
			}
		}
		
		header("WWW-Authenticate:Basic realm='Private'");
		header('HTTP/1.0 401 Unauthorized');
		print "You are unauthorized to enter this area.";
		exit(0);
	}
	private function getBodyParams()
	{
		$raw=file_get_contents('php://input');	
		if(empty($raw))
		{
			throw new Exception('请求参数错误', 400);
		}
		return json_decode($raw,true);
	}
	
	private function jason($message,$code=0)
	{
		header('content-type:application/json;charset=utf-8');
		if($code>0 && $code!=200 && $code!=204)
		{
			header('HTTP/1.1 '.$code." ".$this->statusCode[$code]);
		}
		echo json_encode($message,JSON_UNESCAPED_UNICODE );
		die;
	}
	private function setupRequestMethod()
	{
		$this->requestMethod=$_SERVER['REQUEST_METHOD'];
		if(!in_array($this->requestMethod, $this->allowRequest))
		{
			throw new Exception('请求方法不被允许', 405);
			die;
		}
	}
	private function setupResource()
	{
		$path=$_SERVER['PATH_INFO'];
		$param=explode('/', $path);
		
		$this->resourceName=$param[1];
		if(!in_array($this->resourceName, $this->allowResource))
		{
			throw new Exception('请求资源不被允许', 400);
		}
		if(!empty($param[2]))
		{
			$this->id=$param[2];
		}
	}
}

$user=new user($pdo);
$article=new article($pdo);

$restful=new restful($user, $article);
$restful->run();
