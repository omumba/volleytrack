<?php
require_once __DIR__.'/../../includes/config.php';
$currentPage='streaming'; $pageTitle='Stream'; $db=getDB();

$tab = $_GET['tab'] ?? 'live';  // 'live' | 'replays'
$mid = (int)($_GET['match'] ?? 0);

// Live / Scheduled matches with at least one camera
$liveMatchList = $db->query("SELECT m.id,m.status,m.round,m.match_date,ht.name hn,at.name an,ht.short_name hs,at.short_name as_,ht.color_primary hc,at.color_primary ac,m.home_sets_won,m.away_sets_won,m.home_score_current_set,m.away_score_current_set,(SELECT COUNT(*) FROM stream_cameras sc WHERE sc.match_id=m.id AND sc.status='Live') lc FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.status IN('Live','Scheduled') ORDER BY FIELD(m.status,'Live','Scheduled'),m.match_date LIMIT 10")->fetchAll();

// Completed matches that have at least one recording
$replayMatches = $db->query("SELECT m.id,m.status,m.round,m.match_date,ht.name hn,at.name an,ht.short_name hs,at.short_name as_,ht.color_primary hc,at.color_primary ac,m.home_sets_won,m.away_sets_won FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.status='Completed' AND EXISTS(SELECT 1 FROM stream_cameras sc WHERE sc.match_id=m.id AND sc.recording_url IS NOT NULL AND sc.recording_url<>'') ORDER BY m.match_date DESC LIMIT 20")->fetchAll();

// Auto-select a match
if (!$mid) {
  if ($tab === 'replays') {
    if (!empty($replayMatches)) $mid = $replayMatches[0]['id'];
  } else {
    foreach ($liveMatchList as $m) { if ($m['status']==='Live') { $mid=$m['id']; break; } }
    if (!$mid && !empty($liveMatchList)) $mid = $liveMatchList[0]['id'];
  }
}

$selMatch = null; $cameras = [];
if ($mid) {
  $s = $db->prepare("SELECT m.*,ht.name hn,at.name an,ht.short_name hs,at.short_name as_,ht.color_primary hc,at.color_primary ac FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.id=?");
  $s->execute([$mid]); $selMatch = $s->fetch();

  if ($tab === 'replays') {
    // Only cameras that have a recording URL
    $cs = $db->prepare("SELECT * FROM stream_cameras WHERE match_id=? AND recording_url IS NOT NULL AND recording_url<>'' AND is_enabled=1 ORDER BY is_primary DESC,id ASC");
  } else {
    $cs = $db->prepare("SELECT * FROM stream_cameras WHERE match_id=? AND is_enabled=1 ORDER BY is_primary DESC,id ASC");
  }
  $cs->execute([$mid]); $cameras = $cs->fetchAll();
}

$selCam = (int)($_GET['cam'] ?? ($cameras[0]['id'] ?? 0));
$curCam = null;
foreach ($cameras as $c) { if ($c['id'] === $selCam) { $curCam = $c; break; } }
if (!$curCam && !empty($cameras)) $curCam = $cameras[0];

$chatMsgs = $curCam ? $db->prepare("SELECT sc.*,u.full_name un FROM stream_chat sc LEFT JOIN users u ON u.id=sc.user_id WHERE sc.camera_id=? AND sc.is_deleted=0 ORDER BY sc.created_at DESC LIMIT 30") : null;
if ($chatMsgs) { $chatMsgs->execute([$curCam['id']]); $chatMsgs = array_reverse($chatMsgs->fetchAll()); }
$cu = currentUser();

// Detect recording type
function recordingType(string $url): string {
  if (preg_match('/youtube\.com|youtu\.be/i', $url)) return 'embed';
  if (preg_match('/facebook\.com/i', $url))           return 'embed';
  if (preg_match('/\.(mp4|webm|ogg)(\?|$)/i', $url)) return 'video';
  if (preg_match('/\.m3u8(\?|$)/i', $url))            return 'hls';
  return 'embed'; // treat unknown URLs as embed iframes
}

