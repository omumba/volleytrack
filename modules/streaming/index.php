<?php
require_once __DIR__.'/../../includes/config.php';
$currentPage='streaming'; $pageTitle='Live Stream'; $db=getDB();
$mid=(int)($_GET['match']??0);
$liveMatches=$db->query("SELECT m.id,m.status,m.round,m.match_date,ht.name hn,at.name an,ht.short_name hs,at.short_name as_,ht.color_primary hc,at.color_primary ac,m.home_sets_won,m.away_sets_won,m.home_score_current_set,m.away_score_current_set,(SELECT COUNT(*) FROM stream_cameras sc WHERE sc.match_id=m.id AND sc.status='Live') lc FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.status IN('Live','Scheduled') ORDER BY FIELD(m.status,'Live','Scheduled'),m.match_date LIMIT 10")->fetchAll();
if(!$mid){foreach($liveMatches as $m){if($m['status']==='Live'){$mid=$m['id'];break;}}if(!$mid&&!empty($liveMatches))$mid=$liveMatches[0]['id'];}
$selMatch=null;$cameras=[];
if($mid){
  $s=$db->prepare("SELECT m.*,ht.name hn,at.name an,ht.short_name hs,at.short_name as_,ht.color_primary hc,at.color_primary ac FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.id=?");
  $s->execute([$mid]);$selMatch=$s->fetch();
  $cs=$db->prepare("SELECT * FROM stream_cameras WHERE match_id=? AND is_enabled=1 ORDER BY is_primary DESC,id ASC");
  $cs->execute([$mid]);$cameras=$cs->fetchAll();
}
$selCam=(int)($_GET['cam']??($cameras[0]['id']??0));
$curCam=null;foreach($cameras as $c){if($c['id']===$selCam){$curCam=$c;break;}}
if(!$curCam&&!empty($cameras))$curCam=$cameras[0];
$chatMsgs=$curCam?$db->prepare("SELECT sc.*,u.full_name un FROM stream_chat sc LEFT JOIN users u ON u.id=sc.user_id WHERE sc.camera_id=? AND sc.is_deleted=0 ORDER BY sc.created_at DESC LIMIT 30"):null;
if($chatMsgs){$chatMsgs->execute([$curCam['id']]);$chatMsgs=array_reverse($chatMsgs->fetchAll());}
$cu=currentUser();
include __DIR__.'/../../includes/header.php';
?>
<style>
.stream-layout{display:grid;grid-template-columns:1fr 300px;height:calc(100vh - var(--th));overflow:hidden}
.stream-main{overflow-y:auto;padding:16px;background:var(--bg);display:flex;flex-direction:column;gap:12px}
.stream-chat-col{border-left:1px solid var(--b0);display:flex;flex-direction:column;background:var(--s1)}
@media(max-width:900px){.stream-layout{grid-template-columns:1fr;height:auto}.stream-chat-col{height:360px;border-left:none;border-top:1px solid var(--b0)}}
</style>

