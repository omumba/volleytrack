<?php
require_once __DIR__.'/../includes/config.php';

$type   = $_GET['type']   ?? '';

// Standings is public data; everything else is admin-only
if ($type !== 'standings') requireRole('Admin');
$season = (int)($_GET['season'] ?? 1);
$team   = (int)($_GET['team']   ?? 0);

$db = getDB();

// Build content based on type
switch ($type) {
  case 'teams':
    $rows  = $db->query("SELECT t.*,COUNT(p.id) pc FROM teams t LEFT JOIN players p ON p.team_id=t.id AND p.is_active=1 GROUP BY t.id ORDER BY t.is_active DESC,t.name")->fetchAll();
    $title = 'Teams';
    $cols  = ['#','Team Name','Short','City','Home Court','Coach','Players','Status'];
    $body  = '';
    foreach ($rows as $i => $r) {
      $body .= '<tr>'
        .'<td>'.($i+1).'</td>'
        .'<td><strong>'.htmlspecialchars($r['name']).'</strong></td>'
        .'<td>'.htmlspecialchars($r['short_name']).'</td>'
        .'<td>'.htmlspecialchars($r['city'] ?? '—').'</td>'
        .'<td>'.htmlspecialchars($r['home_court'] ?? '—').'</td>'
        .'<td>'.htmlspecialchars($r['coach'] ?? '—').'</td>'
        .'<td style="text-align:center">'.$r['pc'].'</td>'
        .'<td>'.($r['is_active'] ? 'Active' : 'Inactive').'</td>'
        .'</tr>';
    }
    break;

  case 'players':
    $where = $team ? 'WHERE p.team_id=?' : '';
    $params = $team ? [$team] : [];
    $stmt = $db->prepare("SELECT p.*,t.name tn,t.short_name ts FROM players p JOIN teams t ON t.id=p.team_id $where ORDER BY t.name,p.jersey_number");
    $stmt->execute($params); $rows = $stmt->fetchAll();
    $filterLabel = '';
    if ($team) {
      $tRow = $db->prepare("SELECT name FROM teams WHERE id=?"); $tRow->execute([$team]); $tRow = $tRow->fetch();
      $filterLabel = $tRow ? ' — '.htmlspecialchars($tRow['name']) : '';
    }
    $title = 'Players'.$filterLabel;
    $cols  = ['#','Name','Team','#','Position','Height','Weight','DOB','Status'];
    $body  = '';
    foreach ($rows as $i => $r) {
      $body .= '<tr>'
        .'<td>'.($i+1).'</td>'
        .'<td><strong>'.htmlspecialchars($r['first_name'].' '.$r['last_name']).'</strong></td>'
        .'<td>'.htmlspecialchars($r['tn']).'</td>'
        .'<td style="text-align:center">'.$r['jersey_number'].'</td>'
        .'<td>'.htmlspecialchars($r['position']).'</td>'
        .'<td style="text-align:center">'.($r['height_cm'] ? $r['height_cm'].' cm' : '—').'</td>'
        .'<td style="text-align:center">'.($r['weight_kg'] ? $r['weight_kg'].' kg' : '—').'</td>'
        .'<td>'.($r['date_of_birth'] ? date('d M Y', strtotime($r['date_of_birth'])) : '—').'</td>'
        .'<td>'.($r['is_active'] ? 'Active' : 'Inactive').'</td>'
        .'</tr>';
    }
    break;

  case 'fixtures':
    $sRow  = $db->prepare("SELECT name FROM seasons WHERE id=?"); $sRow->execute([$season]); $sRow = $sRow->fetch();
    $title = 'Fixtures — '.htmlspecialchars($sRow['name'] ?? 'Season '.$season);
    $stmt  = $db->prepare("SELECT m.*,ht.name hn,at.name an,s.name sn FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id JOIN seasons s ON s.id=m.season_id WHERE m.season_id=? ORDER BY m.match_date");
    $stmt->execute([$season]); $rows = $stmt->fetchAll();
    $cols  = ['Date','Round','Home Team','Score','Away Team','Venue','Status'];
    $body  = '';
    foreach ($rows as $r) {
      $score = in_array($r['status'], ['Live','Completed']) ? $r['home_sets_won'].':'.$r['away_sets_won'] : '—';
      $body .= '<tr>'
        .'<td>'.date('d M Y H:i', strtotime($r['match_date'])).'</td>'
        .'<td>'.htmlspecialchars($r['round'] ?? '—').'</td>'
        .'<td><strong>'.htmlspecialchars($r['hn']).'</strong></td>'
        .'<td style="text-align:center;font-weight:bold">'.$score.'</td>'
        .'<td><strong>'.htmlspecialchars($r['an']).'</strong></td>'
        .'<td>'.htmlspecialchars($r['venue'] ?? '—').'</td>'
        .'<td>'.$r['status'].'</td>'
        .'</tr>';
    }
    break;

  case 'standings':
    $sRow  = $db->prepare("SELECT name FROM seasons WHERE id=?"); $sRow->execute([$season]); $sRow = $sRow->fetch();
    $title = 'League Standings — '.htmlspecialchars($sRow['name'] ?? 'Season '.$season);
    $rows  = $db->prepare("SELECT ls.*,t.name,t.short_name FROM league_standings ls JOIN teams t ON t.id=ls.team_id WHERE ls.season_id=? ORDER BY ls.points DESC,ls.set_ratio DESC");
    $rows->execute([$season]); $rows = $rows->fetchAll();
    $cols  = ['Pos','Team','MP','W','L','Sets W','Sets L','Pts'];
    $body  = '';
    foreach ($rows as $i => $r) {
      $body .= '<tr>'
        .'<td style="text-align:center;font-weight:bold">'.($i+1).'</td>'
        .'<td><strong>'.htmlspecialchars($r['name']).'</strong></td>'
        .'<td style="text-align:center">'.$r['matches_played'].'</td>'
        .'<td style="text-align:center">'.$r['matches_won'].'</td>'
        .'<td style="text-align:center">'.$r['matches_lost'].'</td>'
        .'<td style="text-align:center">'.$r['sets_won'].'</td>'
        .'<td style="text-align:center">'.$r['sets_lost'].'</td>'
        .'<td style="text-align:center;font-weight:bold;color:#1a56db">'.$r['points'].'</td>'
        .'</tr>';
    }
    break;

  case 'users':
    $title = 'Users';
    $rows  = $db->query("SELECT u.*,(SELECT COUNT(*) FROM activity_log al WHERE al.user_id=u.id) ac FROM users u ORDER BY u.role,u.full_name")->fetchAll();
    $cols  = ['#','Full Name','Username','Email','Role','Last Login','Actions','Status'];
    $body  = '';
    foreach ($rows as $i => $r) {
      $body .= '<tr>'
        .'<td>'.($i+1).'</td>'
        .'<td><strong>'.htmlspecialchars($r['full_name']).'</strong></td>'
        .'<td>'.htmlspecialchars($r['username']).'</td>'
        .'<td>'.htmlspecialchars($r['email']).'</td>'
        .'<td>'.$r['role'].'</td>'
        .'<td>'.($r['last_login'] ? date('d M Y', strtotime($r['last_login'])) : 'Never').'</td>'
        .'<td style="text-align:center">'.$r['ac'].'</td>'
        .'<td>'.($r['is_active'] ? 'Active' : 'Inactive').'</td>'
        .'</tr>';
    }
    break;

  case 'seasons':
    $title = 'Seasons';
    $rows  = $db->query("SELECT s.*,(SELECT COUNT(*) FROM matches m WHERE m.season_id=s.id) mc,(SELECT COUNT(*) FROM league_standings ls WHERE ls.season_id=s.id) tc FROM seasons s ORDER BY s.start_date DESC")->fetchAll();
    $cols  = ['#','Season Name','Type','Start Date','End Date','Status','Teams','Matches'];
    $body  = '';
    foreach ($rows as $i => $r) {
      $body .= '<tr>'
        .'<td>'.($i+1).'</td>'
        .'<td><strong>'.htmlspecialchars($r['name']).'</strong></td>'
        .'<td>'.$r['type'].'</td>'
        .'<td>'.date('d M Y', strtotime($r['start_date'])).'</td>'
        .'<td>'.($r['end_date'] ? date('d M Y', strtotime($r['end_date'])) : '—').'</td>'
        .'<td>'.$r['status'].'</td>'
        .'<td style="text-align:center">'.$r['tc'].'</td>'
        .'<td style="text-align:center">'.$r['mc'].'</td>'
        .'</tr>';
    }
    break;

  case 'news':
    $title = 'News Articles';
    $rows  = $db->query("SELECT na.*,u.full_name an FROM news_articles na LEFT JOIN users u ON u.id=na.author_id ORDER BY na.created_at DESC")->fetchAll();
    $cols  = ['#','Title','Category','Author','Status','Views','Published'];
    $body  = '';
    foreach ($rows as $i => $r) {
      $body .= '<tr>'
        .'<td>'.($i+1).'</td>'
        .'<td style="max-width:220px">'.htmlspecialchars(mb_strimwidth($r['title'], 0, 70, '…')).'</td>'
        .'<td>'.$r['category'].'</td>'
        .'<td>'.htmlspecialchars($r['an'] ?? 'Staff').'</td>'
        .'<td>'.$r['status'].'</td>'
        .'<td style="text-align:center">'.number_format($r['views']).'</td>'
        .'<td>'.($r['published_at'] ? date('d M Y', strtotime($r['published_at'])) : '—').'</td>'
        .'</tr>';
    }
    break;

  default:
    http_response_code(400); echo 'Unknown export type.'; exit;
}

