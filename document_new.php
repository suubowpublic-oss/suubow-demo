<?php
require __DIR__ . '/config.php'; require_login();
$title = 'Xaree Document Cusub'; $error=''; $success='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();
 $full=trim((string)($_POST['full_name']??'')); $mobile=trim((string)($_POST['mobile']??'')); $address=trim((string)($_POST['address']??'')); $identity=trim((string)($_POST['identity_number']??'')); $type=(string)($_POST['document_type']??''); $ref=trim((string)($_POST['ref_no']??''));
 if (!$full||!$mobile||!$address||!$identity||!in_array($type,categories(),true)||!$ref) $error='Fadlan buuxi dhammaan meelaha loo baahan yahay.';
 elseif (($dup=$pdo->prepare('SELECT id FROM documents WHERE ref_no=? LIMIT 1')) && ($dup->execute([$ref]) && $dup->fetch())) $error='Reject Duplicate REF.NS: Ref.NS-kan hore ayaa loo isticmaalay.';
 elseif (empty($_FILES['pdf']['name']) || $_FILES['pdf']['error']!==UPLOAD_ERR_OK) $error='Fadlan dooro PDF sax ah.';
 elseif ($_FILES['pdf']['size']>15*1024*1024) $error='PDF-gu waa inuu ka yaraadaa 15MB.';
 else {
  $tmp=$_FILES['pdf']['tmp_name']; $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
  if ($mime!=='application/pdf' || strtolower(pathinfo($_FILES['pdf']['name'],PATHINFO_EXTENSION))!=='pdf') $error='Faylka la oggol yahay waa PDF oo keliya.';
  else {
   $dir=__DIR__.'/uploads'; if(!is_dir($dir)) mkdir($dir,0750,true);
   $filename=bin2hex(random_bytes(18)).'.pdf';
   if(move_uploaded_file($tmp,$dir.'/'.$filename)) {
    try {
     $stmt=$pdo->prepare('INSERT INTO documents(full_name,mobile,address,identity_number,document_type,ref_no,pdf_filename,uploaded_by) VALUES(?,?,?,?,?,?,?,?)');
     $stmt->execute([$full,$mobile,$address,$identity,$type,$ref,$filename,$_SESSION['user_id']]);
     header('Location: documents.php?saved=1'); exit;
    } catch(PDOException $ex) { @unlink($dir.'/'.$filename); $error='REF.No. waa inuu gaar ahaadaa, ama xogta lama kaydin.'; }
   } else $error='PDF-ga lama kaydin. Hubi oggolaanshaha folder-ka uploads.';
  }
 }
}
require __DIR__.'/header.php';
?>
<div class="form-card"><div class="form-intro"><div class="stat-icon purple">＋</div><div><h3>Xogta qofka iyo dukumentiga</h3><p>Geli xogta qofka, REF.No., kadibna ku lifaaq PDF-ga.</p></div></div>
<?php if($error): ?><div class="alert"><?=e($error)?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><div class="form-grid">
<div><label>Magaca qofka oo buuxa *</label><input name="full_name" required value="<?=e($_POST['full_name']??'')?>"></div>
<div><label>Mobile number *</label><input name="mobile" required inputmode="tel" value="<?=e($_POST['mobile']??'')?>"></div>
<div><label>Address / Cinwaan *</label><input name="address" required value="<?=e($_POST['address']??'')?>"></div>
<div><label>Aqoonsi number *</label><input name="identity_number" required value="<?=e($_POST['identity_number']??'')?>"></div>
<div><label>Nooca documentiga *</label><select name="document_type" required><option value="">-- Dooro nooca --</option><?php foreach(categories() as $c): ?><option <?=($_POST['document_type']??'')===$c?'selected':''?>><?=e($c)?></option><?php endforeach; ?></select></div>
<div><label>REF.No. *</label><input name="ref_no" required placeholder="Tusaale: NS/001/2026" value="<?=e($_POST['ref_no']??'')?>"></div>
<div class="span-2"><label>Ku lifaaq document PDF * (ugu badnaan 15MB)</label><input type="file" name="pdf" accept="application/pdf,.pdf" required><small class="muted">Faylasha PDF oo keliya ayaa la oggol yahay.</small></div>
</div><div class="form-actions"><a class="btn secondary" href="documents.php">Ka noqo</a><button class="btn primary">Kaydi documentiga</button></div></form></div>
<?php require __DIR__.'/footer.php'; ?>
