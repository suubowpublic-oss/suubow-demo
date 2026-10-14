<?php
require __DIR__.'/config.php'; require_admin();
$title='Employee Control / Shaqaalaha'; $msg=''; $err=''; $editEmployee=null;
$educationOptions=['Jaamacad','Dugsi sare','Dugsi dhexe','Dugsi hoose','Waxbarasho kale'];
$pdo->exec("CREATE TABLE IF NOT EXISTS employees (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(180) NOT NULL,
 mobile VARCHAR(40) NOT NULL,
 address VARCHAR(255) NOT NULL,
 education ENUM('Jaamacad','Dugsi sare','Dugsi dhexe','Dugsi hoose','Waxbarasho kale') NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 employee_role ENUM('Qoraa','Sawir Qaade','Document keydiye') NOT NULL DEFAULT 'Qoraa',
 photo VARCHAR(255) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$cols=$pdo->query("SHOW COLUMNS FROM employees")->fetchAll(PDO::FETCH_COLUMN);
if(!in_array('employee_role',$cols,true)) $pdo->exec("ALTER TABLE employees ADD COLUMN employee_role ENUM('Qoraa','Sawir Qaade','Document keydiye') NOT NULL DEFAULT 'Qoraa'");
if(!in_array('photo',$cols,true)) $pdo->exec("ALTER TABLE employees ADD COLUMN photo VARCHAR(255) NULL");
$roleOptions=['Qoraa','Sawir Qaade','Document keydiye'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf(); $action=$_POST['action']??''; $id=(int)($_POST['id']??0);
 if ($action==='add' || $action==='update') {
  $name=trim((string)($_POST['full_name']??'')); $mobile=trim((string)($_POST['mobile']??'')); $address=trim((string)($_POST['address']??'')); $education=(string)($_POST['education']??''); $employeeRole=(string)($_POST['employee_role']??'Qoraa'); $photoName=null; if(isset($_FILES['photo']) && $_FILES['photo']['error']===UPLOAD_ERR_OK){$mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['photo']['tmp_name']); if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)||$_FILES['photo']['size']>3*1024*1024){$err='Employee Photo waa inuu noqdaa JPG, PNG ama WEBP oo ka yar 3MB.';}else{$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime];$photoName='employee_'.bin2hex(random_bytes(12)).'.'.$ext;if(!is_dir(__DIR__.'/uploads'))mkdir(__DIR__.'/uploads',0750,true);if(!move_uploaded_file($_FILES['photo']['tmp_name'],__DIR__.'/uploads/'.$photoName)){$photoName=null;$err='Sawirka lama kaydin.';}}}
  if (!$name||!$mobile||!$address||!in_array($education,$educationOptions,true)||!in_array($employeeRole,$roleOptions,true)) $err='Fadlan buuxi xogta shaqaalaha oo dhan.';
  elseif ($action==='update' && $id<1) $err='Shaqaalaha la cusboonaysiinayo lama helin.';
  else {
   if($action==='add') { $st=$pdo->prepare('INSERT INTO employees(full_name,mobile,address,education,employee_role,photo) VALUES(?,?,?,?,?,?)'); $st->execute([$name,$mobile,$address,$education,$employeeRole,$photoName]); $msg='Shaqaalaha waa la diiwaangeliyey.'; }
   else { $st=$pdo->prepare('UPDATE employees SET full_name=?,mobile=?,address=?,education=?,employee_role=?'.($photoName?',photo=?':'').' WHERE id=?'); $args=[$name,$mobile,$address,$education,$employeeRole]; if($photoName)$args[]=$photoName; $args[]=$id; $st->execute($args); $msg='Xogta shaqaalaha waa la cusboonaysiiyey.'; }
  }
 } elseif ($action==='delete') {
  if($id<1) $err='Shaqaalaha lama helin.';
  else {
   // Soft-delete keeps attendance history intact; inactive employees cannot be checked in.
   $st=$pdo->prepare('UPDATE employees SET active=0 WHERE id=? AND active=1'); $st->execute([$id]);
   if($st->rowCount()) $msg='Shaqaalaha waa la tirtiray liiska shaqaalaha firfircoon; taariikhdiisa Attendance waa la ilaaliyey.';
   else $err='Shaqaalaha lama helin ama horay ayaa loo tirtiray.';
  }
 } elseif ($action==='restore') {
  if($id>0){$st=$pdo->prepare('UPDATE employees SET active=1 WHERE id=?');$st->execute([$id]);$msg='Shaqaalaha waa la soo celiyey.';}
 }
}
if(isset($_GET['edit'])) { $st=$pdo->prepare('SELECT * FROM employees WHERE id=? AND active=1');$st->execute([(int)$_GET['edit']]);$editEmployee=$st->fetch()?:null; }
$employees=$pdo->query('SELECT * FROM employees ORDER BY active DESC,full_name')->fetchAll();
require __DIR__.'/header.php'; ?>
<div class="section-heading"><div><h3>Diiwaangelinta shaqaalaha</h3><p>Ku dar, cusboonaysii ama ka saar shaqaalaha liiska firfircoon. Attendance waxaa loogu talagalay oo keliya Soo gal iyo Ka bax.</p></div></div>
<?php if($msg):?><div class="success"><?=e($msg)?></div><?php endif;?><?php if($err):?><div class="alert"><?=e($err)?></div><?php endif;?>
<div class="form-card"><h3><?= $editEmployee?'Update Employee / Cusboonaysii shaqaalaha':'Diiwaangeli shaqaale cusub' ?></h3><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="<?=$editEmployee?'update':'add'?>"><?php if($editEmployee):?><input type="hidden" name="id" value="<?=$editEmployee['id']?>"><?php endif;?><div class="form-grid"><div><label>Magaca shaqaalaha *</label><input name="full_name" required value="<?=e($editEmployee['full_name']??'')?>"></div><div><label>Mobile number *</label><input name="mobile" required inputmode="tel" value="<?=e($editEmployee['mobile']??'')?>"></div><div><label>Address uu degan yahay *</label><input name="address" required value="<?=e($editEmployee['address']??'')?>"></div><div><label>Employee Role *</label><select name="employee_role" required><?php foreach($roleOptions as $ro):?><option value="<?=e($ro)?>" <?=($editEmployee['employee_role']??'Qoraa')===$ro?'selected':''?>><?=e($ro)?></option><?php endforeach;?></select></div><div><label>Employee Photo</label><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></div><div><label>Heerka waxbarashada *</label><select name="education" required><option value="">-- Dooro --</option><?php foreach($educationOptions as $ed):?><option value="<?=e($ed)?>" <?=($editEmployee['education']??'')===$ed?'selected':''?>><?=e($ed)?></option><?php endforeach;?></select></div></div><div class="form-actions"><button class="btn primary"><?=$editEmployee?'Kaydi isbeddelka':'Diiwaangeli shaqaalaha'?></button><?php if($editEmployee):?><a class="btn" href="employees.php">Jooji</a><?php endif;?></div></form></div>
<div class="section-heading"><div><h3>Liiska shaqaalaha</h3><p>Shaqaalaha aan firfircoonayn waa laga saarayaa Attendance, laakiin taariikhdooda waa la hayaa.</p></div></div>
<div class="table-wrap"><table><thead><tr><th>Sawir</th><th>Magaca</th><th>Role</th><th>Mobile</th><th>Address</th><th>Waxbarasho</th><th>Xaalad</th><th>Ficil</th></tr></thead><tbody><?php if(!$employees):?><tr><td colspan="8" class="empty">Weli shaqaale lama diiwaangelin.</td></tr><?php endif;?><?php foreach($employees as $emp):?><tr><td><?php if(!empty($emp['photo'])):?><img src="uploads/<?=e($emp['photo'])?>" alt="Employee Photo" style="width:42px;height:42px;object-fit:cover;border-radius:50%"><?php else:?>—<?php endif;?></td><td><?=e($emp['full_name'])?></td><td><?=e($emp['employee_role']??'Qoraa')?></td><td><?=e($emp['mobile'])?></td><td><?=e($emp['address'])?></td><td><?=e($emp['education'])?></td><td><?=$emp['active']?'Firfircoon':'La tirtiray'?></td><td><div class="form-actions"><a class="btn" href="employees.php?edit=<?=$emp['id']?>">Update</a><?php if($emp['active']):?><form method="post" onsubmit="return confirm('Ma hubtaa inaad shaqaalahan ka saarayso liiska firfircoon? Taariikhda Attendance waa la ilaalinayaa.');"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$emp['id']?>"><button class="btn warning-btn" type="submit">Delete</button></form><?php else:?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="restore"><input type="hidden" name="id" value="<?=$emp['id']?>"><button class="btn" type="submit">Soo celi</button></form><?php endif;?></div></td></tr><?php endforeach;?></tbody></table></div>
<?php require __DIR__.'/footer.php'; ?>