// Build header row
$headerCells = '';
foreach ($cols as $c) $headerCells .= '<th>'.htmlspecialchars($c).'</th>';

$generatedAt = date('d M Y, H:i');
$appName     = APP_NAME;
$rowCount    = count($rows);

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body        { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10pt; color: #111; margin: 0; }
  h1          { font-size: 15pt; font-weight: bold; margin: 0 0 2px; color: #1a1a1a; }
  .subtitle   { font-size: 9pt; color: #666; margin-bottom: 14px; }
  table       { width: 100%; border-collapse: collapse; font-size: 9pt; }
  th          { background: #1a56db; color: #fff; padding: 6px 8px; text-align: left; font-weight: 600; white-space: nowrap; }
  td          { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
  tr:nth-child(even) td { background: #f9fafb; }
  tr:last-child td      { border-bottom: none; }
  .footer     { font-size: 8pt; color: #999; margin-top: 14px; border-top: 1px solid #e5e7eb; padding-top: 6px; display: flex; justify-content: space-between; }
</style>
</head>
<body>
  <h1>{$appName} — {$title}</h1>
  <div class="subtitle">Generated: {$generatedAt} &nbsp;·&nbsp; {$rowCount} record(s)</div>
  <table>
    <thead><tr>{$headerCells}</tr></thead>
    <tbody>{$body}</tbody>
  </table>
  <div class="footer">
    <span>{$appName}</span>
    <span>Generated {$generatedAt}</span>
  </div>
</body>
</html>
HTML;

// Require Composer autoload
$autoload = __DIR__.'/../vendor/autoload.php';
if (!file_exists($autoload)) {
  http_response_code(500);
  echo '<p>mPDF not installed. Run <code>composer require mpdf/mpdf</code> in the project root.</p>';
  exit;
}
require_once $autoload;

$filename = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title)).'-'.date('Ymd').'.pdf';

$mpdf = new \Mpdf\Mpdf([
  'mode'          => 'utf-8',
  'format'        => 'A4',
  'orientation'   => ($type === 'players' || $type === 'fixtures') ? 'L' : 'P',
  'margin_top'    => 14,
  'margin_bottom' => 14,
  'margin_left'   => 14,
  'margin_right'  => 14,
]);
$mpdf->SetTitle($appName.' — '.$title);
$mpdf->WriteHTML($html);
$mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