include __DIR__.'/../../includes/header.php';
?>
<style>
.stream-layout{display:grid;grid-template-columns:1fr 300px;height:calc(100vh - var(--th));overflow:hidden}
.stream-main{overflow-y:auto;padding:16px;background:var(--bg);display:flex;flex-direction:column;gap:12px}
.stream-chat-col{border-left:1px solid var(--b0);display:flex;flex-direction:column;background:var(--s1)}
@media(max-width:900px){.stream-layout{grid-template-columns:1fr;height:auto}.stream-chat-col{height:360px;border-left:none;border-top:1px solid var(--b0)}}
.replay-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(139,92,246,.15);color:#8b5cf6;border:1px solid rgba(139,92,246,.25)}
</style>

<div class="stream-layout">
  <div class="stream-main">

    <!-- Tab switcher -->
    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
      <a href="?tab=live<?= $tab==='live'&&$mid?'&match='.$mid:'' ?>" class="btn <?= $tab!=='replays'?'btn-pri':'btn-ghost' ?> btn-sm"><i class="bi bi-broadcast"></i>Live & Upcoming</a>
      <a href="?tab=replays<?= $tab==='replays'&&$mid?'&match='.$mid:'' ?>" class="btn <?= $tab==='replays'?'btn-pri':'btn-ghost' ?> btn-sm">
        <i class="bi bi-play-circle"></i>Replays
        <?php if(!empty($replayMatches)): ?><span class="badge b-sched" style="font-size:9px"><?= count($replayMatches) ?></span><?php endif; ?>
      </a>
    </div>

    <?php if ($tab === 'replays'): ?>

      <!-- Replay match selector -->
      <?php if (empty($replayMatches)): ?>
      <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4rem;text-align:center;color:var(--t3)">
        <i class="bi bi-play-circle" style="font-size:40px;display:block;margin-bottom:12px;opacity:.3"></i>
        <div style="font-size:14px;color:var(--t2)">No recordings available yet</div>
        <div style="font-size:12px;margin-top:6px">Recordings appear here once admins add a VOD link to a completed match</div>
      </div>
      <?php else: ?>

      <?php if (count($replayMatches) > 1): ?>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <?php foreach ($replayMatches as $m): ?>
        <a href="?tab=replays&match=<?= $m['id'] ?>" class="btn <?= $m['id']==$mid?'btn-pri':'btn-ghost' ?> btn-sm">
          <?= htmlspecialchars($m['hs']) ?> <?= $m['home_sets_won'] ?>–<?= $m['away_sets_won'] ?> <?= htmlspecialchars($m['as_']) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($selMatch): ?>
      <!-- Match info bar -->
      <div style="background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);padding:12px 14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div style="display:flex;align-items:center;gap:12px">
          <span class="replay-badge"><i class="bi bi-play-fill"></i>Replay</span>
          <div style="font-size:14px;font-weight:600;display:flex;align-items:center;gap:5px"><div class="team-dot" style="background:<?= $selMatch['hc'] ?>"></div><?= htmlspecialchars($selMatch['hn']) ?></div>
          <div style="font-size:18px;font-weight:700;color:var(--gld)"><?= $selMatch['home_sets_won'] ?><span style="color:var(--t3);font-weight:400;font-size:14px">:</span><?= $selMatch['away_sets_won'] ?></div>
          <div style="font-size:14px;font-weight:600;display:flex;align-items:center;gap:5px"><div class="team-dot" style="background:<?= $selMatch['ac'] ?>"></div><?= htmlspecialchars($selMatch['an']) ?></div>
        </div>
        <div style="font-size:12px;color:var(--t3)"><?= date('d M Y', strtotime($selMatch['match_date'])) ?><?= $selMatch['round'] ? ' · '.htmlspecialchars($selMatch['round']) : '' ?></div>
      </div>

      <!-- Camera tabs for replays -->
      <?php if (count($cameras) > 1): ?>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <?php foreach ($cameras as $cam): ?>
        <a href="?tab=replays&match=<?= $mid ?>&cam=<?= $cam['id'] ?>" class="cam-tab <?= $cam['id']==$curCam['id']?'sel':'' ?>">
          <i class="bi bi-camera-video" style="color:var(--t3)"></i>
          <?= htmlspecialchars($cam['label']) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Replay player -->
      <div class="player-wrap">
        <?php if ($curCam && $curCam['recording_url']):
          $rType = recordingType($curCam['recording_url']);
          if ($rType === 'embed'): ?>
        <iframe src="<?= htmlspecialchars($curCam['recording_url']) ?>" allowfullscreen allow="autoplay;encrypted-media"></iframe>
        <?php elseif ($rType === 'hls'): ?>
        <video id="recPlayer" controls playsinline style="width:100%;height:100%;display:block;background:#000">
          Your browser does not support HTML5 video.
        </video>
        <?php else: /* direct video */ ?>
        <video controls playsinline style="width:100%;height:100%;display:block;background:#000">
          <source src="<?= htmlspecialchars($curCam['recording_url']) ?>">
          Your browser does not support HTML5 video.
        </video>
        <?php endif; ?>
        <!-- Final score overlay -->
        <div class="overlay-top" style="pointer-events:none">
          <span class="score-overlay">
            <span style="color:<?= $selMatch['hc'] ?>"><?= htmlspecialchars($selMatch['hs']) ?></span>
            <span style="font-size:16px;font-weight:800"><?= $selMatch['home_sets_won'] ?></span>
            <span style="color:var(--t3)">–</span>
            <span style="font-size:16px;font-weight:800"><?= $selMatch['away_sets_won'] ?></span>
            <span style="color:<?= $selMatch['ac'] ?>"><?= htmlspecialchars($selMatch['as_']) ?></span>
          </span>
          <span class="replay-badge" style="background:rgba(0,0,0,.55);border-color:rgba(255,255,255,.15);color:#fff"><i class="bi bi-play-fill"></i>REPLAY</span>
        </div>
        <?php else: ?>
        <div class="player-offline"><i class="bi bi-play-circle" style="font-size:48px;margin-bottom:12px;opacity:.4"></i><div style="font-size:14px;color:var(--t2)">No recording for this camera</div></div>
        <?php endif; ?>
      </div>

      <?php endif; /* selMatch */ ?>
      <?php endif; /* replayMatches not empty */ ?>

    <?php else: /* LIVE TAB */ ?>

    <!-- Live match selector -->
    <?php if (count($liveMatchList) > 1): ?>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <?php foreach ($liveMatchList as $m): ?>
      <a href="?match=<?= $m['id'] ?>" class="btn <?= $m['id']==$mid?'btn-pri':'btn-ghost' ?> btn-sm">
        <?php if($m['status']==='Live'): ?><span style="width:6px;height:6px;border-radius:50%;background:var(--red);display:inline-block"></span><?php endif; ?>
        <?= htmlspecialchars($m['hs']) ?> vs <?= htmlspecialchars($m['as_']) ?>
        <?php if($m['lc']>0): ?><span class="badge b-live" style="font-size:9px"><?= $m['lc'] ?> live</span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$selMatch): ?>
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:4rem;text-align:center;color:var(--t3)">
      <i class="bi bi-broadcast" style="font-size:40px;display:block;margin-bottom:12px;opacity:.3"></i>
      <div style="font-size:14px;color:var(--t2)">No live streams available</div>
      <div style="font-size:12px;margin-top:6px">Check back during match times<?= !empty($replayMatches) ? ' · <a href="?tab=replays" style="color:var(--acc)">Watch replays</a>' : '' ?></div>
    </div>
    <?php else: ?>

    <!-- Match info bar -->
    <div style="background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);padding:12px 14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
      <div style="display:flex;align-items:center;gap:12px">
        <span class="badge <?= $selMatch['status']==='Live'?'b-live':'b-sched' ?>"><?= $selMatch['status'] ?></span>
        <div style="font-size:14px;font-weight:600;display:flex;align-items:center;gap:5px"><div class="team-dot" style="background:<?= $selMatch['hc'] ?>"></div><?= htmlspecialchars($selMatch['hn']) ?></div>
        <?php if($selMatch['status']==='Live'): ?>
        <div style="font-size:18px;font-weight:700;color:var(--gld)"><?= $selMatch['home_sets_won'] ?><span style="color:var(--t3);font-weight:400;font-size:14px">:</span><?= $selMatch['away_sets_won'] ?></div>
        <div style="font-size:14px;font-weight:600;display:flex;align-items:center;gap:5px"><div class="team-dot" style="background:<?= $selMatch['ac'] ?>"></div><?= htmlspecialchars($selMatch['an']) ?></div>
        <?php else: ?>
        <div style="font-size:13px;color:var(--t3)">vs <?= htmlspecialchars($selMatch['an']) ?></div>
        <?php endif; ?>
      </div>
      <a href="<?= APP_URL ?>/modules/scoreboard/index.php?match=<?= $mid ?>" class="btn btn-ghost btn-sm"><i class="bi bi-display"></i>Scoreboard</a>
    </div>

    <!-- Camera tabs -->
    <?php if (count($cameras) > 1): ?>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <?php foreach ($cameras as $cam): ?>
      <a href="?match=<?= $mid ?>&cam=<?= $cam['id'] ?>" class="cam-tab <?= $cam['id']==$curCam['id']?'sel':'' ?> <?= $cam['status']==='Offline'?'offline':'' ?>">
        <i class="bi bi-camera-video<?= $cam['status']==='Live'?'-fill':'' ?>" style="color:<?= $cam['status']==='Live'?'var(--red)':'var(--t3)' ?>"></i>
        <?= htmlspecialchars($cam['label']) ?>
        <?php if($cam['status']==='Live'): ?><span class="badge b-live" style="font-size:9px">LIVE</span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Live player -->
    <div class="player-wrap">
      <?php if ($curCam && $curCam['status']==='Live'): ?>
        <?php if ($curCam['embed_url'] && in_array($curCam['provider'],['YouTube','Facebook','Custom'])): ?>
        <iframe src="<?= htmlspecialchars($curCam['embed_url']) ?>" allowfullscreen allow="autoplay;encrypted-media"></iframe>
        <?php elseif ($curCam['hls_url']): ?>
        <video id="hlsPlayer" controls autoplay muted playsinline style="width:100%;height:100%;display:block;background:#000">
          Your browser does not support HTML5 video.
        </video>
        <?php else: ?>
        <div class="player-offline"><i class="bi bi-camera-video-off" style="font-size:48px;margin-bottom:12px;opacity:.4"></i><div style="font-size:14px;color:var(--t2)">Stream source not configured</div></div>
        <?php endif; ?>
        <?php if ($selMatch['status']==='Live'): ?>
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
      <?php elseif ($curCam && $curCam['status']==='Ended'): ?>
      <div class="player-offline">
        <i class="bi bi-check-circle" style="font-size:48px;margin-bottom:12px;color:var(--grn);opacity:.6"></i>
        <div style="font-size:14px;color:var(--t2)">Stream has ended</div>
        <?php if (!empty($replayMatches)): ?><div style="margin-top:10px"><a href="?tab=replays&match=<?= $mid ?>" class="btn btn-ghost btn-sm"><i class="bi bi-play-circle"></i>Watch Replay</a></div><?php endif; ?>
      </div>
      <?php else: ?>
      <div class="player-offline">
        <i class="bi bi-broadcast-pin" style="font-size:48px;margin-bottom:12px;opacity:.4"></i>
        <div style="font-size:14px;color:var(--t2);margin-bottom:6px"><?= $curCam ? 'Camera offline' : 'No cameras configured' ?></div>
        <div style="font-size:12px;color:var(--t3)">Stream will begin when the match goes live</div>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($curCam && $curCam['status']==='Live' && $curCam['viewer_count']): ?>
    <div style="font-size:12px;color:var(--t3)"><i class="bi bi-eye me-1"></i><?= number_format($curCam['viewer_count']) ?> watching</div>
    <?php endif; ?>

    <?php endif; /* selMatch */ ?>
    <?php endif; /* tab */ ?>

  </div><!-- /stream-main -->

  <!-- Chat column -->
  <div class="stream-chat-col">
    <div style="padding:10px 14px;border-bottom:1px solid var(--b0);display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
      <span style="font-size:12px;font-weight:600;color:var(--t2)"><i class="bi bi-chat-dots me-1"></i><?= $tab==='replays' ? 'Match Chat' : 'Live Chat' ?></span>
      <?php if ($curCam): ?><span style="font-size:11px;color:var(--t3)" id="chatCount">0 online</span><?php endif; ?>
    </div>
    <div class="chat-msgs" id="chatMsgs">
      <?php if (!$curCam): ?><div style="padding:20px;text-align:center;color:var(--t3);font-size:12px">No camera selected</div>
      <?php elseif (empty($chatMsgs)): ?><div style="padding:20px;text-align:center;color:var(--t3);font-size:12px" id="noChat">No messages yet. Be the first!</div>
      <?php else: foreach ($chatMsgs as $msg):
        $name = htmlspecialchars($msg['un'] ?? $msg['guest_name']);
        $initial = strtoupper(substr($name, 0, 1));
        $hue = abs(crc32($name) % 360);
      ?>
      <div class="chat-msg">
        <div class="chat-av" style="background:hsl(<?= $hue ?>,30%,18%);color:hsl(<?= $hue ?>,60%,65%)"><?= $initial ?></div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:baseline;gap:6px"><span class="chat-name"><?= $name ?></span><span class="chat-time"><?= date('H:i', strtotime($msg['created_at'])) ?></span></div>
          <div class="chat-text"><?= htmlspecialchars($msg['message']) ?></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
    <?php if ($curCam): ?>
    <div class="chat-input">
      <?php if (!$cu): ?><div style="font-size:12px;color:var(--t3);text-align:center;padding:4px 0"><a href="<?= APP_URL ?>/login.php" style="color:var(--acc)">Sign in</a> to chat or enter a name</div><?php endif; ?>
      <div style="display:flex;gap:6px">
        <?php if (!$cu): ?><input type="text" id="chatName" placeholder="Your name" style="background:var(--s3);border:1px solid var(--b1);color:var(--t1);border-radius:var(--r);padding:6px 8px;font-size:12px;width:90px;outline:none;font-family:inherit"><?php endif; ?>
        <input type="text" id="chatInput" placeholder="Message..." style="flex:1;background:var(--s2);border:1px solid var(--b1);color:var(--t1);border-radius:var(--r);padding:6px 8px;font-size:12px;outline:none;font-family:inherit" onkeydown="if(event.key==='Enter')sendChat()">
        <button onclick="sendChat()" class="btn btn-pri btn-sm btn-icon"><i class="bi bi-send-fill" style="font-size:11px"></i></button>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($tab !== 'replays' && $curCam && $curCam['hls_url'] && $curCam['status']==='Live'): ?>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest/dist/hls.min.js"></script>
