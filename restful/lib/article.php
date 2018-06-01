<?php
class article
{
	private $db;
	function __construct($db)
	{
		$this->db=$db;
	}
	function add($title,$content,$userId)
	{
		$sql= "insert into `my_article` (`title`,`content`,`addtime`,`user_id`) values(:title,:content,:addtime,:user_id)";
		
		$addtime=date('Y-m-d H:i:s',time());
		
		$stmt=$this->db->prepare($sql);
		
		$stmt->bindParam(':title',$title);
		$stmt->bindParam(':content',$content);
		$stmt->bindParam(':addtime',$addtime);
		$stmt->bindParam(':user_id',$userId);
		
		if(!$stmt->execute())
		{
			throw new Exception('文章写入失败', 400);
			die();
		}
		return [
				'article_id'=>$this->db->lastInsertId(),
				'title'=>$title,
		       ];
	}
	function view($article_id)
	{
		if(empty($article_id))
		{
			throw new Exception('文章id不能是空的', 400);
		}
		$sql='select * from `my_article` where `article_id`=:article_id';
		$stmt=$this->db->prepare($sql);
		
		$stmt->bindParam(':article_id',$article_id);
		$stmt->execute();
		$rel=$stmt->fetch(PDO::FETCH_ASSOC);
		if(empty($rel))
		{
			throw new Exception('文章不存在', 400);
			die();
		}
		return $rel;
	}
	function edit($articleId,$title,$content,$userId)
	{
		$art=$this->view($articleId);
		//用户id一致
		if($userId!=$art['user_id'])
		{
			throw new Exception('无权编辑', 400);
			die();
		}
		//title content 为空，使用原来的信息
		$title=empty($title)?$art['title']:$title;
		$content=empty($content)?$art['content']:$content;
		//如果都没有修改，则不进行数据库操作
		if($title===$art['title']&&$content=$art['content'])
		{
			return $art;
		}
		$sql='update `my_article` set `title`=:title,`content`=:content where `article_id`=:articleId';
		$stmt=$this->db->prepare($sql);
		$stmt->bindParam(':title',$title);
		$stmt->bindParam(':content',$content);
		$stmt->bindParam(':articleId',$articleId);
		if(!$stmt->execute())
		{
			throw new Exception('文章更新失败', 400);
			die;
		}
		return [
				'articleId'=>$articleId,
				'title'=>$title,
				'content'=>$content,
				'addtime'=>$art['addtime'],
		];
		
	}
	function delete($articleId,$userId)
	{
		
		if($this->view($articleId)==null)
		{
			throw new Exception('文章不存在', 400);
			die;
		}
		$sql='delete from `my_article` where `article_id`=:article_id and `user_id`=:user_id';
		$stmt=$this->db->prepare($sql);
		$stmt->bindParam(':article_id',$articleId);
		$stmt->bindParam(':user_id',$userId);
		if(false===$stmt->execute())
		{
			throw new Exception('文章删除失败', error::ARTICLE_DEL_FAIL);
			die;
		}
		return true;
	}
	function getList($userId,$page=1,$size=1)
	{
	
		if($size>100)
		{
			throw new Exception('文章获取页数太多', 400);
			die();
		}
		$sql='select * from `my_article` where `user_id`=:user_id limit :limit,:offset';
	
		$limit=($page-1)*$size;
		$limit=$limit<0?0:$limit;
	
		$stmt=$this->db->prepare($sql);
	
		$stmt->bindParam(':user_id',$userId);
		$stmt->bindParam(':limit',$limit);
		$stmt->bindParam(':offset',$size);
	
		$stmt->execute();
	
		$rel=$stmt->fetchAll(PDO::FETCH_ASSOC);
	
		if(empty($rel))
		{
			throw new Exception('获取文章失败', 400);
			die();
		}
		
		return $rel;
	}
}