<?php
session_start();
require_once('config.php');
$errors = [];
$old_data = [];
if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $phone_raw = $_POST['phone'] ?? '';
    $phone_digits = preg_replace('/\D+/', '', $phone_raw);
    $phone = mysqli_real_escape_string($connection, $phone_digits);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms = isset($_POST['terms']);
    $user_type = 'Customer';
    if (empty($name) || empty($phone) || empty($password) || empty($confirm_password)) { $errors[] = "All fields are required"; }
    if (!preg_match('/^[0-9]{6,10}$/', $phone_digits)) { $errors[] = "Please enter a valid phone number (6-10 digits)"; }
    if ($password !== $confirm_password) { $errors[] = "Passwords do not match"; }
    if (strlen($password) < 6) { $errors[] = "Password must be at least 6 characters long"; }
    if (!$terms) { $errors[] = "You must agree to the Terms of Service and Privacy Policy"; }
    $old_data = ['name' => $name, 'phone' => $phone_digits];
    if (empty($errors)) {
        $check_sql = "SELECT user_id FROM user WHERE phone = '$phone'";
        $check_res = mysqli_query($connection, $check_sql);
        
        if (!$check_res) {
            error_log("Database error in signup check: " . mysqli_error($connection));
            $errors[] = "System error. Please try again later.";
        } elseif (mysqli_num_rows($check_res) > 0) {
            $errors[] = "Phone number is already registered";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO user (name, phone, password, user_type, is_active) VALUES ('$name', '$phone', '$hashed', '$user_type', 1)";
            if (mysqli_query($connection, $insert_sql)) {
                $user_id = mysqli_insert_id($connection);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['name'] = $name;
                $_SESSION['user_type'] = $user_type;
                $_SESSION['phone'] = $phone;
                $_SESSION['logged_in'] = true;
                header("Location: customer_dashboard.php");
                exit();
            } else {
                $errors[] = "Error creating account";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Kaah Fast Food</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #fff7f2;
            --surface: #ffffff;
            --text: #1f1f29;
            --muted: #6b6b80;
            --primary: #ff5a1f;
            --primary-dark: #dd4712;
            --line: #ece8e3;
            --danger-bg: #ffe8e8;
            --danger-text: #a22929;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Inter", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(160deg, #fff7f2 0%, #fff 45%, #fff 100%);
            color: var(--text);
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
        }
        .auth-shell {
            width: min(1080px, 100%);
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 20px 48px rgba(30, 30, 42, 0.12);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        .auth-side {
            padding: 34px;
            background: linear-gradient(145deg, #ff5a1f 0%, #ff7d37 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 680px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 1.1rem;
        }
        .side-copy h2 {
            font-size: clamp(1.7rem, 2.6vw, 2.3rem);
            line-height: 1.15;
            margin-bottom: 12px;
        }
        .side-copy p { opacity: 0.95; max-width: 420px; }
        .side-points {
            list-style: none;
            margin-top: 18px;
        }
        .side-points li {
            margin-bottom: 10px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .location-chip {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.35);
            padding: 8px 12px;
            border-radius: 999px;
            width: fit-content;
            font-size: .9rem;
        }
        .auth-main {
            padding: 34px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .go-back {
            text-decoration: none;
            color: var(--muted);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 9px 12px;
            font-weight: 600;
            display: inline-flex;
            gap: 8px;
            align-items: center;
        }
        .go-back:hover { color: var(--text); border-color: #dad4cd; }
        .auth-main h1 {
            font-size: clamp(1.45rem, 2.2vw, 1.9rem);
            margin-bottom: 6px;
        }
        .subtitle { color: var(--muted); margin-bottom: 22px; }
        .error-alert {
            background: var(--danger-bg);
            color: var(--danger-text);
            border: 1px solid #ffcaca;
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 16px;
            font-size: .93rem;
        }
        .error-alert ul { margin-left: 16px; }
        .form-group { margin-bottom: 14px; }
        label {
            display: block;
            font-size: .9rem;
            font-weight: 700;
            margin-bottom: 7px;
        }
        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9a9aac;
        }
        .form-control {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px 44px 12px 38px;
            font-size: .96rem;
            color: var(--text);
            background: #fff;
        }
        .form-control:focus {
            outline: none;
            border-color: #ffb899;
            box-shadow: 0 0 0 3px #fff0e8;
        }
        .plain-input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: .96rem;
            color: #85859b;
            background: #fafafa;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #8d8da2;
            cursor: pointer;
            width: 28px;
            height: 28px;
        }
        .hint {
            display: block;
            color: var(--muted);
            font-size: .82rem;
            margin-top: 6px;
        }
        .terms {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            color: var(--muted);
            font-size: .9rem;
            margin: 6px 0 16px;
        }
        .terms input { accent-color: var(--primary); margin-top: 3px; }
        .terms a { color: var(--primary-dark); text-decoration: none; }
        .btn-primary {
            width: 100%;
            border: none;
            border-radius: 12px;
            background: var(--primary);
            color: #fff;
            padding: 12px 16px;
            font-weight: 800;
            font-size: .97rem;
            display: inline-flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: .2s ease;
        }
        .btn-primary:hover { background: var(--primary-dark); }
        .auth-footer {
            margin-top: 16px;
            color: var(--muted);
            text-align: center;
            font-size: .94rem;
        }
        .auth-footer a { color: var(--primary-dark); font-weight: 700; text-decoration: none; }

        @media (max-width: 900px) {
            .auth-shell { grid-template-columns: 1fr; }
            .auth-side { min-height: 300px; padding: 26px; }
            .auth-main { padding: 26px; }
        }
        @media (max-width: 560px) {
            body { padding: 12px; }
            .auth-main, .auth-side { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <aside class="auth-side">
            <div class="brand">
                <i class="fas fa-utensils"></i>
                <span>Kaah Fast Food</span>
            </div>
            <div class="side-copy">
                <h2>Create your account and order in minutes.</h2>
                <p>Join Kaah Fast Food today and enjoy a faster ordering experience with full access to your dashboard.</p>
                <ul class="side-points">
                    <li><i class="fas fa-check-circle"></i><span>Quick registration</span></li>
                    <li><i class="fas fa-check-circle"></i><span>Track orders in real-time</span></li>
                    <li><i class="fas fa-check-circle"></i><span>Built for customers in New Hargeisa</span></li>
                </ul>
            </div>
            <div class="location-chip">
                <i class="fas fa-map-marker-alt"></i>
                <span>New Hargeisa</span>
            </div>
        </aside>

        <main class="auth-main">
            <div class="top-actions">
                <a href="../index.php" class="go-back">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </div>

            <h1>Create your account</h1>
            <p class="subtitle">Fill in your details to register with Kaah Fast Food.</p>

            <?php if(!empty($errors)): ?>
                <div class="error-alert">
                    <ul>
                        <?php foreach($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-wrap">
                        <i class="fas fa-user"></i>
                        <input class="form-control" type="text" id="name" name="name"
                               placeholder="Enter your full name"
                               value="<?php echo isset($old_data['name']) ? htmlspecialchars($old_data['name']) : ''; ?>"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-wrap">
                        <i class="fas fa-phone"></i>
                        <input class="form-control" type="tel" id="phone" name="phone"
                               placeholder="Enter phone (6-10 digits)"
                               value="<?php echo isset($old_data['phone']) ? htmlspecialchars($old_data['phone']) : ''; ?>"
                               pattern="[0-9]{6,10}" minlength="6" maxlength="10" required>
                    </div>
                    <small class="hint">Use digits only. This will be your login phone.</small>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock"></i>
                        <input class="form-control" type="password" id="password" name="password"
                               placeholder="Create password (min 6 chars)" required>
                        <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock"></i>
                        <input class="form-control" type="password" id="confirm_password" name="confirm_password"
                               placeholder="Re-enter your password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Account Type</label>
                    <input class="plain-input" type="text" value="Customer" readonly>
                    <input type="hidden" name="user_type" value="Customer">
                </div>

                <div class="terms">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</label>
                </div>

                <button type="submit" name="submit" class="btn-primary">
                    <i class="fas fa-user-plus"></i>
                    <span>Create Account</span>
                </button>
            </form>

            <div class="auth-footer">
                Already registered? <a href="login.php">Login here</a>
            </div>
        </main>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            });
        });

        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        
        function checkPasswordMatch() {
            if (passwordInput.value && confirmInput.value) {
                if (passwordInput.value !== confirmInput.value) {
                    confirmInput.style.borderColor = '#e74c3c';
                } else {
                    confirmInput.style.borderColor = '#27ae60';
                }
            }
        }
        
        passwordInput.addEventListener('input', checkPasswordMatch);
        confirmInput.addEventListener('input', checkPasswordMatch);
        
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            if(!terms) {
                e.preventDefault();
                alert('You must agree to the Terms of Service and Privacy Policy');
                return false;
            }
            
            if(password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if(password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return false;
            }
        });
    </script>
</body>
</html>