<script>
const video=document.getElementById('hlsPlayer');
const src=<?= json_encode($curCam['hls_url']) ?>;
if(Hls.isSupported()){const hls=new Hls();hls.loadSource(src);hls.attachMedia(video);}
else if(video.canPlayType('application/vnd.apple.mpegurl')){video.src=src;}
</script>
<?php endif; ?>

<?php if ($tab === 'replays' && $curCam && !empty($curCam['recording_url']) && recordingType($curCam['recording_url'])==='hls'): ?>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest/dist/hls.min.js"></script>
<script>
const video=document.getElementById('recPlayer');
const src=<?= json_encode($curCam['recording_url']) ?>;
if(Hls.isSupported()){const hls=new Hls();hls.loadSource(src);hls.attachMedia(video);}
else if(video.canPlayType('application/vnd.apple.mpegurl')){video.src=src;}
</script>
<?php endif; ?>

<?php if ($curCam): ?>
<script>
const CAM_ID=<?= $curCam['id'] ?>;
const CU_NAME=<?= json_encode($cu ? $cu['name'] : '') ?>;
const IS_REPLAY=<?= $tab==='replays' ? 'true' : 'false' ?>;
let lastId=<?= !empty($chatMsgs) ? end($chatMsgs)['id'] : 0 ?>;

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

// Only poll for new chat on live streams
if(!IS_REPLAY){
  setInterval(async()=>{
    try{const r=await fetch(`<?= APP_URL ?>/api/stream_chat.php?camera_id=${CAM_ID}&after=${lastId}`);const d=await r.json();if(d.messages)d.messages.forEach(appendMsg);}catch(e){}
  },3000);
}
</script>
<?php endif; ?>

<?php include __DIR__.'/../../includes/footer.php'; ?>
