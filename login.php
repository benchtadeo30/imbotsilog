<?php include 'auth.php';
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verify_csrf()) $errors[]='Invalid request.';
    $email=trim($_POST['email']??'');
    $pass=$_POST['password']??'';
    if ($email===''||$pass==='') $errors[]='Email and password are required.';
    if (!$errors) {
        $conn=new mysqli('localhost','root','','imbotsilog_db');
        $stmt=$conn->prepare('SELECT user_id,first_name,last_name,password_hash,role FROM users WHERE email=?');
        $stmt->bind_param('s',$email); $stmt->execute();
        $res=$stmt->get_result();
        if ($user=$res->fetch_assoc()) {
            if (password_verify($pass,$user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']=$user['user_id'];
                $_SESSION['user_role']=$user['role'];
                $_SESSION['user_name']=$user['first_name'].' '.$user['last_name'];
                if ($user['role']==='admin') header('Location: admin_dashboard.php');
                else header('Location: home.php');
                exit;
            } else $errors[]='Invalid email or password.';
        } else $errors[]='Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Imbo't Silog</title>
  <link rel="stylesheet" href="home.css">
  <style>
    .auth-card { max-width: 420px; margin: 40px auto; background:#1a1a1a; border:2px solid orange; border-radius:20px; padding:28px; }
    .auth-card h2 { color:#ff7a00; text-align:center; margin:0 0 8px; }
    .auth-card .sub { color:#bbb; text-align:center; margin-bottom:18px; }
    .field { width:100%; box-sizing:border-box; padding:10px; border-radius:10px; border:1px solid #444; background:#0f0f0f; color:#fff; font-family:'Inter',Arial,sans-serif; margin:6px 0 12px; }
    .btn { width:100%; box-sizing:border-box; background:#ff7a00; color:#000; border:none; font-weight:bold; padding:12px; border-radius:10px; cursor:pointer; font-family:'Inter',Arial,sans-serif; }
    .link { color:#ff7a00; text-decoration:none; }
    .err { color:#f87171; font-size:13px; margin-bottom:10px; }
  </style>
</head>
<body>

    <!-- SIDE NAV BUTTON -->
<div class="menu-btn" onclick="toggleSidebar()">☰</div>

<!-- SIDEBAR -->
<div id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <img src="logo.jpg" class="logo" alt="Imbo't Silog">
        <button class="close-side" onclick="toggleSidebar()">✕</button>
    </div>
    <nav>
        <a href="home.php">🏠 Home</a>
        <a href="menu.php">☰ Menu</a>
        <a href="about.php">💬 About</a>
        <a href="more.php">ℹ️ More</a>
        <a href="login.php">🔐 Login</a>
        <a href="register.php">📝 Register</a>
    </nav>
    <div class="sidebar-footer">
        Imbo't Silog &copy; <?= date('Y') ?>
    </div>
</div>

<!-- NAVBAR -->
<div class="navbar">
    <img src="logo.jpg" class="logo" alt="Imbo't Silog">
    <div class="nav-links">
        <a href="home.php" class="<?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active' : '' ?>">🏠 Home</a>
        <a href="register.php" class="<?= basename($_SERVER['PHP_SELF']) === 'register.php' ? 'active' : '' ?>">📝 Register</a>
    </div>
    <button class="menu-btn" onclick="toggleSidebar()">☰</button>
</div>
<div class="auth-card">
  <h2>Imbo't Silog</h2>
  <div class="sub">Welcome Back</div>
  <?php foreach($errors as $e) echo '<div class="err">'.htmlspecialchars($e).'</div>'; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <input class="field" name="email" type="email" placeholder="Email" required value="<?php echo htmlspecialchars($_POST['email']??''); ?>">
    <input class="field" name="password" type="password" placeholder="Password" required>
    <button class="btn" type="submit">Login</button>
  </form>
  <p style="color:#bbb;text-align:center;margin-top:12px;">Don't have an account? <a class="link" href="register.php">Create Account</a></p>
</div>
</body>
</html>
