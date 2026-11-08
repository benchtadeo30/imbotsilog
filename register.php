<?php include 'auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Imbo't Silog</title>
  <link rel="stylesheet" href="home.css">
  <style>
    .auth-card { max-width: 520px; margin: 40px auto; background:#1a1a1a; border:2px solid orange; border-radius:20px; padding:28px; }
    .auth-card h2 { color:#ff7a00; text-align:center; margin:0 0 8px; }
    .auth-card .sub { color:#bbb; text-align:center; margin-bottom:18px; }
    .field { width:100%; box-sizing:border-box; padding:10px; border-radius:10px; border:1px solid #444; background:#0f0f0f; color:#fff; font-family:'Inter',Arial,sans-serif; margin:6px 0 12px; }
    .btn { width:100%; box-sizing:border-box; background:#ff7a00; color:#000; border:none; font-weight:bold; padding:12px; border-radius:10px; cursor:pointer; font-family:'Inter',Arial,sans-serif; }
    .link { color:#ff7a00; text-decoration:none; }
    .err { color:#f87171; font-size:13px; margin-bottom:10px; }
    .ok { color:#4ade80; font-size:13px; margin-bottom:10px; }
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
        <a href="login.php" class="<?= basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : '' ?>">🔐 Login</a>
    </div>
    <button class="menu-btn" onclick="toggleSidebar()">☰</button>
</div>
<div class="auth-card">
  <h2>Imbo't Silog</h2>
  <div class="sub">Create an Account</div>
  <?php
  $errors=[]; $ok='';
  if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verify_csrf()) $errors[]='Invalid request.';
    $first=trim($_POST['first_name']??'');
    $last=trim($_POST['last_name']??'');
    $email=trim($_POST['email']??'');
    $phone=trim($_POST['phone']??'');
    $pass=$_POST['password']??'';
    $confirm=$_POST['confirm_password']??'';
    $adminCode=trim($_POST['admin_code']??'');
    if ($first===''||$last===''||$email===''||$pass==='') $errors[]='All required fields are required.';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Invalid email format.';
    if ($pass!==$confirm) $errors[]='Passwords do not match.';
    if (strlen($pass)<6) $errors[]='Password must be at least 6 characters.';
    if (!$errors) {
      $conn=new mysqli('localhost','root','','imbotsilog_db');
      $stmt=$conn->prepare('SELECT user_id FROM users WHERE email=?');
      $stmt->bind_param('s',$email); $stmt->execute(); $stmt->store_result();
      if ($stmt->num_rows>0) $errors[]='Email already registered.';
      else {
        $hash=password_hash($pass,PASSWORD_DEFAULT);
        $role = ($adminCode !== '' && $adminCode === 'imbotsilog-admin-2026') ? 'admin' : 'customer';
        $ins=$conn->prepare('INSERT INTO users (first_name,last_name,email,phone,password_hash,role) VALUES (?,?,?,?,?,?)');
        $ins->bind_param('ssssss',$first,$last,$email,$phone,$hash,$role);
        if ($ins->execute()) { $ok='Account created. You may now login.'; }
        else $errors[]='Registration failed.';
      }
    }
  }
  foreach($errors as $e) echo '<div class="err">'.htmlspecialchars($e).'</div>';
  if($ok) echo '<div class="ok">'.htmlspecialchars($ok).'</div>';
  ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <input class="field" name="first_name" placeholder="First Name" required value="<?php echo htmlspecialchars($_POST['first_name']??''); ?>">
    <input class="field" name="last_name" placeholder="Last Name" required value="<?php echo htmlspecialchars($_POST['last_name']??''); ?>">
    <input class="field" name="email" type="email" placeholder="Email" required value="<?php echo htmlspecialchars($_POST['email']??''); ?>">
    <input class="field" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($_POST['phone']??''); ?>">
    <input class="field" name="admin_code" placeholder="Admin Code (optional, for admin accounts)" value="<?php echo htmlspecialchars($_POST['admin_code']??''); ?>">
    <input class="field" name="password" type="password" placeholder="Password" required>
    <input class="field" name="confirm_password" type="password" placeholder="Confirm Password" required>
    <button class="btn" type="submit">Create Account</button>
  </form>
  <p style="color:#bbb;text-align:center;margin-top:12px;">Already have an account? <a class="link" href="login.php">Login</a></p>
</div>
</body>
</html>