<div class="stream-layout">
  <div class="stream-main">
    <!-- Match selector -->
    <?php if(is_array($liveMatches)&&count($liveMatches)>1): ?>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <?php foreach($liveMatches as $m): ?>
      <a href="?match=<?= $m['id'] ?>" class="btn <?= $m['id']==$mid?'btn-pri':'btn-ghost' ?> btn-sm">
        <?php if($m['status']==='Live'): ?><span style="width:6px;height:6px;border-radius:50%;background:var(--red);display:inline-block"></span><?php endif; ?>
        <?= htmlspecialchars($m['hs']) ?> vs <?= htmlspecialchars($m['as_']) ?>
        <?php if($m['lc']>0): ?><span class="badge b-live" style="font-size:9px"><?= $m['lc'] ?> live</span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if(!$selMatch): ?>
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4rem;text-align:center;color:var(--t3)"><i class="bi bi-broadcast" style="font-size:40px;display:block;margin-bottom:12px;opacity:.3"></i><div style="font-size:14px;color:var(--t2)">No live streams available</div><div style="font-size:12px;margin-top:6px">Check back during match times</div></div>
    <?php else: ?>

    <!-- Match info bar -->
    <div style="background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);padding:12px 14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
      <div style="display:flex;align-items:center;gap:12px">
        <span class="badge <?= $selMatch['status']==='Live'?'b-live':'b-sched' ?>"><?= $selMatch['status'] ?></span>
        <div style="font-size:14px;font-weight:600">
          <span style="display:flex;align-items:center;gap:5px"><div class="team-dot" style="background:<?= $selMatch['hc'] ?>"></div><?= htmlspecialchars($selMatch['hn']) ?></span>
        </div>
        <?php if($selMatch['status']==='Live'): ?>
        <div style="font-size:18px;font-weight:700;color:var(--gld)"><?= $selMatch['home_sets_won'] ?><span style="color:var(--t3);font-weight:400;font-size:14px">:</span><?= $selMatch['away_sets_won'] ?></div>
        <div style="font-size:14px;font-weight:600">
          <span style="display:flex;align-items:center;gap:5px"><div class="team-dot" style="background:<?= $selMatch['ac'] ?>"></div><?= htmlspecialchars($selMatch['an']) ?></span>
        </div>
        <?php else: ?>
        <div style="font-size:13px;color:var(--t3)">vs <?= htmlspecialchars($selMatch['an']) ?></div>
        <?php endif; ?>
      </div>
      <a href="<?= APP_URL ?>/modules/scoreboard/index.php?match=<?= $mid ?>" class="btn btn-ghost btn-sm"><i class="bi bi-display"></i>Scoreboard</a>
    </div>

    <!-- Camera tabs -->
    <?php if(is_array($cameras)&&count($cameras)>1): ?>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <?php foreach($cameras as $cam): ?>
      <a href="?match=<?= $mid ?>&cam=<?= $cam['id'] ?>" class="cam-tab <?= $cam['id']==$curCam['id']?'sel':'' ?> <?= $cam['status']==='Offline'?'offline':'' ?>">
        <i class="bi bi-camera-video<?= $cam['status']==='Live'?'-fill':'' ?>" style="color:<?= $cam['status']==='Live'?'var(--red)':'var(--t3)' ?>"></i>
        <?= htmlspecialchars($cam['label']) ?>
        <?php if($cam['status']==='Live'): ?><span class="badge b-live" style="font-size:9px">LIVE</span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Player -->
    <div class="player-wrap">
      <?php if($curCam&&$curCam['status']==='Live'): ?>
        <?php if($curCam['embed_url']&&in_array($curCam['provider'],['YouTube','Facebook','Custom'])): ?>
        <iframe src="<?= htmlspecialchars($curCam['embed_url']) ?>" allowfullscreen allow="autoplay;encrypted-media"></iframe>
        <?php elseif($curCam['hls_url']): ?>
        <video id="hlsPlayer" controls autoplay muted playsinline style="width:100%;height:100%;display:block;background:#000">
          Your browser does not support HTML5 video.
        </video>
        <?php else: ?>
        <div class="player-offline"><i class="bi bi-camera-video-off" style="font-size:48px;margin-bottom:12px;opacity:.4"></i><div style="font-size:14px;color:var(--t2)">Stream source not configured</div></div>
        <?php endif; ?>
        <!-- Score overlay -->
        <?php if($selMatch['status']==='Live'): ?>
        <div class="overlay-top">
          <span class="score-overlay">
            <span style="color:<?= $selMatch['hc'] ?>"><?= htmlspecialchars($selMatch['hs']) ?></span>
            <span style="font-size:16px;font-weight:800"><?= $selMatch['home_sets_won'] ?></span>
            <span style="color:var(--t3)">–</span>
            <span style="font-size:16px;font-weight:800"><?= $selMatch['away_sets_won'] ?></span>
            <span style="color:<?= $selMatch['ac'] ?>"><?= htmlspecialchars($selMatch['as_']) ?></span>
          </span>
          <span class="live-chip" style="pointer-events:none"><span class="live-dot"></span>LIVE</span>
        </div>
        <div class="overlay-bottom">
          <div style="font-size:12px;font-weight:600;color:rgba(255,255,255,.8)">Set <?= $selMatch['current_set'] ?> · <?= $selMatch['home_score_current_set'] ?>–<?= $selMatch['away_score_current_set'] ?></div>
        </div>
        <?php endif; ?>
      <?php elseif($curCam&&$curCam['status']==='Ended'): ?>
      <div class="player-offline"><i class="bi bi-check-circle" style="font-size:48px;margin-bottom:12px;color:var(--grn);opacity:.6"></i><div style="font-size:14px;color:var(--t2)">Stream has ended</div></div>
      <?php else: ?>
      <div class="player-offline">
        <i class="bi bi-broadcast-pin" style="font-size:48px;margin-bottom:12px;opacity:.4"></i>
        <div style="font-size:14px;color:var(--t2);margin-bottom:6px"><?= $curCam?'Camera offline':'No cameras configured' ?></div>
        <div style="font-size:12px;color:var(--t3)">Stream will begin when the match goes live</div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Viewer count -->
    <?php if($curCam&&$curCam['status']==='Live'&&$curCam['viewer_count']): ?>
    <div style="font-size:12px;color:var(--t3)"><i class="bi bi-eye me-1"></i><?= number_format($curCam['viewer_count']) ?> watching</div>
    <?php endif; ?>

    <?php endif; /* $selMatch */ ?>
  </div><!-- /stream-main -->

  <!-- Chat column -->
  <div class="stream-chat-col">
    <div style="padding:10px 14px;border-bottom:1px solid var(--b0);display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
      <span style="font-size:12px;font-weight:600;color:var(--t2)"><i class="bi bi-chat-dots me-1"></i>Live Chat</span>
      <?php if($curCam): ?><span style="font-size:11px;color:var(--t3)" id="chatCount">0 online</span><?php endif; ?>
    </div>
    <div class="chat-msgs" id="chatMsgs">
      <?php if(!$curCam): ?><div style="padding:20px;text-align:center;color:var(--t3);font-size:12px">No camera selected</div>
      <?php elseif(empty($chatMsgs)): ?><div style="padding:20px;text-align:center;color:var(--t3);font-size:12px" id="noChat">No messages yet. Be the first!</div>
      <?php else: foreach($chatMsgs as $msg):
        $name=htmlspecialchars($msg['un']??$msg['guest_name']);
        $initial=strtoupper(substr($name,0,1));
        $hue=abs(crc32($name)%360);
      ?>
      <div class="chat-msg">
        <div class="chat-av" style="background:hsl(<?= $hue ?>,30%,18%);color:hsl(<?= $hue ?>,60%,65%)"><?= $initial ?></div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:baseline;gap:6px"><span class="chat-name"><?= $name ?></span><span class="chat-time"><?= date('H:i',strtotime($msg['created_at'])) ?></span></div>
          <div class="chat-text"><?= htmlspecialchars($msg['message']) ?></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
    <?php if($curCam): ?>
    <div class="chat-input">
      <?php if(!$cu): ?><div style="font-size:12px;color:var(--t3);text-align:center;padding:4px 0"><a href="<?= APP_URL ?>/login.php" style="color:var(--acc)">Sign in</a> to chat or enter a name</div><?php endif; ?>
      <div style="display:flex;gap:6px">
        <?php if(!$cu): ?><input type="text" id="chatName" placeholder="Your name" style="background:var(--s3);border:1px solid var(--b1);color:var(--t1);border-radius:var(--r);padding:6px 8px;font-size:12px;width:90px;outline:none;font-family:inherit"><?php endif; ?>
        <input type="text" id="chatInput" placeholder="Message..." style="flex:1;background:var(--s2);border:1px solid var(--b1);color:var(--t1);border-radius:var(--r);padding:6px 8px;font-size:12px;outline:none;font-family:inherit" onkeydown="if(event.key==='Enter')sendChat()">
        <button onclick="sendChat()" class="btn btn-pri btn-sm btn-icon"><i class="bi bi-send-fill" style="font-size:11px"></i></button>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if($curCam&&$curCam['hls_url']&&$curCam['status']==='Live'): ?>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest/dist/hls.min.js"></script>
