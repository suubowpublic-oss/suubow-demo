<?php
require __DIR__.'/config.php';
require_admin();
$title='User Control'; $error=''; $success='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action=$_POST['action']??'';
    if ($action==='create') {
        $username=trim((string)($_POST['username']??''));
        $name=trim((string)($_POST['full_name']??''));
        $pass=(string)($_POST['password']??'');
        if ($username==='' || $name==='' || strlen($pass)<8) {
            $error='Buuxi xogta oo password-ku ha ahaado ugu yaraan 8 xaraf.';
        } else {
            try {
                $st=$pdo->prepare("INSERT INTO users(username,password_hash,full_name,role) VALUES(?,?,?,'staff')");
                $st->execute([$username,password_hash($pass,PASSWORD_DEFAULT),$name]);
                $success='User cusub waa la sameeyey.';
            } catch (PDOException $e) { $error='Username-kan hore ayuu u jiraa.'; }
        }
    } elseif ($action==='delete') {
        $id=(int)($_POST['id']??0);
        if ($id===(int)$_SESSION['user_id']) {
            $error='Ma tirtiri kartid akoonka aad hadda ku jirto.';
        } else {
            $st=$pdo->prepare('SELECT role FROM users WHERE id=?'); $st->execute([$id]); $target=$st->fetch();
            if (!$target) { $error='User lama helin.'; }
            elseif ($target['role']==='admin') { $error='Akoon admin ah lagama tirtiri karo boggan.'; }
            else {
                $chk=$pdo->prepare('SELECT COUNT(*) FROM documents WHERE uploaded_by=?'); $chk->execute([$id]); $docs=(int)$chk->fetchColumn();
                $chk=$pdo->prepare('SELECT COUNT(*) FROM attendance WHERE user_id=?'); $chk->execute([$id]); $att=(int)$chk->fetchColumn();
                if ($docs>0 || $att>0) {
                    $error='User-kan wuxuu leeyahay dukumenti ama xaadirin taariikhi ah. Si xogta loo ilaaliyo, marka hore kaydi/wareeji xogtaas ka hor tirtiridda akoonka.';
                } else {
                    $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]); $success='User-ka waa la tirtiray.';
                }
            }
        }
    }
}
$users=$pdo->query('SELECT id,username,full_name,role,created_at FROM users ORDER BY created_at DESC')->fetchAll();
require __DIR__.'/header.php';
?>
<div class="section-heading"><div><h3>Maamulka isticmaalayaasha</h3><p>Admin-ku wuxuu samayn karaa ama tirtiri karaa akoonnada shaqaalaha.</p></div></div>
<?php if($error):?><div class="alert"><?=e($error)?></div><?php endif;?><?php if($success):?><div class="success"><?=e($success)?></div><?php endif;?>
<div class="form-card"><h3>Create User</h3><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="create"><div class="form-grid"><div><label>Magaca buuxa</label><input name="full_name" required></div><div><label>Username</label><input name="username" required autocomplete="off"></div><div><label>Password (ugu yaraan 8 xaraf)</label><input type="password" name="password" minlength="8" required autocomplete="new-password"></div></div><div class="form-actions"><button class="btn primary">Create User</button></div></form></div>
<div class="section-heading"><div><h3>Users-ka jira</h3><p>Shaqaalaha (staff) waxay arki karaan oo keliya dukumentiyada ay iyagu xareeyeen.</p></div></div><div class="table-wrap"><table><thead><tr><th>Magac</th><th>Username</th><th>Role</th><th>Taariikh</th><th>Fal</th></tr></thead><tbody><?php foreach($users as $u):?><tr><td><?=e($u['full_name'])?></td><td><?=e($u['username'])?></td><td><?=e($u['role'])?></td><td><?=e(date('d/m/Y',strtotime($u['created_at'])))?></td><td><?php if($u['role']==='staff'):?><form method="post" onsubmit="return confirm('Ma hubtaa inaad tirtirayso user-kan?');"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn secondary">Delete User</button></form><?php else:?>—<?php endif;?></td></tr><?php endforeach;?></tbody></table></div>
<?php require __DIR__.'/footer.php'; ?>
