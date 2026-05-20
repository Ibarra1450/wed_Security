<?php
require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: dashboard_Admin.php');
    } else {
        header('Location: dashboard_User.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken($_POST['csrf_token'] ?? '');

    $email    = sanitizeInput($_POST['email']);
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName  = sanitizeInput($_POST['last_name'] ?? '');
    $middleInitial = strtoupper(sanitizeInput($_POST['middle_initial'] ?? ''));
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $accountType = $_POST['account_type'] ?? 'user'; // 'user' or 'admin'

    // Basic validation
    $errors = [];

    if (empty($firstName)) {
        $errors[] = 'First Name is required.';
    } elseif (preg_match('/\d/', $firstName)) {
        $errors[] = 'Numbers are not allowed in First Name.';
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $firstName)) {
        $errors[] = 'First Name can only contain letters, spaces, hyphens, and apostrophes.';
    }

    if (empty($lastName)) {
        $errors[] = 'Last Name is required.';
    } elseif (preg_match('/\d/', $lastName)) {
        $errors[] = 'Numbers are not allowed in Last Name.';
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $lastName)) {
        $errors[] = 'Last Name can only contain letters, spaces, hyphens, and apostrophes.';
    }

    if (!empty($middleInitial)) {
        if (preg_match('/\d/', $middleInitial)) {
            $errors[] = 'Numbers are not allowed in Middle Initial.';
        } elseif (!preg_match("/^[A-Z]{1}\.?$/", $middleInitial)) {
            $errors[] = 'Middle Initial must be a single letter (optionally with a period).';
        }
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    } elseif (!preg_match('/@ntc\.edu\.ph$/', $email)) {
        $errors[] = 'Only @ntc.edu.ph email addresses are allowed for registration.';
    }
    
    // PHP Validation: Updated to 10 minimum and 23 maximum length
    $passwordLength = strlen($password);
    if ($passwordLength < 10 || $passwordLength > 23) {
        $errors[] = 'Password must be between 10 and 23 characters long.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d])/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
    }
    
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Step 1: Decide which table to use
        $table = ($accountType === 'admin') ? 'admins' : 'users';

        // Step 2: Check if email already exists in that specific table
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE email = ?");
        if ($stmt->execute([$email]) && $stmt->fetch()) {
            $errors[] = 'This email address is already registered.';
        } else {
            // Step 3: Hash password and insert into the chosen table
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO $table (email, first_name, last_name, middle_initial, password) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$email, $firstName, $lastName, $middleInitial, $hashed])) {
                logActivity($pdo->lastInsertId(), $accountType, 'registration');
                setFlashMessage(ucfirst($accountType) . ' registration successful! You can now log in.', 'success');
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }

    // If errors, store them in session to display in pop-up
    if (!empty($errors)) {
        $_SESSION['message'] = implode('<br>', $errors);
        $_SESSION['message_type'] = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Secure System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>

<div class="dashboard-wrapper">

    <!-- Left Sidebar -->
    <div class="dashboard-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">WEBSECURE</div>
        </div>
        
        <div class="sidebar-content">
            <img src="https://i.pinimg.com/736x/83/56/55/835655dfe773e2ef81d1878777d24962.jpg" alt="Network Security" class="sidebar-image">
            <h2 class="sidebar-tagline">Securing Connections, Protecting Identity</h2>
        </div>
        
        <div class="sidebar-footer">
            <a href="#" class="back-link">Back to website</a>
        </div>
    </div>

    <!-- Right Form Area -->
    <div class="dashboard-header">
        <div class="dashboard-header-content">
            <h1>Create an account</h1>
            <p>Join us by registering your academic account.</p>
        </div>

        <div class="card register-card">
            
            <?php if (isset($_SESSION['message']) && $_SESSION['message_type'] === 'error'): ?>
            <div class="error-message">
                <?= $_SESSION['message'] ?>
            </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" name="first_name" id="first_name" placeholder="Enter your first name" required 
                               pattern="^[a-zA-Z\s'-]+$" title="First name must only contain letters, spaces, hyphens, and apostrophes. Numbers are not allowed.">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" name="last_name" id="last_name" placeholder="Enter your last name" required 
                               pattern="^[a-zA-Z\s'-]+$" title="Last name must only contain letters, spaces, hyphens, and apostrophes. Numbers are not allowed.">
                    </div>

                    <div class="form-group">
                        <label for="middle_initial">Middle Initial</label>
                        <input type="text" name="middle_initial" id="middle_initial" placeholder="E.g. A or A." maxlength="2"
                               pattern="^[a-zA-Z]?\.?$" title="Single letter (optionally followed by a dot). Numbers are not allowed.">
                    </div>


                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" placeholder="Enter your password" required 
                               minlength="10" maxlength="23"
                               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{10,23}" 
                               title="Must contain at least one uppercase, one lowercase, one number, one special character, and be 10-23 characters">
                        <small id="password-static-desc">Between 10 to 23 characters with uppercase, lowercase, number, and special character</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" minlength="10" maxlength="23" required>
                    </div>

                    <div class="form-group">
                        <label for="account_type">Register As</label>
                        <select name="account_type" id="account_type" required>
                            <option value="user">Standard User</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="action-btn">Create Account</button>
                </form>

                <div class="footer-text">
                    Already have an account? <a href="index.php">Log in</a>
                </div>
            </div>
        </div>
    </div>

</div>

<div id="chocolateBar" class="chocolate-bar hidden"></div>

<?php if (isset($_SESSION['message'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof showChocolateBar === 'function') {
        showChocolateBar(<?= json_encode($_SESSION['message']) ?>, <?= json_encode($_SESSION['message_type'] ?? 'success') ?>);
    }
});
</script>
<?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
<?php endif; ?>

<script src="main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const accountTypeSelect = document.getElementById('account_type');
    
    const staticPassDesc = document.getElementById('password-static-desc');
    
    function createHelperText(input, id) {
        let helper = document.getElementById(id);
        if (!helper) {
            helper = document.createElement('small');
            helper.id = id;
            input.parentNode.appendChild(helper);
        }
        return helper;
    }
    
    const emailHelper = createHelperText(emailInput, 'email-helper');
    const passwordHelper = createHelperText(passwordInput, 'password-helper');
    const confirmHelper = createHelperText(confirmPasswordInput, 'confirm-helper');
    
    emailHelper.style.display = 'none';
    passwordHelper.style.display = 'none';
    confirmHelper.style.display = 'none';

    function checkExists(type, value, accountType) {
        if (!value) return;
        
        const helper = emailHelper;
        
        fetch('check_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `type=${encodeURIComponent(type)}&value=${encodeURIComponent(value)}&account_type=${encodeURIComponent(accountType)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                helper.textContent = 'This email address is already registered.';
                helper.className = 'error-hint';
                helper.style.display = 'block';
            } else {
                helper.textContent = 'Email is available';
                helper.className = 'success-hint';
                helper.style.display = 'block';
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // JavaScript Validation: Updated length verification boundaries to 10 and 23
    function validatePassword() {
        const password = passwordInput.value;
        const confirm = confirmPasswordInput.value;
        
        if (password.length > 0) {
            staticPassDesc.style.display = 'none';
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            const hasSpecial = /[^a-zA-Z\d]/.test(password);
            const isValidLength = password.length >= 10 && password.length <= 23;
            
            if (!isValidLength || !hasUpperCase || !hasLowerCase || !hasNumbers || !hasSpecial) {
                passwordHelper.textContent = 'Password must be between 10 and 23 characters with uppercase, lowercase, number, and special character';
                passwordHelper.className = 'error-hint';
                passwordHelper.style.display = 'block';
            } else {
                passwordHelper.textContent = 'Password strength is good';
                passwordHelper.className = 'success-hint';
                passwordHelper.style.display = 'block';
            }
        } else {
            passwordHelper.style.display = 'none';
            staticPassDesc.style.display = 'block';
        }
        
        if (confirm.length > 0) {
            if (password !== confirm) {
                confirmHelper.textContent = 'Passwords do not match';
                confirmHelper.className = 'error-hint';
                confirmHelper.style.display = 'block';
            } else {
                confirmHelper.textContent = 'Passwords match';
                confirmHelper.className = 'success-hint';
                confirmHelper.style.display = 'block';
            }
        } else {
            confirmHelper.style.display = 'none';
        }
    }

    emailInput.addEventListener('blur', function() {
        const accountType = accountTypeSelect.value;
        const email = this.value;
        const emailPattern = /@ntc\.edu\.ph$/;
        
        if (email.length > 0) {
            if (!filter_var(email, FILTER_VALIDATE_EMAIL)) {
                emailHelper.textContent = 'Invalid email address';
                emailHelper.className = 'error-hint';
                emailHelper.style.display = 'block';
            } else if (!emailPattern.test(email)) {
                emailHelper.textContent = 'Only @ntc.edu.ph email addresses are allowed';
                emailHelper.className = 'error-hint';
                emailHelper.style.display = 'block';
            } else {
                checkExists('email', email, accountType);
            }
        } else {
            emailHelper.style.display = 'none';
        }
    });

    passwordInput.addEventListener('input', validatePassword);
    confirmPasswordInput.addEventListener('input', validatePassword);

    accountTypeSelect.addEventListener('change', function() {
        if (emailInput.value) {
            checkExists('email', emailInput.value, this.value);
        }
    });
    
    function filter_var(email, type) {
        if (type === FILTER_VALIDATE_EMAIL) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        return false;
    }
    
    const FILTER_VALIDATE_EMAIL = 274;
});
</script>
</body>
</html>