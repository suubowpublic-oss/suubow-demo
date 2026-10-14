<?php
require __DIR__ . '/config.php';
require_admin();
$title = 'Change Admin Password';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($current, $admin['password_hash'])) {
        $error = 'Password-kii hadda jira waa khalad.';
    } elseif (strlen($new) < 8) {
        $error = 'Password-ka cusub waa inuu ugu yaraan ka koobnaadaa 8 xaraf.';
    } elseif ($new !== $confirm) {
        $error = 'Password-ka cusub iyo xaqiijintiisu isma waafaqaan.';
    } elseif (password_verify($new, $admin['password_hash'])) {
        $error = 'Fadlan dooro password ka duwan kii hore.';
    } else {
        $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'admin'");
        $update->execute([password_hash($new, PASSWORD_DEFAULT), (int)$_SESSION['user_id']]);
        $success = 'Password-ka Admin si guul leh ayaa loo beddelay.';
    }
}
require __DIR__ . '/header.php';
?>
<div class="section-heading"><div><h3>Beddel password-ka Admin</h3><p>Geli password-ka hadda jira, kadib dooro mid cusub oo adag.</p></div></div>
<?php if ($error): ?><div class="alert"><?=e($error)?></div><?php endif; ?>
<?php if ($success): ?><div class="success"><?=e($success)?></div><?php endif; ?>
<div class="form-card" style="max-width:680px">
  <div class="form-intro"><div class="stat-icon purple">🔒</div><div><h3>Change Password</h3><p>Isbeddelkan wuxuu khuseeyaa akoonka Admin ee hadda ku jira.</p></div></div>
  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
    <div class="form-grid">
      <div class="span-2"><label>Password-ka hadda jira</label><input type="password" name="current_password" required autocomplete="current-password"></div>
      <div><label>Password cusub (ugu yaraan 8 xaraf)</label><input type="password" name="new_password" minlength="8" required autocomplete="new-password"></div>
      <div><label>Xaqiiji password-ka cusub</label><input type="password" name="confirm_password" minlength="8" required autocomplete="new-password"></div>
    </div>
    <div class="form-actions"><button type="submit" class="btn primary">Update Password</button></div>
  </form>
</div>
<?php require __DIR__ . '/footer.php'; ?>
