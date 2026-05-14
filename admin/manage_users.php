<?php
require_once __DIR__.'/../includes/config.php';
requireLogin();
if(($_SESSION['user_role']??'')<>'Admin'){header('Location:'.APP_URL.'/admin/index.php');exit;}
$currentPage='users'; $pageTitle='Users'; $db=getDB(); $ok=$err='';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $a=$_POST['action']??'';
  if(in_array($a,['create','edit'])){
    $uid=(int)($_POST['uid']??0);$un=trim($_POST['username']??'');$em=trim($_POST['email']??'');
    $fn=trim($_POST['full_name']??'');$ro=$_POST['role']??'Viewer';$pw=$_POST['password']??'';$active=isset($_POST['is_active'])?1:0;
    if(!$un||!$em||!$fn){$err='Name, username and email are required.';}
    elseif(!in_array($ro,['Admin','Referee','Scorer','Viewer'])){$err='Invalid role.';}
    else{
      if($a==='create'){
        if(strlen($pw)<6){$err='Password must be at least 6 characters.';}
        else{
          $chk=$db->prepare("SELECT COUNT(*) FROM users WHERE username=? OR email=?");$chk->execute([$un,$em]);
          if($chk->fetchColumn()>0){$err='Username or email already exists.';}
          else{$db->prepare("INSERT INTO users(username,email,password_hash,full_name,role,is_active)VALUES(?,?,?,?,?,?)")->execute([$un,$em,password_hash($pw,PASSWORD_BCRYPT,['cost'=>12]),$fn,$ro,$active]);$ok="User <strong>$fn</strong> created.";logActivity("Created user: $un",'user',(int)$db->lastInsertId());}
        }
      }else{
        if($uid===(int)$_SESSION['user_id']&&$ro!=='Admin'){$err='Cannot remove Admin from your own account.';}
        else{
          $chk=$db->prepare("SELECT COUNT(*) FROM users WHERE (username=? OR email=?) AND id!=?");$chk->execute([$un,$em,$uid]);
          if($chk->fetchColumn()>0){$err='Username or email already taken.';}
          else{
            if($pw&&strlen($pw)>=6)$db->prepare("UPDATE users SET username=?,email=?,password_hash=?,full_name=?,role=?,is_active=? WHERE id=?")->execute([$un,$em,password_hash($pw,PASSWORD_BCRYPT,['cost'=>12]),$fn,$ro,$active,$uid]);
            else $db->prepare("UPDATE users SET username=?,email=?,full_name=?,role=?,is_active=? WHERE id=?")->execute([$un,$em,$fn,$ro,$active,$uid]);
            $ok="User <strong>$fn</strong> updated.";logActivity("Updated user: $un",'user',$uid);
          }
        }
      }
    }
  }elseif($a==='toggle'){
    $uid=(int)$_POST['uid'];
    if($uid===(int)$_SESSION['user_id']){$err='Cannot deactivate your own account.';}
    else{$db->prepare("UPDATE users SET is_active=NOT is_active WHERE id=?")->execute([$uid]);$ok='Status updated.';}
  }elseif($a==='reset_pw'){
    $uid=(int)$_POST['uid'];$pw=$_POST['new_pw']??'';
    if(strlen($pw)<6){$err='Password must be at least 6 characters.';}
    else{$db->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($pw,PASSWORD_BCRYPT,['cost'=>12]),$uid]);$ok='Password reset.';}
  }elseif($a==='delete'){
    $uid=(int)$_POST['uid'];
    if($uid===(int)$_SESSION['user_id']){$err='Cannot delete your own account.';}
    else{$db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);$ok='User deleted.';}
  }
}

