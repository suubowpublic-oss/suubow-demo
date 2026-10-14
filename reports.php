<?php
require __DIR__.'/config.php'; require_login(); $title='Reports';
$period=(string)($_GET['period']??'monthly'); if(!in_array($period,['weekly','monthly','yearly'],true))$period='monthly';
if($period==='weekly'){$where='created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';$label='7-dii maalmood ee u dambeeyey';}
elseif($period==='yearly'){$where='YEAR(created_at)=YEAR(CURDATE())';$label='Sannadkan';}
else{$where='YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())';$label='Bishan';}
$args=[]; if(($_SESSION['role']??'staff')!=='admin'){$where.=' AND uploaded_by=?';$args[]=(int)$_SESSION['user_id'];}$stmt=$pdo->prepare("SELECT COUNT(*) FROM documents WHERE $where");$stmt->execute($args);$total=(int)$stmt->fetchColumn();
$stmt=$pdo->prepare("SELECT document_type,COUNT(*) total FROM documents WHERE $where GROUP BY document_type ORDER BY total DESC");$stmt->execute($args);$rows=$stmt->fetchAll();
require __DIR__.'/header.php';
?>
<div class="report-tabs"><a class="<?=$period==='weekly'?'selected':''?>" href="?period=weekly">Weekly</a><a class="<?=$period==='monthly'?'selected':''?>" href="?period=monthly">Monthly</a><a class="<?=$period==='yearly'?'selected':''?>" href="?period=yearly">Yearly</a></div>
<div class="report-summary"><div><span>Warbixinta: <?=e($label)?></span><h1><?=$total?></h1><p>Wadarta dukumentiyada muddadan</p></div><div class="report-art">▥</div></div>
<div class="table-wrap"><table><thead><tr><th>Nooca documentiga</th><th>Tirada</th><th>Saamiga</th></tr></thead><tbody><?php foreach($rows as $r): $pct=$total?round((int)$r['total']/$total*100):0; ?><tr><td><?=e($r['document_type'])?></td><td><strong><?=(int)$r['total']?></strong></td><td><div class="progress"><span style="width:<?=$pct?>%"></span></div><?=$pct?>%</td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="3" class="empty">Wax dukumenti ah laguma xareyn muddadan.</td></tr><?php endif; ?></tbody></table></div>
<?php require __DIR__.'/footer.php'; ?>
