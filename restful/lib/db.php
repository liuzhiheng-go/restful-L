<?php
$pdo=new PDO('mysql:host=localhost;dbname=mydb', 'root', 'root');
//不会出现int转化为string了，这是预处理问题导致的
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);

return $pdo;