<?php
require __DIR__.'/config.php'; require_login(); $title='Attendance / Xaadirinta'; $msg=''; $err=''; $admin=(($_SESSION['role']??'staff')==='admin');
if(!$admin){ http_response_code(403); exit('Xaadirinta shaqaalaha waxaa maamuli kara System Administrator oo keliya.'); }

// Automatic repair for existing installations: create the employees table and
// add attendance columns/indexes that may be missing from older databases.
$pdo->exec("CREATE TABLE IF NOT EXISTS employees (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(180) NOT NULL,
 mobile VARCHAR(40) NOT NULL,
 address VARCHAR(255) NOT NULL,
 education ENUM('Jaamacad','Dugsi sare','Dugsi dhexe','Dugsi hoose','Waxbarasho kale') NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$attendanceColumns = $pdo->query('SHOW COLUMNS FROM attendance')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('employee_id', $attendanceColumns, true)) {
    $pdo->exec('ALTER TABLE attendance ADD COLUMN employee_id INT UNSIGNED NULL AFTER id');
}
$attendanceIndexes = $pdo->query('SHOW INDEX FROM attendance')->fetchAll();
$indexNames = array_unique(array_column($attendanceIndexes, 'Key_name'));
// Preserve the user foreign key: add a standalone user_id index before
// dropping the legacy composite unique index that MySQL may be using for it.
if (!in_array('idx_attendance_user_id', $indexNames, true)) {
    $pdo->exec('ALTER TABLE attendance ADD INDEX idx_attendance_user_id (user_id)');
    $indexNames[] = 'idx_attendance_user_id';
}
// The old user/day uniqueness rule blocks recording multiple employees on one day.
if (in_array('unique_user_day', $indexNames, true)) {
    $pdo->exec('ALTER TABLE attendance DROP INDEX unique_user_day');
}
// Refresh index names after the migration before adding the employee/day key.
$attendanceIndexes = $pdo->query('SHOW INDEX FROM attendance')->fetchAll();
$indexNames = array_unique(array_column($attendanceIndexes, 'Key_name'));
if (!in_array('unique_employee_day', $indexNames, true)) {
    $pdo->exec('ALTER TABLE attendance ADD UNIQUE KEY unique_employee_day (employee_id, work_date)');
}
$foreignKeys = $pdo->prepare("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'employee_id' AND REFERENCED_TABLE_NAME = 'employees'");
$foreignKeys->execute();
if (!$foreignKeys->fetch()) {
    $pdo->exec('ALTER TABLE attendance ADD CONSTRAINT fk_attendance_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL');
}
if($_SERVER['REQUEST_METHOD']==='POST'){ verify_csrf(); $action=$_POST['action']??'';
 if(in_array($action,['in','out'],true)){ $eid=(int)($_POST['employee_id']??0);$today=date('Y-m-d');$st=$pdo->prepare('SELECT id FROM employees WHERE id=? AND active=1');$st->execute([$eid]);if(!$st->fetch())$err='Dooro shaqaale sax ah.';else{$st=$pdo->prepare('SELECT * FROM attendance WHERE employee_id=? AND work_date=?');$st->execute([$eid,$today]);$rec=$st->fetch();if($action==='in'){if($rec&&$rec['check_in'])$err='Shaqaalahani maanta hore ayuu u soo galay.';elseif($rec){$pdo->prepare('UPDATE attendance SET check_in=NOW() WHERE id=?')->execute([$rec['id']]);$msg='Waqtiga soo gelitaanka waa la diiwaangeliyey.';}else{$pdo->prepare('INSERT INTO attendance(employee_id,user_id,work_date,check_in) VALUES(?,?,?,NOW())')->execute([$eid,(int)$_SESSION['user_id'],$today]);$msg='Waqtiga soo gelitaanka waa la diiwaangeliyey.';}}else{if(!$rec||!$rec['check_in'])$err='Marka hore diiwaangeli waqtiga soo gelitaanka.';elseif($rec['check_out'])$err='Bixitaanka maanta hore ayaa loo diiwaangeliyey.';else{$pdo->prepare('UPDATE attendance SET check_out=NOW() WHERE id=?')->execute([$rec['id']]);$msg='Waqtiga bixitaanka waa la diiwaangeliyey.';}}}}
}
$employees=$pdo->query('SELECT * FROM employees WHERE active=1 ORDER BY full_name')->fetchAll();$rows=$pdo->query('SELECT a.*,e.full_name FROM attendance a JOIN employees e ON e.id=a.employee_id ORDER BY a.work_date DESC,a.check_in DESC LIMIT 100')->fetchAll();require __DIR__.'/header.php'; ?>
<div class="section-heading"><div><h3>Attendance — Soo gal / Ka bax</h3><p>Xaadirinta waxaa lagu koobay diiwaangelinta waqtiga soo gelitaanka iyo bixitaanka shaqada.</p></div></div><?php if($msg):?><div class="success"><?=e($msg)?></div><?php endif;?><?php if($err):?><div class="alert"><?=e($err)?></div><?php endif;?>
<div class="form-card"><h3>Diiwaangeli waqtiga shaqada</h3><p class="muted">Dooro shaqaalaha kadibna riix Soo gal ama Ka bax. Waqtiga waxaa si toos ah u kaydinaya system-ka.</p><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><div class="form-grid"><div><label>Shaqaalaha</label><select name="employee_id" required><option value="">-- Dooro shaqaale --</option><?php foreach($employees as $emp):?><option value="<?=$emp['id']?>"><?=e($emp['full_name'])?></option><?php endforeach;?></select></div><div class="form-actions" style="align-items:end"><button class="btn success-btn" name="action" value="in">◷ Soo gal shaqada</button><button class="btn warning-btn" name="action" value="out">◷ Ka bax shaqada</button></div></div></form></div>
<div class="section-heading"><div><h3>Taariikhda Attendance</h3><p>100-kii diiwaan ee ugu dambeeyey</p></div></div><div class="table-wrap"><table><thead><tr><th>Taariikh</th><th>Magaca shaqaalaha</th><th>Soo galay</th><th>Baxay</th></tr></thead><tbody><?php if(!$rows):?><tr><td colspan="4" class="empty">Weli xaadirin lama diiwaangelin.</td></tr><?php endif;?><?php foreach($rows as $r):?><tr><td><?=e(date('d/m/Y',strtotime($r['work_date'])))?></td><td><?=e($r['full_name'])?></td><td><?=$r['check_in']?e(date('h:i A',strtotime($r['check_in']))):'—'?></td><td><?=$r['check_out']?e(date('h:i A',strtotime($r['check_out']))):'—'?></td></tr><?php endforeach;?></tbody></table></div>
<?php require __DIR__.'/footer.php'; ?>
