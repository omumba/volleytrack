<?php
require_once __DIR__.'/includes/config.php';
if(isLoggedIn()){header('Location:'.APP_URL.'/index.php');exit;}
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $u=trim($_POST['username']??'');$p=$_POST['password']??'';
  if($u&&$p){
    $db=getDB();
    $s=$db->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND is_active=1");
    $s->execute([$u,$u]);$usr=$s->fetch();
    if($usr&&password_verify($p,$usr['password_hash'])){
      $_SESSION['user_id']=$usr['id'];$_SESSION['user_name']=$usr['full_name'];$_SESSION['user_role']=$usr['role'];
      $db->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$usr['id']]);
      logActivity("Login: {$usr['username']}");
      // Redirect based on role
      $dest = match($usr['role']) {
        'Admin','Referee' => APP_URL.'/admin/index.php',
        'Scorer'          => APP_URL.'/admin/score_entry.php',
        default           => APP_URL.'/index.php',
      };
      header('Location:'.$dest); exit;
    }
    $err='Invalid username or password.';
  } else $err='Enter username and password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign In — VolleyTrack</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet">
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.lbox{width:100%;max-width:360px}
.lcard{background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);padding:28px}
</style>
</head>
<body>
<div class="lbox">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:24px">
    <div style="width:32px;height:32px;border-radius:6px;background:var(--acc);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;flex-shrink:0">
      <i class="bi bi-trophy-fill"></i>
    </div>
    <div>
      <div style="font-size:15px;font-weight:700;color:var(--t1)">VolleyTrack</div>
      <div style="font-size:11px;color:var(--t3)">Malawi Volleyball</div>
    </div>
  </div>

  <div class="lcard">
    <h2 style="font-size:16px;margin-bottom:20px">Sign in</h2>
    <?php if($err): ?>
    <div class="alert a-err" style="margin-bottom:16px"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div style="display:flex;flex-direction:column;gap:14px">
        <div class="field">
          <label class="label">Username or Email</label>
          <input type="text" name="username" class="input" placeholder="admin" value="<?= htmlspecialchars($_POST['username']??'') ?>" required autofocus>
        </div>
        <div class="field">
          <label class="label">Password</label>
          <div style="position:relative">
            <input type="password" name="password" class="input" placeholder="password" required id="pwf" style="padding-right:34px">
            <button type="button" onclick="const f=document.getElementById('pwf');f.type=f.type==='password'?'text':'password'" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--t3);cursor:pointer;font-size:13px;padding:0;line-height:1">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-pri" style="width:100%;justify-content:center;padding:8px">
          Sign in <i class="bi bi-arrow-right"></i>
        </button>
      </div>
    </form>
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--b0);font-size:12px;color:var(--t3)">
      Default: <span style="color:var(--t2)">admin</span> / <span style="color:var(--t2)">password</span>
    </div>
  </div>

  <div style="margin-top:14px;text-align:center">
    <a href="<?= APP_URL ?>/index.php" style="font-size:12px;color:var(--t3);text-decoration:none">
      <i class="bi bi-arrow-left me-1"></i>Back to site
    </a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