$users=$db->query("SELECT u.*,(SELECT COUNT(*) FROM activity_log al WHERE al.user_id=u.id) ac FROM users u ORDER BY u.role,u.full_name")->fetchAll();
$roles=['Admin','Referee','Scorer','Viewer'];
$rclr=['Admin'=>'var(--acc)','Referee'=>'var(--pri)','Scorer'=>'var(--grn)','Viewer'=>'var(--pur)'];
$ricn=['Admin'=>'bi-shield-fill','Referee'=>'bi-person-check','Scorer'=>'bi-pencil-fill','Viewer'=>'bi-eye-fill'];
$rdsc=['Admin'=>'Full access to all features and settings','Referee'=>'Match management and live scoring','Scorer'=>'Live score entry only','Viewer'=>'Read-only access to public pages'];
include __DIR__.'/../includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <h1 style="font-size:18px;font-weight:600">Users</h1>
    <div style="display:flex;gap:6px">
      <a href="<?=APP_URL?>/admin/export_pdf.php?type=users" class="btn btn-ghost btn-sm" target="_blank"><i class="bi bi-file-earmark-pdf"></i>Export PDF</a>
      <button class="btn btn-pri btn-sm" onclick="openCreate()"><i class="bi bi-person-plus-fill"></i>Create User</button>
    </div>
  </div>

  <?php if($ok): ?><div class="alert a-ok"><i class="bi bi-check-circle-fill"></i><span><?= $ok ?></span></div><?php endif; ?>
  <?php if($err): ?><div class="alert a-err"><i class="bi bi-exclamation-circle-fill"></i><span><?= htmlspecialchars($err) ?></span></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:16px">
    <div style="display:flex;flex-direction:column;gap:8px">
      <?php $cnt=['total'=>0,'Admin'=>0,'Referee'=>0,'Scorer'=>0,'Viewer'=>0,'active'=>0];
      foreach($users as $u){$cnt['total']++;$cnt[$u['role']]++;if($u['is_active'])$cnt['active']++;} ?>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:8px">
        <?php foreach([['Total',$cnt['total']],['Active',$cnt['active']],['Admins',$cnt['Admin']],['Scorers',$cnt['Scorer']]] as [$l,$v]): ?>
        <div class="stat-card"><div class="stat-val" style="font-size:20px"><?= $v ?></div><div class="stat-lbl"><?= $l ?></div></div>
        <?php endforeach; ?>
      </div>

      <?php foreach($users as $u):
        $rc=$rclr[$u['role']]??'var(--t2)';$ri=$ricn[$u['role']]??'bi-person';
        $isSelf=$u['id']==$_SESSION['user_id'];
        $hue=abs(crc32($u['username'])%360);
      ?>
      <div style="background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);<?= !$u['is_active']?'opacity:.5':'' ?>">
        <div style="padding:12px 14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
          <div style="width:36px;height:36px;border-radius:50%;background:hsl(<?= $hue ?>,30%,20%);border:1px solid hsl(<?= $hue ?>,40%,30%);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;color:hsl(<?= $hue ?>,60%,65%);flex-shrink:0"><?= strtoupper(substr($u['full_name'],0,1)) ?></div>
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:3px">
              <span style="font-size:14px;font-weight:600;color:var(--t1)"><?= htmlspecialchars($u['full_name']) ?></span>
              <?php if($isSelf): ?><span class="badge b-active" style="font-size:10px">you</span><?php endif; ?>
              <span class="badge" style="background:color-mix(in srgb,<?= $rc ?> 12%,transparent);color:<?= $rc ?>;border-color:color-mix(in srgb,<?= $rc ?> 25%,transparent);font-size:10px"><i class="bi <?= $ri ?>"></i> <?= $u['role'] ?></span>
              <?php if(!$u['is_active']): ?><span class="badge b-off" style="font-size:10px">inactive</span><?php endif; ?>
            </div>
            <div style="font-size:12px;color:var(--t3);display:flex;gap:12px;flex-wrap:wrap">
              <span><i class="bi bi-at me-1"></i><?= htmlspecialchars($u['username']) ?></span>
              <span><?= htmlspecialchars($u['email']) ?></span>
              <span><i class="bi bi-clock me-1"></i><?= $u['last_login']?date('d M Y H:i',strtotime($u['last_login'])):'Never' ?></span>
              <span><?= number_format($u['ac']) ?> actions</span>
            </div>
          </div>
          <div style="display:flex;gap:5px;flex-wrap:wrap">
            <button class="btn btn-ghost btn-sm btn-icon" onclick='openEdit(<?= htmlspecialchars(json_encode($u)) ?>)' title="Edit"><i class="bi bi-pencil" style="font-size:12px"></i></button>
            <button class="btn btn-ghost btn-sm btn-icon" onclick="openReset(<?= $u['id'] ?>,'<?= htmlspecialchars(addslashes($u['full_name'])) ?>')" title="Reset password"><i class="bi bi-key" style="font-size:12px"></i></button>
            <?php if(!$isSelf): ?>
            <form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="uid" value="<?= $u['id'] ?>"><button type="submit" class="btn btn-ghost btn-sm btn-icon" title="Toggle active"><i class="bi bi-<?= $u['is_active']?'pause':'play' ?>-fill" style="font-size:12px"></i></button></form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="uid" value="<?= $u['id'] ?>"><button type="submit" class="btn btn-danger btn-sm btn-icon"><i class="bi bi-trash" style="font-size:12px"></i></button></form>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div>
      <div class="card" style="position:sticky;top:calc(var(--th) + 14px)">
        <div class="card-head"><h3 id="fTitle">Create User</h3><button class="btn btn-ghost btn-sm btn-icon" onclick="resetForm()"><i class="bi bi-x" style="font-size:14px"></i></button></div>
        <form method="POST" style="padding:14px;display:flex;flex-direction:column;gap:12px">
          <input type="hidden" name="action" id="fAction" value="create">
          <input type="hidden" name="uid" id="fUid" value="">
          <div class="field"><label class="label">Full Name</label><input type="text" name="full_name" id="fName" class="input" required placeholder="Chisomo Phiri"></div>
          <div class="field"><label class="label">Username</label><input type="text" name="username" id="fUser" class="input" required placeholder="chisomo.phiri" autocomplete="off"></div>
          <div class="field"><label class="label">Email</label><input type="email" name="email" id="fEmail" class="input" required placeholder="user@example.com"></div>
          <div class="field">
            <label class="label">Role</label>
            <select name="role" id="fRole" class="select" onchange="showHint()">
              <?php foreach($roles as $r): ?><option value="<?= $r ?>"><?= $r ?></option><?php endforeach; ?>
            </select>
            <div id="roleHint" style="font-size:11px;color:var(--t3);background:var(--s3);border:1px solid var(--b0);border-radius:var(--r);padding:5px 8px;margin-top:4px"></div>
          </div>
          <div class="field">
            <label class="label">Password <span id="pwNote" style="font-weight:400;color:var(--t3)">(required)</span></label>
            <div style="position:relative">
              <input type="password" name="password" id="fPw" class="input" placeholder="Min 6 characters" autocomplete="new-password" style="padding-right:34px">
              <button type="button" onclick="const f=document.getElementById('fPw');f.type=f.type==='password'?'text':'password'" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--t3);cursor:pointer;font-size:13px;padding:0;line-height:1"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:8px;padding:8px;background:var(--s2);border:1px solid var(--b0);border-radius:var(--r);cursor:pointer" onclick="document.getElementById('fActive').click()">
            <input type="checkbox" name="is_active" id="fActive" value="1" checked style="accent-color:var(--grn);cursor:pointer">
            <div><div style="font-size:12px;font-weight:600;color:var(--t1)">Active account</div><div style="font-size:11px;color:var(--t3)">User can sign in</div></div>
          </div>
          <button type="submit" class="btn btn-pri" id="fSubmit" style="justify-content:center"><i class="bi bi-person-plus-fill"></i><span id="fBtnTxt">Create User</span></button>
          <button type="button" class="btn btn-ghost" style="justify-content:center" onclick="resetForm()">Clear</button>
        </form>
        <div style="border-top:1px solid var(--b0)">
          <?php foreach($roles as $r): $c=$rclr[$r]; ?>
          <div style="display:flex;gap:8px;align-items:flex-start;padding:9px 14px;border-bottom:1px solid var(--b0)">
            <i class="bi <?= $ricn[$r] ?>" style="color:<?= $c ?>;font-size:13px;margin-top:1px;flex-shrink:0"></i>
            <div><div style="font-size:12px;font-weight:600;color:<?= $c ?>"><?= $r ?></div><div style="font-size:11px;color:var(--t3);line-height:1.4;margin-top:2px"><?= $rdsc[$r] ?></div></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="resetModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="resetTitle">Reset Password</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST"><div class="modal-body"><input type="hidden" name="action" value="reset_pw"><input type="hidden" name="uid" id="resetUid"><div class="field"><label class="label">New Password</label><input type="password" name="new_pw" class="input" required minlength="6" placeholder="Min 6 characters"></div></div><div class="modal-footer"><button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-pri btn-sm"><i class="bi bi-key"></i>Reset</button></div></form>
