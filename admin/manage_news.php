<?php
require_once __DIR__.'/../includes/config.php';
requireRole('Admin'); $currentPage='manage_news'; $pageTitle='News'; $db=getDB(); $ok=$err='';
$view=$_GET['view']??'list'; $eid=(int)($_GET['id']??0);

function mslug(string $t, PDO $db, int $excludeId=0): string {
  $s = trim(preg_replace('/[\s-]+/','-',preg_replace('/[^a-z0-9\s-]/','',strtolower($t))),'-');
  $base = $s; $i = 1;
  $chk = $db->prepare("SELECT COUNT(*) FROM news_articles WHERE slug=? AND id!=?");
  while(true){ $chk->execute([$s,$excludeId]); if(!$chk->fetchColumn()) break; $s=$base.'-'.$i++; }
  return $s;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $a=$_POST['action']??'';
  if(in_array($a,['save','publish','update'])){
    $aid=(int)($_POST['article_id']??0); $title=trim($_POST['title']??''); $summary=trim($_POST['summary']??''); $content=trim($_POST['content']??''); $cat=$_POST['category']??'General'; $tags=trim($_POST['tags']??''); $status=$a==='publish'?'Published':($_POST['status']??'Draft'); $feat=isset($_POST['is_featured'])?1:0;
    if(!$title||!$content){$err='Title and content required.';}
    else{
      $img=null;
      if(!empty($_FILES['featured_image']['name'])&&$_FILES['featured_image']['error']===UPLOAD_ERR_OK){
        $mimes=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        $mime=mime_content_type($_FILES['featured_image']['tmp_name']);
        if(isset($mimes[$mime])&&$_FILES['featured_image']['size']<=5242880){
          $newsDir=__DIR__.'/../assets/uploads/news/';
          if(!is_dir($newsDir)) mkdir($newsDir,0755,true);
          $img=uniqid('news_').'.'.$mimes[$mime];
          move_uploaded_file($_FILES['featured_image']['tmp_name'],$newsDir.$img);
        }
      }
      if($aid){
        $ex=$db->prepare("SELECT featured_image FROM news_articles WHERE id=?");$ex->execute([$aid]);$ex=$ex->fetch();
        if(!$img&&$ex)$img=$ex['featured_image'];
        $pub=$status==='Published'?($ex['published_at']??date('Y-m-d H:i:s')):null;
        $db->prepare("UPDATE news_articles SET title=?,summary=?,content=?,category=?,tags=?,status=?,is_featured=?,featured_image=?,published_at=? WHERE id=?")->execute([$title,$summary,$content,$cat,$tags,$status,$feat,$img,$pub,$aid]);
        $ok='Article updated.'; logActivity("Updated article: $title",'news',$aid);
      }else{
        $sl=mslug($title,$db); $pub=$status==='Published'?date('Y-m-d H:i:s'):null;
        $db->prepare("INSERT INTO news_articles(author_id,title,slug,summary,content,category,tags,status,is_featured,featured_image,published_at)VALUES(?,?,?,?,?,?,?,?,?,?,?)")->execute([$_SESSION['user_id'],$title,$sl,$summary,$content,$cat,$tags,$status,$feat,$img,$pub]);
        $nid=$db->lastInsertId(); $ok='Article saved.'; logActivity("Created article: $title",'news',(int)$nid);
        if($a==='publish'){header('Location:'.APP_URL.'/modules/news/article.php?slug='.$sl);exit;}
        $eid=(int)$nid;$view='edit';
      }
    }
  }elseif($a==='delete'){
    $aid=(int)$_POST['article_id'];
    $db->prepare("DELETE FROM news_articles WHERE id=?")->execute([$aid]);
    $ok='Article deleted.'; header('Location:'.APP_URL.'/admin/manage_news.php');exit;
  }elseif($a==='toggle_featured'){
    $aid=(int)$_POST['article_id'];
    $db->prepare("UPDATE news_articles SET is_featured=NOT is_featured WHERE id=?")->execute([$aid]);
    $db->prepare("UPDATE news_articles SET is_featured=0 WHERE id!=? AND is_featured=1")->execute([$aid]);
    header('Location:'.APP_URL.'/admin/manage_news.php');exit;
  }
}

