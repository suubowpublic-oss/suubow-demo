<?php
require __DIR__.'/config.php'; require_login();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT); if(!$id){http_response_code(400);exit('ID-ga documentiga ma saxna.');}
$sql='SELECT * FROM documents WHERE id=?';$args=[$id];if(($_SESSION['role']??'staff')!=='admin'){$sql.=' AND uploaded_by=?';$args[]=(int)$_SESSION['user_id'];}$stmt=$pdo->prepare($sql);$stmt->execute($args);$doc=$stmt->fetch();if(!$doc){http_response_code(404);exit('Document lama helin.');}
$title='Update Document';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$full=trim((string)($_POST['full_name']??''));$mobile=trim((string)($_POST['mobile']??''));$address=trim((string)($_POST['address']??''));$identity=trim((string)($_POST['identity_number']??''));$type=(string)($_POST['document_type']??'');$ref=trim((string)($_POST['ref_no']??''));
 if(!$full||!$mobile||!$address||!$identity||!$ref||!in_array($type,categories(),true))$error='Fadlan buuxi dhammaan meelaha loo baahan yahay.';
 else {
  $newFile=null;
  if(isset($_FILES['pdf']) && $_FILES['pdf']['error']!==UPLOAD_ERR_NO_FILE){
   if($_FILES['pdf']['error']!==UPLOAD_ERR_OK)$error='PDF-ga lama soo gelin karin.';
   elseif($_FILES['pdf']['size']>15*1024*1024)$error='PDF-gu waa inuu ka yaraadaa 15MB.';
   else{$tmp=$_FILES['pdf']['tmp_name'];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);if($mime!=='application/pdf'||strtolower(pathinfo($_FILES['pdf']['name'],PATHINFO_EXTENSION))!=='pdf')$error='Faylka la oggol yahay waa PDF oo keliya.';else{$dir=__DIR__.'/uploads';if(!is_dir($dir))mkdir($dir,0750,true);$newFile=bin2hex(random_bytes(18)).'.pdf';if(!move_uploaded_file($tmp,$dir.'/'.$newFile)){$newFile=null;$error='PDF-ga lama kaydin.';}}}
  }
  if(!$error){try{$filename=$newFile?:$doc['pdf_filename'];$u=$pdo->prepare('UPDATE documents SET full_name=?,mobile=?,address=?,identity_number=?,document_type=?,ref_no=?,pdf_filename=? WHERE id=?');$u->execute([$full,$mobile,$address,$identity,$type,$ref,$filename,$id]);if($newFile&&is_file(__DIR__.'/uploads/'.$doc['pdf_filename']))@unlink(__DIR__.'/uploads/'.$doc['pdf_filename']);header('Location: document_view.php?id='.$id.'&updated=1');exit;}catch(PDOException $ex){if($newFile)@unlink(__DIR__.'/uploads/'.$newFile);$error='REF.No. waa inuu gaar ahaadaa, ama xogta lama kaydin.';}}
  $doc=array_merge($doc,['full_name'=>$full,'mobile'=>$mobile,'address'=>$address,'identity_number'=>$identity,'document_type'=>$type,'ref_no'=>$ref]);
 }
}
require __DIR__.'/header.php';
?>
<div class="form-card"><div class="form-intro"><div class="stat-icon purple">✎</div><div><h3>Update Document</h3><p>Waxaad cusboonaysiin kartaa xogta ama ku beddeli kartaa PDF cusub.</p></div></div>
<?php if($error): ?><div class="alert"><?=e($error)?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><div class="form-grid">
<div><label>Magaca qofka oo buuxa *</label><input name="full_name" required value="<?=e($doc['full_name'])?>"></div><div><label>Mobile number *</label><input name="mobile" required value="<?=e($doc['mobile'])?>"></div><div><label>Address / Cinwaan *</label><input name="address" required value="<?=e($doc['address'])?>"></div><div><label>Aqoonsi number *</label><input name="identity_number" required value="<?=e($doc['identity_number'])?>"></div>
<div><label>Nooca documentiga *</label><select name="document_type" required><?php foreach(categories() as $c): ?><option value="<?=e($c)?>" <?=$doc['document_type']===$c?'selected':''?>><?=e($c)?></option><?php endforeach; ?></select></div><div><label>REF.No. *</label><input name="ref_no" required value="<?=e($doc['ref_no'])?>"></div>
<div class="span-2"><label>Ku beddel PDF (ikhtiyaari)</label><input type="file" name="pdf" accept="application/pdf,.pdf"><small class="muted">Haddii aadan dooran PDF cusub, kii hore waa la haynayaa. Ugu badnaan 15MB.</small></div></div><div class="form-actions"><a class="btn secondary" href="document_view.php?id=<?=$id?>">Ka noqo</a><button class="btn primary">Update Document</button></div></form></div>
<?php require __DIR__.'/footer.php'; ?>
