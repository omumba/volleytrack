<?php
require_once __DIR__.'/../includes/config.php';
header('Content-Type: application/json');
$db = getDB();

if($_SERVER['REQUEST_METHOD']==='GET'){
  $cid=(int)($_GET['camera_id']??0);
  $after=(int)($_GET['after']??0);
  if(!$cid) jsonResponse(['messages'=>[]]);
  $s=$db->prepare("SELECT sc.id,sc.guest_name,sc.message,sc.created_at,u.full_name un FROM stream_chat sc LEFT JOIN users u ON u.id=sc.user_id WHERE sc.camera_id=? AND sc.id>? AND sc.is_deleted=0 ORDER BY sc.created_at ASC LIMIT 20");
  $s->execute([$cid,$after]);
  jsonResponse(['messages'=>$s->fetchAll()]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $d=json_decode(file_get_contents('php://input'),true)??[];
  $cid=(int)($d['camera_id']??0);
  $msg=trim($d['message']??'');
  $guest=trim($d['guest_name']??'')?:'Guest';
  if(!$cid||!$msg) jsonResponse(['error'=>'camera_id and message required'],400);
  if(mb_strlen($msg)>300) jsonResponse(['error'=>'Too long'],400);
  $uid=isLoggedIn()?$_SESSION['user_id']:null;
  if($uid) $guest=$_SESSION['user_name'];
  // Store raw message — escaping happens at display time, not storage
  $db->prepare("INSERT INTO stream_chat(camera_id,user_id,guest_name,message)VALUES(?,?,?,?)")->execute([$cid,$uid,$guest,$msg]);
  $nid=$db->lastInsertId();
  $r=$db->prepare("SELECT sc.id,sc.guest_name,sc.message,sc.created_at,u.full_name un FROM stream_chat sc LEFT JOIN users u ON u.id=sc.user_id WHERE sc.id=?");
  $r->execute([$nid]);
  jsonResponse(['success'=>true,'message'=>$r->fetch()]);
}
jsonResponse(['error'=>'Method not allowed'],405);
