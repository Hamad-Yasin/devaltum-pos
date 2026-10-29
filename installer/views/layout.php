<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer - DevAltum POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; font-family:'Manrope', sans-serif; }
        body {
            min-height:100vh;
            display:grid;
            place-items:center;
            padding:24px;
            background:
                radial-gradient(circle at top right, rgba(15,109,255,.15), transparent 30%),
                radial-gradient(circle at bottom left, rgba(245,158,11,.16), transparent 25%),
                #f3f6fb;
            color:#0f172a;
        }
        .installer-shell {
            width:min(980px, 100%);
            display:grid;
            grid-template-columns: 1.1fr 1fr;
            background:#fff;
            border:1px solid #dbe4f0;
            border-radius:30px;
            overflow:hidden;
            box-shadow:0 22px 60px rgba(15,23,42,.14);
        }
        .installer-side {
            padding:38px;
            background:linear-gradient(180deg, #0f172a 0%, #102249 100%);
            color:#fff;
        }
        .installer-main {
            padding:38px;
        }
        .badge {
            display:inline-block;
            background:rgba(255,255,255,.12);
            padding:8px 12px;
            border-radius:999px;
            font-size:12px;
            margin-bottom:18px;
        }
        h1 { font-size:34px; line-height:1.1; margin-bottom:14px; }
        .muted { color:#64748b; }
        .side-muted { color:rgba(255,255,255,.72); }
        .feature {
            padding:14px 0;
            border-top:1px solid rgba(255,255,255,.1);
            font-size:14px;
        }
        .card {
            background:#fff;
            border:1px solid #dbe4f0;
            border-radius:22px;
            padding:24px;
        }
        .field { margin-bottom:16px; }
        label { display:block; margin-bottom:8px; font-size:14px; font-weight:700; }
        input {
            width:100%;
            padding:14px 16px;
            border:1px solid #dbe4f0;
            border-radius:16px;
            font:inherit;
        }
        input:focus {
            outline:none;
            border-color:#0f6dff;
            box-shadow:0 0 0 4px rgba(15,109,255,.12);
        }
        .btn {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            border:none;
            border-radius:16px;
            padding:14px 18px;
            cursor:pointer;
            text-decoration:none;
            font-weight:700;
        }
        .btn-primary { background:linear-gradient(135deg, #0f6dff, #2b87ff); color:#fff; }
        .btn-secondary { background:#fff; color:#0f172a; border:1px solid #dbe4f0; }
        .alert {
            background:#fee2e2;
            border:1px solid #fecaca;
            color:#dc2626;
            padding:12px 14px;
            border-radius:14px;
            margin-bottom:18px;
        }
        .req-list { display:flex; flex-direction:column; gap:10px; }
        .req-item {
            display:flex;
            justify-content:space-between;
            gap:12px;
            padding:12px 14px;
            border:1px solid #dbe4f0;
            border-radius:14px;
        }
        .ok { color:#059669; font-weight:700; }
        .fail { color:#dc2626; font-weight:700; }
        @media (max-width:860px) {
            .installer-shell { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
    <div class="installer-shell">
        <aside class="installer-side">
            <div class="badge">Fast Hosting Installer</div>
            <h1>DevAltum POS</h1>
            <p class="side-muted" style="margin-bottom:26px;">Premium 4-step setup for shared hosting, VPS, and local server deployment.</p>
            <div class="feature"><strong>1.</strong> Validating Product License</div>
            <div class="feature"><strong>2.</strong> Server requirements check</div>
            <div class="feature"><strong>3.</strong> Database + config setup</div>
            <div class="feature"><strong>4.</strong> Admin account creation</div>
            <div class="feature"><strong>Bonus.</strong> Ready-to-login immediately!</div>
        </aside>
        <main class="installer-main">
            <?php echo $content; ?>
        </main>
    </div>
</body>
</html>