$editArt=null;
if($view==='edit'&&$eid){$s=$db->prepare("SELECT * FROM news_articles WHERE id=?");$s->execute([$eid]);$editArt=$s->fetch();if($editArt)$pageTitle='Edit: '.substr($editArt['title'],0,30).'...';}
$articles=$db->query("SELECT na.*,u.full_name an FROM news_articles na LEFT JOIN users u ON u.id=na.author_id ORDER BY na.created_at DESC")->fetchAll();
$cats=['Match Report','Transfer','General','Tournament','Interview','Analysis'];
$catC=['Match Report'=>'var(--acc)','Analysis'=>'var(--pri)','General'=>'var(--grn)','Tournament'=>'var(--gld)','Interview'=>'var(--pur)','Transfer'=>'var(--acc)'];
include __DIR__.'/../includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <h1 style="font-size:18px;font-weight:600"><?=$view==='list'?'News':''.($eid?'Edit Article':'Write Article')?></h1>
    <div style="display:flex;gap:6px">
      <?php if($view!=='list'):?>
        <a href="<?=APP_URL?>/admin/manage_news.php" class="btn btn-ghost btn-sm"><i class="bi bi-arrow-left"></i>All Articles</a>
      <?php else:?>
        <a href="<?=APP_URL?>/admin/export_pdf.php?type=news" class="btn btn-ghost btn-sm" target="_blank"><i class="bi bi-file-earmark-pdf"></i>Export PDF</a>
        <a href="?view=edit" class="btn btn-pri btn-sm"><i class="bi bi-pencil-square"></i>Write Article</a>
      <?php endif;?>
    </div>
  </div>
  <?php if($ok):?><div class="alert a-ok"><i class="bi bi-check-circle-fill"></i><?=htmlspecialchars($ok)?></div><?php endif;?>
  <?php if($err):?><div class="alert a-err"><i class="bi bi-exclamation-circle-fill"></i><?=htmlspecialchars($err)?></div><?php endif;?>

  <?php if($view==='list'):?>
  <div class="card">
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Views</th><th>Featured</th><th>Published</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($articles as $a):?>
          <tr>
            <td style="max-width:280px"><div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($a['title'])?></div></td>
            <td><span style="font-size:11px;font-weight:600;color:<?=$catC[$a['category']]??'var(--t2)'?>"><?=$a['category']?></span></td>
            <td style="font-size:12px;color:var(--t2)"><?=htmlspecialchars($a['an']??'Staff')?></td>
            <td><span class="badge <?=$a['status']==='Published'?'b-done':($a['status']==='Draft'?'b-draft':'b-off')?>" style="font-size:10px"><?=$a['status']?></span></td>
            <td style="font-size:12px;color:var(--t2)"><?=number_format($a['views'])?></td>
            <td class="text-center"><form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle_featured"><input type="hidden" name="article_id" value="<?=$a['id']?>"><button type="submit" style="background:none;border:none;cursor:pointer;color:<?=$a['is_featured']?'var(--gld)':'var(--t3)'?>;font-size:14px;padding:0"><i class="bi bi-star<?=$a['is_featured']?'-fill':''?>"></i></button></form></td>
            <td style="font-size:12px;color:var(--t2)"><?=$a['published_at']?date('d M Y',strtotime($a['published_at'])):'—'?></td>
            <td>
              <div style="display:flex;gap:4px">
                <a href="?view=edit&id=<?=$a['id']?>" class="btn btn-ghost btn-sm btn-icon" title="Edit"><i class="bi bi-pencil" style="font-size:12px"></i></a>
                <?php if($a['status']==='Published'):?><a href="<?=APP_URL?>/modules/news/article.php?slug=<?=urlencode($a['slug'])?>" target="_blank" class="btn btn-ghost btn-sm btn-icon" title="View"><i class="bi bi-eye" style="font-size:12px"></i></a><?php endif;?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="article_id" value="<?=$a['id']?>"><button type="submit" class="btn btn-danger btn-sm btn-icon"><i class="bi bi-trash" style="font-size:12px"></i></button></form>
              </div>
            </td>
          </tr>
          <?php endforeach;?>
          <?php if(empty($articles)):?><tr><td colspan="8" style="text-align:center;padding:30px;color:var(--t3)">No articles yet</td></tr><?php endif;?>
        </tbody>
      </table>
    </div>
  </div>

  <?php else: // EDIT/WRITE VIEW ?>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="<?=$eid?'update':'save'?>" id="fAction">
    <?php if($eid):?><input type="hidden" name="article_id" value="<?=$eid?>"><?php endif;?>
    <div style="display:grid;grid-template-columns:1fr 280px;gap:16px">
      <div style="display:flex;flex-direction:column;gap:12px">
        <div class="field"><label class="label">Title</label><input type="text" name="title" class="input" required style="font-size:16px;font-weight:600" placeholder="Article headline..." value="<?=htmlspecialchars($editArt['title']??'')?>"></div>
        <div class="field"><label class="label">Summary</label><textarea name="summary" class="textarea" rows="2" placeholder="Brief description for listing page..."><?=htmlspecialchars($editArt['summary']??'')?></textarea></div>
        <div class="card" style="overflow:hidden">
          <div style="padding:8px 12px;border-bottom:1px solid var(--b0);display:flex;align-items:center;gap:6px;background:var(--s2)">
            <span style="font-size:12px;font-weight:600;color:var(--t2)">Content</span>
            <?php foreach(['b'=>'type-bold','i'=>'type-italic','h3'=>'type-h3','ul'=>'list-ul','ol'=>'list-ol'] as $tag=>$ico):?>
            <button type="button" class="btn btn-ghost btn-sm btn-icon" onclick="ins('<?=$tag?>')" style="font-size:11px"><i class="bi bi-<?=$ico?>"></i></button>
            <?php endforeach;?>
          </div>
          <textarea name="content" id="editor" rows="20" style="background:var(--s2);border:none;color:var(--t1);padding:12px 14px;font-size:13px;font-family:'SFMono-Regular',Consolas,monospace;width:100%;outline:none;resize:vertical;min-height:320px" placeholder="Write article content here (HTML supported)..."><?=htmlspecialchars($editArt['content']??'')?></textarea>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:12px">
        <div class="card">
          <div class="card-head"><h3>Publish</h3></div>
          <div style="padding:12px;display:flex;flex-direction:column;gap:10px">
            <div class="field"><label class="label">Status</label><select name="status" class="select"><option value="Draft" <?=($editArt['status']??'')==='Draft'?'selected':''?>>Draft</option><option value="Published" <?=($editArt['status']??'')==='Published'?'selected':''?>>Published</option><option value="Archived" <?=($editArt['status']??'')==='Archived'?'selected':''?>>Archived</option></select></div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="is_featured" value="1" <?=($editArt['is_featured']??0)?'checked':''?> style="accent-color:var(--gld)"><span style="font-size:13px">Featured article</span></label>
            <div style="display:flex;flex-direction:column;gap:6px">
              <button type="submit" name="action" value="save" class="btn btn-def btn-sm" style="justify-content:center"><i class="bi bi-floppy"></i>Save Draft</button>
              <button type="submit" name="action" value="publish" class="btn btn-pri btn-sm" style="justify-content:center"><i class="bi bi-send-fill"></i>Publish</button>
              <?php if($eid&&($editArt['status']??'')==='Published'):?><a href="<?=APP_URL?>/modules/news/article.php?slug=<?=urlencode($editArt['slug']??'')?>" target="_blank" class="btn btn-ghost btn-sm" style="justify-content:center"><i class="bi bi-eye"></i>Preview</a><?php endif;?>
            </div>
          </div>
        </div>
        <div class="card"><div class="card-head"><h3>Category &amp; Tags</h3></div>
          <div style="padding:12px;display:flex;flex-direction:column;gap:10px">
            <div class="field"><label class="label">Category</label><select name="category" class="select"><?php foreach($cats as $c):?><option value="<?=$c?>" <?=($editArt['category']??'')===$c?'selected':''?>><?=$c?></option><?php endforeach;?></select></div>
            <div class="field"><label class="label">Tags (comma-separated)</label><input type="text" name="tags" class="input" placeholder="blantyre, volleyball..." value="<?=htmlspecialchars($editArt['tags']??'')?>"></div>
          </div>
        </div>
        <div class="card"><div class="card-head"><h3>Featured Image</h3></div>
          <div style="padding:12px">
            <?php if(!empty($editArt['featured_image'])):?><img src="<?=APP_URL?>/assets/uploads/news/<?=htmlspecialchars($editArt['featured_image'])?>" style="width:100%;border-radius:var(--r);max-height:120px;object-fit:cover;margin-bottom:8px"><?php endif;?>
            <input type="file" name="featured_image" class="input" accept="image/jpeg,image/png,image/webp" style="font-size:12px">
            <div style="font-size:11px;color:var(--t3);margin-top:4px">JPG, PNG or WebP. Recommended 1200×630px</div>
          </div>
        </div>
      </div>
    </div>
  </form>
  <script>
  function ins(tag){const e=document.getElementById('editor');const s=e.selectionStart,en=e.selectionEnd,sel=e.value.substring(s,en)||'text';let o,c;if(['b','i','h3'].includes(tag)){o=`<${tag}>`;c=`</${tag}>`;}else if(tag==='ul'){o='<ul>\n  <li>';c='</li>\n</ul>';}else if(tag==='ol'){o='<ol>\n  <li>';c='</li>\n</ol>';}else{o=`<${tag}>`;c=`</${tag}>`;}e.value=e.value.substring(0,s)+o+sel+c+e.value.substring(en);e.focus();e.setSelectionRange(s+o.length,s+o.length+sel.length);}
  </script>
  <?php endif;?>
</div>
<?php include __DIR__.'/../includes/footer.php';?>
