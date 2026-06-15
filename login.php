<?php

require_once __DIR__ . '/includes/config.php';


if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';
$redirect = htmlspecialchars($_GET['redirect'] ?? BASE_URL . 'index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, full_name, email, role FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id'        => $user['id'],
                'username'  => $user['username'],
                'full_name' => $user['full_name'],
                'email'     => $user['email'],
                'role'      => $user['role'],
            ];
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}

$pageTitle  = 'Sign In';
$currentPage = '';
require_once __DIR__ . '/includes/header.php';
?>

<main class="section-gap section-cream d-flex align-items-center justify-content-center" style="min-height:calc(100vh - 80px);">
    <div class="section-container w-100">
        <div class="login-card mx-auto reveal">

            <!-- Logo / Brand -->
            <div class="text-center mb-5">
                <div class="gold-line mx-auto"></div>
                <p class="elysian-label-sm text-gold mb-2">WELCOME BACK</p>
                <h1 class="elysian-headline-md">Sign In</h1>
                <p class="elysian-body-sm text-muted-soft mt-2">Access your Elysian Reserve account</p>
            </div>

            <!-- Error Alert -->
            <?php if ($error): ?>
            <div class="alert-elysian error mb-4" role="alert" id="loginError">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:8px;">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="loginForm" method="POST" action="" novalidate>
                <input type="hidden" name="redirect" value="<?= $redirect ?>">

                <div class="elysian-form-group">
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="elysian-form-control"
                        placeholder="Enter username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        data-validate="required"
                        data-error-msg="Username is required."
                    >
                    <label for="username" class="elysian-form-label">Username</label>
                </div>

                <div class="elysian-form-group">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="elysian-form-control"
                        placeholder="Enter password"
                        autocomplete="current-password"
                        data-validate="required"
                        data-error-msg="Password is required."
                    >
                    <label for="password" class="elysian-form-label">Password</label>
                </div>

                <button type="submit" class="btn-elysian-primary w-full mt-4" id="loginBtn">
                    Sign In
                </button>
            </form>

            <!-- Demo credentials hint -->
            <div class="mt-5 pt-4 border-top">
                <p class="elysian-label-sm text-muted-soft mb-3 text-center">DEMO CREDENTIALS</p>
                <div class="row g-2">
                    <div class="col-6">
                        <div style="background:var(--color-cream-low);padding:14px 16px;border:1px solid var(--color-cream-high);">
                            <p class="elysian-label-sm text-gold mb-1">ADMIN</p>
                            <p class="elysian-body-sm mb-0">admin / Password123!</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:var(--color-cream-low);padding:14px 16px;border:1px solid var(--color-cream-high);">
                            <p class="elysian-label-sm text-gold mb-1">GUEST</p>
                            <p class="elysian-body-sm mb-0">evan / Password123!</p>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /login-card -->
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
