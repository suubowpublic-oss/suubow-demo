<?php
require __DIR__ . '/config.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare('SELECT id, username, password_hash, full_name, role FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php'); exit;
    }
    $error = 'Magaca isticmaalaha ama password-ku waa khalad.';
}
?><!doctype html><html lang="so"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Gelitaanka | Suubow</title><link rel="stylesheet" href="style.css"></head>
<body class="login-bg"><main class="login-card"><div class="brand-mark">S</div><h1>Suubow Documents</h1><p class="muted">Nidaamka kaydinta dukumentiyada</p>
<?php if ($error): ?><div class="alert"><?=e($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><label>Username</label><input name="username" required autocomplete="username" autofocus><label>Password</label><input type="password" name="password" required autocomplete="current-password"><button class="btn primary full login-submit" id="loginSubmit">Soo gal <span class="loader"></span></button></form>
<p class="small muted">Default: admin / ChangeMe123!</p></main><script>document.querySelector("form").addEventListener("submit",()=>{const b=document.getElementById("loginSubmit");b.classList.add("loading");b.disabled=true;});</script></body></html>