<script>
const video=document.getElementById('hlsPlayer');
const src=<?= json_encode($curCam['hls_url']) ?>;
if(Hls.isSupported()){const hls=new Hls();hls.loadSource(src);hls.attachMedia(video);}
else if(video.canPlayType('application/vnd.apple.mpegurl')){video.src=src;}
</script>
<?php endif; ?>

<?php if($curCam): ?>
<script>
const CAM_ID=<?= $curCam['id'] ?>;
const CU_NAME=<?= json_encode($cu?$cu['name']:'') ?>;
let lastId=<?= !empty($chatMsgs)?end($chatMsgs)['id']:0 ?>;

async function sendChat(){
  const inp=document.getElementById('chatInput');
  const msg=inp.value.trim();if(!msg)return;
  const nameEl=document.getElementById('chatName');
  const guest=nameEl?nameEl.value.trim()||'Guest':CU_NAME;
  const d=await api('<?= APP_URL ?>/api/stream_chat.php',{camera_id:CAM_ID,message:msg,guest_name:guest});
  if(d.success){inp.value='';appendMsg(d.message);}
  else toast(d.error||'Error','err');
}

function appendMsg(m){
  document.getElementById('noChat')?.remove();
  const msgs=document.getElementById('chatMsgs');
  const name=m.un||m.guest_name;const initial=name.charAt(0).toUpperCase();
  const hue=Math.abs(hashCode(name)%360);
  const el=document.createElement('div');el.className='chat-msg';
  el.innerHTML=`<div class="chat-av" style="background:hsl(${hue},30%,18%);color:hsl(${hue},60%,65%)">${initial}</div><div style="flex:1;min-width:0"><div style="display:flex;align-items:baseline;gap:6px"><span class="chat-name">${esc(name)}</span><span class="chat-time">${new Date(m.created_at).toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'})}</span></div><div class="chat-text">${esc(m.message)}</div></div>`;
  msgs.appendChild(el);msgs.scrollTop=msgs.scrollHeight;lastId=m.id;
}

function hashCode(s){let h=0;for(let i=0;i<s.length;i++)h=Math.imul(31,h)+s.charCodeAt(i)|0;return h;}
function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

// Poll for new messages every 3s
setInterval(async()=>{
  try{const r=await fetch(`<?= APP_URL ?>/api/stream_chat.php?camera_id=${CAM_ID}&after=${lastId}`);const d=await r.json();if(d.messages)d.messages.forEach(appendMsg);}catch(e){}
},3000);
</script>
<?php endif; ?>

<?php include __DIR__.'/../../includes/footer.php'; ?>
