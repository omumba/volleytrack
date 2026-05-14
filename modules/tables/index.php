<?php
require_once __DIR__.'/../../includes/config.php';
$currentPage='tables'; $pageTitle='League Table'; $db=getDB();
$seasons=$db->query("SELECT * FROM seasons ORDER BY start_date DESC")->fetchAll();
$sid=(int)($_GET['season']??1);
$s=$db->prepare("SELECT ls.*,t.name,t.short_name,t.color_primary,t.logo FROM league_standings ls JOIN teams t ON t.id=ls.team_id WHERE ls.season_id=? ORDER BY ls.points DESC,ls.set_ratio DESC");
$s->execute([$sid]); $standings=$s->fetchAll();
$form=[];
foreach($standings as $st){
  $tid=$st['team_id'];
  $f=$db->prepare("SELECT CASE WHEN m.home_team_id=? AND m.home_sets_won>m.away_sets_won THEN 'W' WHEN m.away_team_id=? AND m.away_sets_won>m.home_sets_won THEN 'W' WHEN m.status='Completed' THEN 'L' END r FROM matches m WHERE (m.home_team_id=? OR m.away_team_id=?) AND m.status='Completed' AND m.season_id=? ORDER BY m.match_date DESC LIMIT 5");
  $f->execute([$tid,$tid,$tid,$tid,$sid]); $form[$tid]=array_column($f->fetchAll(),'r');
}
include __DIR__.'/../../includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <h1 style="font-size:18px;font-weight:600">League Table</h1>
    <div style="display:flex;gap:6px">
      <a href="<?= APP_URL ?>/admin/export_pdf.php?type=standings&season=<?= $sid ?>" class="btn btn-ghost btn-sm" target="_blank"><i class="bi bi-file-earmark-pdf"></i>Export PDF</a>
      <?php if(hasRole('Admin')): ?><a href="<?= APP_URL ?>/admin/recalculate_standings.php" class="btn btn-ghost btn-sm"><i class="bi bi-arrow-clockwise"></i>Recalculate</a><?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px">
    <?php foreach($seasons as $se): ?>
    <a href="?season=<?= $se['id'] ?>" class="btn <?= $se['id']==$sid?'btn-pri':'btn-ghost' ?> btn-sm">
      <?= htmlspecialchars($se['name']) ?>
      <span class="badge <?= $se['status']==='Active'?'b-active':'b-off' ?>" style="font-size:10px"><?= $se['status'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <div style="display:grid;grid-template-columns:1fr 260px;gap:16px">
    <div class="card">
      <div class="card-head"><h3>Standings</h3><span style="font-size:11px;color:var(--t3)">Win=3pts · Loss=0pts</span></div>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th>#</th><th>Team</th><th>MP</th><th>W</th><th>L</th><th>SW</th><th>SL</th><th>SR</th><th>Form</th><th>Pts</th></tr></thead>
          <tbody>
            <?php foreach($standings as $i=>$st):
              $pos=$i+1; $pc=$pos===1?'pos-1':($pos===2?'pos-2':($pos===3?'pos-3':''));
              $sr=$st['sets_lost']>0?number_format($st['sets_won']/$st['sets_lost'],3):($st['sets_won']>0?'∞':'0.000');
              $tf=$form[$st['team_id']]??[];
            ?>
            <tr>
              <td><span class="pos-num <?= $pc ?>"><?= $pos ?></span></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <?php if(!empty($st['logo'])): ?>
                  <img src="<?= APP_URL ?>/assets/uploads/teams/<?= htmlspecialchars($st['logo']) ?>" alt=""
                       style="width:24px;height:24px;border-radius:4px;object-fit:contain;background:#fff;border:1px solid var(--b0);padding:1px;flex-shrink:0">
                  <?php else: ?>
                  <div style="width:24px;height:24px;border-radius:4px;background:<?= $st['color_primary'] ?>;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0"><?= substr($st['short_name'],0,3) ?></div>
                  <?php endif; ?>
                  <span style="font-size:13px;font-weight:600"><?= htmlspecialchars($st['name']) ?></span>
                </div>
              </td>
              <td style="color:var(--t2)"><?= $st['matches_played'] ?></td>
              <td style="color:var(--grn);font-weight:600"><?= $st['matches_won'] ?></td>
              <td style="color:var(--red)"><?= $st['matches_lost'] ?></td>
              <td><?= $st['sets_won'] ?></td>
              <td style="color:var(--t2)"><?= $st['sets_lost'] ?></td>
              <td style="font-size:12px;color:var(--t2)"><?= $sr ?></td>
              <td>
                <div style="display:flex;gap:2px">
                  <?php foreach(array_reverse($tf) as $r): ?>
                  <span style="width:16px;height:16px;border-radius:3px;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;background:<?= $r==='W'?'rgba(63,185,80,.15)':'rgba(248,81,73,.15)' ?>;color:<?= $r==='W'?'var(--grn)':'var(--red)' ?>"><?= $r ?></span>
                  <?php endforeach; ?>
                  <?php for($j=count($tf);$j<5;$j++): ?><span style="width:16px;height:16px;border-radius:3px;background:var(--s3);color:var(--t3);font-size:10px;display:inline-flex;align-items:center;justify-content:center">–</span><?php endfor; ?>
                </div>
              </td>
              <td class="cell-pts"><?= $st['points'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($standings)): ?><tr><td colspan="10" style="text-align:center;padding:30px;color:var(--t3)">No standings data for this season</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="card-foot" style="font-size:11px;color:var(--t3)">MP=Played · W=Won · L=Lost · SW=Sets Won · SL=Sets Lost · SR=Set Ratio</div>
    </div>
    <div style="display:flex;flex-direction:column;gap:14px">
      <?php if(!empty($standings)): ?>
      <div class="card">
        <div class="card-head"><h3>Top Teams</h3></div>
        <?php foreach(array_slice($standings,0,3) as $i=>$st):
          $wr=$st['matches_played']>0?round($st['matches_won']/$st['matches_played']*100):0;
          $mc=['var(--gld)','#a0a0a0','#cd7f32'][$i];
        ?>
        <div style="padding:10px 14px;border-bottom:1px solid var(--b0)">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
            <span style="font-size:16px;font-weight:700;color:<?= $mc ?>"><?= $i+1 ?></span>
            <?php if(!empty($st['logo'])): ?>
            <img src="<?= APP_URL ?>/assets/uploads/teams/<?= htmlspecialchars($st['logo']) ?>" alt="" style="width:20px;height:20px;border-radius:3px;object-fit:contain;background:#fff;border:1px solid var(--b0);flex-shrink:0">
            <?php else: ?>
            <div style="width:20px;height:20px;border-radius:3px;background:<?= $st['color_primary'] ?>;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;flex-shrink:0"><?= substr($st['short_name'],0,3) ?></div>
            <?php endif; ?>
            <div style="flex:1"><div style="font-size:13px;font-weight:600"><?= htmlspecialchars($st['name']) ?></div><div style="font-size:11px;color:var(--t3)"><?= $st['points'] ?> pts · <?= $wr ?>% win rate</div></div>
          </div>
          <div style="height:3px;background:var(--s3);border-radius:2px"><div style="height:100%;width:<?= $wr ?>%;background:<?= $mc ?>;border-radius:2px"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="card">
        <div class="card-head"><h3>Scoring Rules</h3></div>
        <?php foreach([['Win (3-0 or 3-1)','3 pts','var(--grn)'],['Win (3-2)','2 pts','var(--pri)'],['Loss (2-3)','1 pt','var(--gld)'],['Loss (0-3 or 1-3)','0 pts','var(--red)']] as [$r,$pts,$c]): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 14px;border-bottom:1px solid var(--b0)">
          <span style="font-size:12px;color:var(--t2)"><?= $r ?></span>
          <span style="font-size:13px;font-weight:700;color:<?= $c ?>"><?= $pts ?></span>
        </div>
        <?php endforeach; ?>
        <div style="padding:8px 14px;font-size:11px;color:var(--t3)">Tiebreaker: Set Ratio → Point Ratio → Head-to-Head</div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__.'/../../includes/footer.php'; ?>
