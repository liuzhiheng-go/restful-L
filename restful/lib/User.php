<?php
class user
{
	private $db;
	
	function __construct($db)
	{
		$this->db=$db;	
		
	}
	
	function login($user,$pass)
	{
		if(empty($user))
		{
			throw new Exception('用户名不能为空', 422);
		}
			
		if(empty($pass))
		{
			throw new Exception('密码不能为空', 422);
		}
		$pass=$this->md($pass);
		
		$sql='select * from `my_user` where user_name=:user and user_pass=:pass';
		
		$stmt=$this->db->prepare($sql);
		$stmt->bindParam(':user',$user);
		$stmt->bindParam(':pass',$pass);
		$stmt->execute();
		$rel=$stmt->fetch(PDO::FETCH_ASSOC);
		if(empty($rel))
		{
			throw new Exception('用户名密码错误',400);
		}
		return $rel;
	}
	
	function regist($user,$pass)
	{
			if(empty($user))
			{
				throw new Exception('用户名不能为空', 422);
			}
			
			if(empty($pass))
			{
				throw new Exception('密码不能为空', 422);
			}
			
			if($this->_isExist($user))
			{
				throw new Exception('用户名已经存在', 400);
			}
			
		$sql= "insert into `my_user` (`user_name`,`user_pass`,`addtime`) values(:user,:pass,:addtime)";
		
		
		$pass=$this->md($pass);
		$addtime=date('Y-m-d H:i:s',time());
		
		$stmt=$this->db->prepare($sql);
		
		$stmt->bindParam(':user',$user);
		$stmt->bindParam(':pass',$pass);
		$stmt->bindParam(':addtime',$addtime);
		$stmt->execute();
		
		if(!$stmt->execute())
		{
			throw new Exception('注册入库失败',error::REGIST_FAIL);
		}
		return [
				'user_id'=>$this->db->lastInsertId(),
				'user'=>$user,
				'addtime'=>$addtime
			   ];
	}
	
	private function md($pass)
	{
		return md5($pass.'lzh');
	}
	
	private function _isExist($user)
	{
		$sql='select * from `my_user` where user_name=:user';
		$stmt=$this->db->prepare($sql);
		$stmt->bindParam(':user',$user);
		$stmt->execute();
		$rel=$stmt->fetch(PDO::FETCH_ASSOC);
		return !empty($rel);
		
	}
}
