<?php
require_once __DIR__ . '/config.php';

$error = '';
$backgroundUrl = '';
$referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
$refererParts = $referer !== '' ? parse_url($referer) : false;
$host = (string)($_SERVER['HTTP_HOST'] ?? '');
$basePath = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/logout.php'))), '/');
if ($basePath === '.') $basePath = '';
if (is_array($refererParts)
    && isset($refererParts['scheme'], $refererParts['host'], $refererParts['path'])
    && in_array(strtolower($refererParts['scheme']), ['http', 'https'], true)
    && strcasecmp($refererParts['host'], preg_replace('/:\d+$/', '', $host)) === 0
    && ($basePath === '' || $refererParts['path'] === $basePath || strpos($refererParts['path'], $basePath . '/') === 0)
    && $refererParts['path'] !== $basePath . '/logout.php'
    && strpos($refererParts['path'], '\\') === false
    && !preg_match('~(?:^|/)\.\.(?:/|$)~', $refererParts['path'])) {
    $backgroundUrl = $refererParts['path']
        . (isset($refererParts['query']) ? '?' . $refererParts['query'] : '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $backgroundUrl = (string)($_SESSION['logout_background'] ?? $backgroundUrl);
    $token = (string)($_POST['csrf'] ?? '');
    if (empty($_SESSION['logout_csrf']) || !hash_equals($_SESSION['logout_csrf'], $token)) {
        http_response_code(403);
        $_SESSION['logout_csrf'] = bin2hex(random_bytes(32));
        $error = 'This confirmation expired or is invalid. Please try again.';
    } else {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php');
        exit;
    }
} else {
    $_SESSION['logout_csrf'] = bin2hex(random_bytes(32));
    $_SESSION['logout_background'] = $backgroundUrl;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Logout | SK KK Profiling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="sidebar.css" rel="stylesheet">
    <style>
        body{
            position:relative;
            isolation:isolate;
            background:var(--bs-tertiary-bg);
        }
        body::before{
            content:"";
            position:fixed;
            inset:0;
            z-index:0;
            background:
                radial-gradient(ellipse at 18% 24%, rgba(var(--bs-primary-rgb),.12), transparent 48%),
                radial-gradient(ellipse at 82% 78%, rgba(var(--bs-primary-rgb),.08), transparent 44%),
                linear-gradient(135deg, var(--bs-tertiary-bg), var(--bs-secondary-bg));
            pointer-events:none;
        }
        main{
            position:relative;
            z-index:1;
        }
        .logout-header{
            background:var(--bs-primary);
            border-bottom:0;
            border-radius:22px 22px 0 0;
        }
        .logout-background,
        .logout-backdrop{
            position:fixed;
            inset:0;
            width:100%;
            height:100%;
        }
        .logout-background{
            z-index:0;
            border:0;
            pointer-events:none;
        }
        .logout-backdrop{
            z-index:1;
            background:rgba(20,24,30,.32);
        }
        .logout-dialog{
            position:relative;
            z-index:2;
            width:calc(100% - 1rem);
            max-width:500px;
            margin:1.75rem auto;
        }
        .logout-content{
            border:0;
            border-radius:22px;
            background-color:#fff;
            box-shadow:0 1rem 3rem rgba(0,0,0,.18);
            opacity:1;
        }
        .logout-footer{
            border-top:1px solid var(--bs-border-color);
            border-radius:0 0 22px 22px;
        }
        <?php if ($backgroundUrl === ''): ?>
        body{
            background:
                radial-gradient(ellipse at 18% 24%, rgba(var(--bs-primary-rgb),.12), transparent 48%),
                radial-gradient(ellipse at 82% 78%, rgba(var(--bs-primary-rgb),.08), transparent 44%),
                linear-gradient(135deg, var(--bs-tertiary-bg), var(--bs-secondary-bg)) !important;
        }
        <?php endif; ?>
    </style>
</head>
<body class="min-vh-100 d-flex align-items-center">
    <?php if ($backgroundUrl !== ''): ?>
        <iframe class="logout-background" src="<?= htmlspecialchars($backgroundUrl, ENT_QUOTES, 'UTF-8') ?>" title="Previous page"></iframe>
        <div class="logout-backdrop" aria-hidden="true"></div>
    <?php endif; ?>
    <main class="container py-4">
        <div class="logout-dialog">
            <div class="modal-content logout-content">
                <div class="modal-header logout-header px-4 py-3">
                    <h1 class="modal-title fs-5 fw-bold mb-0">Confirm Logout</h1>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-0">Are you sure you want to log out?</p>
                </div>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger mx-4 mb-0" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="post" class="modal-footer logout-footer px-4 py-3">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['logout_csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" class="btn btn-outline-secondary" id="cancelLogout">Cancel</button>
                    <button type="submit" class="btn btn-danger">Log out</button>
                </form>
            </div>
        </div>
    </main>
    <script>
        document.getElementById('cancelLogout').addEventListener('click', function () {
            if (window.history.length > 1) window.history.back();
            else window.location.href = 'dashboard.php';
        });
    </script>
</body>
</html>