</div></div></div>

<script>
const RDSC=<?= json_encode($rdsc) ?>;
function showHint(){document.getElementById('roleHint').textContent=RDSC[document.getElementById('fRole').value]||'';}
function openCreate(){resetForm();document.getElementById('fName').focus();}
function openEdit(u){
  document.getElementById('fTitle').textContent='Edit User';
  document.getElementById('fAction').value='edit';document.getElementById('fUid').value=u.id;
  document.getElementById('fName').value=u.full_name;document.getElementById('fUser').value=u.username;
  document.getElementById('fEmail').value=u.email;document.getElementById('fRole').value=u.role;
  document.getElementById('fActive').checked=u.is_active==1;document.getElementById('fPw').value='';
  document.getElementById('pwNote').textContent='(leave blank to keep)';
  document.getElementById('fBtnTxt').textContent='Save Changes';showHint();
  document.getElementById('fName').scrollIntoView({behavior:'smooth',block:'nearest'});
}
function resetForm(){
  document.getElementById('fTitle').textContent='Create User';document.getElementById('fAction').value='create';document.getElementById('fUid').value='';
  document.getElementById('fName').value='';document.getElementById('fUser').value='';document.getElementById('fEmail').value='';
  document.getElementById('fRole').value='Viewer';document.getElementById('fActive').checked=true;document.getElementById('fPw').value='';
  document.getElementById('pwNote').textContent='(required)';document.getElementById('fBtnTxt').textContent='Create User';showHint();
}
function openReset(id,name){document.getElementById('resetTitle').textContent='Reset: '+name;document.getElementById('resetUid').value=id;new bootstrap.Modal(document.getElementById('resetModal')).show();}
document.addEventListener('DOMContentLoaded',showHint);
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
