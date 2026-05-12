<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$loggedIn = isset($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MDM System — Mobile Device Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  :root {
    --primary: #1a2e4a;
    --accent:  #4f8ef7;
    --accent2: #7c3aed;
    --grad: linear-gradient(135deg, #1a2e4a 0%, #0f1f35 60%, #0d1b2e 100%);
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  html { scroll-behavior: smooth; }

  body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: #060e1a;
    color: #e2e8f0;
    overflow-x: hidden;
  }

  /* ── Navbar ─────────────────────────────────────────────── */
  .lp-nav {
    position: fixed; top: 0; left: 0; right: 0; z-index: 999;
    padding: 18px 40px;
    display: flex; align-items: center; justify-content: space-between;
    transition: background 0.4s, backdrop-filter 0.4s, padding 0.3s;
  }
  .lp-nav.scrolled {
    background: rgba(6, 14, 26, 0.85);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255,255,255,0.06);
    padding: 12px 40px;
  }
  .nav-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
  .nav-logo-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; color: #fff;
  }
  .nav-logo-text { font-size: 1rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
  .nav-logo-sub  { font-size: 0.65rem; color: rgba(255,255,255,0.45); letter-spacing: 0.06em; text-transform: uppercase; }
  .nav-cta { display: flex; gap: 12px; align-items: center; }
  .btn-nav-ghost {
    color: rgba(255,255,255,0.7); background: transparent;
    border: 1px solid rgba(255,255,255,0.15);
    padding: 8px 20px; border-radius: 8px; font-size: 0.85rem; font-weight: 500;
    text-decoration: none; transition: all 0.2s;
  }
  .btn-nav-ghost:hover { background: rgba(255,255,255,0.08); color: #fff; border-color: rgba(255,255,255,0.3); }
  .btn-nav-solid {
    background: var(--accent); color: #fff;
    padding: 8px 22px; border-radius: 8px; font-size: 0.85rem; font-weight: 600;
    text-decoration: none; transition: all 0.2s;
    box-shadow: 0 4px 14px rgba(79,142,247,0.35);
  }
  .btn-nav-solid:hover { background: #3a7de8; box-shadow: 0 6px 20px rgba(79,142,247,0.5); color: #fff; transform: translateY(-1px); }

  /* ── Hero ─────────────────────────────────────────────────── */
  .hero {
    min-height: 100vh;
    display: flex; align-items: center;
    position: relative; overflow: hidden;
    padding: 120px 40px 80px;
  }

  /* animated dot-grid background */
  .hero-bg {
    position: absolute; inset: 0; z-index: 0;
    background:
      radial-gradient(ellipse 80% 60% at 60% 40%, rgba(79,142,247,0.12) 0%, transparent 60%),
      radial-gradient(ellipse 60% 50% at 20% 70%, rgba(124,58,237,0.1) 0%, transparent 55%),
      #060e1a;
  }
  .hero-bg::before {
    content: '';
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.045) 1px, transparent 1px);
    background-size: 32px 32px;
    animation: bgShift 20s linear infinite;
  }
  @keyframes bgShift { to { background-position: 32px 32px; } }

  /* floating orbs */
  .orb {
    position: absolute; border-radius: 50%;
    filter: blur(60px); pointer-events: none; z-index: 0;
    animation: orbFloat 8s ease-in-out infinite;
  }
  .orb-1 { width: 500px; height: 500px; background: rgba(79,142,247,0.12); top: -100px; right: -100px; animation-delay: 0s; }
  .orb-2 { width: 350px; height: 350px; background: rgba(124,58,237,0.1); bottom: 50px; left: -80px; animation-delay: -3s; }
  .orb-3 { width: 250px; height: 250px; background: rgba(16,185,129,0.07); top: 40%; left: 40%; animation-delay: -6s; }
  @keyframes orbFloat {
    0%,100% { transform: translateY(0px) scale(1); }
    50%      { transform: translateY(-30px) scale(1.05); }
  }

  .hero-content { position: relative; z-index: 1; max-width: 620px; }

  .hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(79,142,247,0.12); border: 1px solid rgba(79,142,247,0.3);
    color: #7eb8ff; padding: 6px 16px; border-radius: 50px;
    font-size: 0.78rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase;
    margin-bottom: 28px;
    animation: fadeSlideDown 0.8s ease both;
  }
  .hero-badge .dot {
    width: 6px; height: 6px; border-radius: 50%; background: #4f8ef7;
    animation: pulse 2s ease-in-out infinite;
  }
  @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.5;transform:scale(1.4)} }

  .hero-title {
    font-size: clamp(2.4rem, 5vw, 3.8rem);
    font-weight: 800; line-height: 1.1; letter-spacing: -0.03em;
    margin-bottom: 22px;
    animation: fadeSlideDown 0.8s ease 0.15s both;
  }
  .hero-title .grad-text {
    background: linear-gradient(135deg, #4f8ef7, #a78bfa);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  }

  .hero-desc {
    font-size: 1.08rem; color: rgba(255,255,255,0.55); line-height: 1.75;
    margin-bottom: 36px; max-width: 520px;
    animation: fadeSlideDown 0.8s ease 0.3s both;
  }

  .hero-actions {
    display: flex; gap: 14px; flex-wrap: wrap;
    animation: fadeSlideDown 0.8s ease 0.45s both;
  }
  .btn-hero-primary {
    background: var(--accent); color: #fff;
    padding: 14px 32px; border-radius: 10px; font-size: 0.95rem; font-weight: 700;
    text-decoration: none; transition: all 0.25s;
    box-shadow: 0 8px 24px rgba(79,142,247,0.4);
    display: inline-flex; align-items: center; gap: 9px;
  }
  .btn-hero-primary:hover { background:#3a7de8; transform:translateY(-3px); box-shadow:0 14px 32px rgba(79,142,247,0.55); color:#fff; }
  .btn-hero-secondary {
    background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.8);
    border: 1px solid rgba(255,255,255,0.12);
    padding: 14px 28px; border-radius: 10px; font-size: 0.95rem; font-weight: 600;
    text-decoration: none; transition: all 0.25s;
    display: inline-flex; align-items: center; gap: 9px;
  }
  .btn-hero-secondary:hover { background:rgba(255,255,255,0.1); color:#fff; transform:translateY(-2px); }

  /* hero dashboard mockup */
  .hero-visual {
    position: absolute; right: 40px; top: 50%; transform: translateY(-50%);
    z-index: 1; width: min(520px, 48vw);
    animation: heroVisualIn 1s ease 0.5s both;
  }
  @keyframes heroVisualIn {
    from { opacity:0; transform: translateY(-50%) translateX(40px); }
    to   { opacity:1; transform: translateY(-50%) translateX(0); }
  }
  .mockup-window {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    backdrop-filter: blur(12px);
    overflow: hidden;
    box-shadow: 0 40px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.05);
  }
  .mockup-bar {
    background: rgba(255,255,255,0.05);
    padding: 12px 18px;
    display: flex; align-items: center; gap: 8px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
  }
  .mockup-dot { width:10px;height:10px;border-radius:50%; }
  .mockup-dot:nth-child(1){background:#ff5f57}
  .mockup-dot:nth-child(2){background:#febc2e}
  .mockup-dot:nth-child(3){background:#28c840}
  .mockup-url {
    flex:1; height:22px; background:rgba(255,255,255,0.07); border-radius:5px;
    margin: 0 10px; display:flex; align-items:center; padding: 0 10px;
    font-size:0.68rem; color:rgba(255,255,255,0.3);
  }
  .mockup-body { padding: 20px; }
  .mock-stat-row { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:14px; }
  .mock-stat {
    background: rgba(255,255,255,0.05); border-radius:10px; padding:12px 14px;
    border: 1px solid rgba(255,255,255,0.07);
  }
  .mock-stat-n { font-size:1.3rem; font-weight:800; color:#fff; }
  .mock-stat-l { font-size:0.6rem; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:.06em; margin-top:2px; }
  .mock-stat-n.green { color:#4ade80; }
  .mock-stat-n.amber { color:#fbbf24; }
  .mock-stat-n.red   { color:#f87171; }
  .mock-map {
    background: rgba(79,142,247,0.08); border-radius:10px;
    border:1px solid rgba(79,142,247,0.15);
    height:110px; display:flex; align-items:center; justify-content:center;
    position:relative; overflow:hidden; margin-bottom:14px;
  }
  .mock-map-grid {
    position:absolute;inset:0;
    background-image: linear-gradient(rgba(79,142,247,0.08) 1px,transparent 1px),
                      linear-gradient(90deg,rgba(79,142,247,0.08) 1px,transparent 1px);
    background-size:20px 20px;
  }
  .mock-pin {
    width:12px;height:12px;background:#4f8ef7;border-radius:50%;
    position:absolute; border:2px solid rgba(255,255,255,0.4);
    box-shadow:0 0 0 6px rgba(79,142,247,0.2);
    animation: pingPin 2s ease-in-out infinite;
  }
  .mock-pin:nth-child(2){top:30%;left:40%;animation-delay:.5s}
  .mock-pin:nth-child(3){top:60%;left:65%;width:9px;height:9px;background:#a78bfa;animation-delay:1s}
  .mock-pin:nth-child(4){top:25%;left:70%;width:8px;height:8px;background:#4ade80;animation-delay:1.5s}
  @keyframes pingPin {
    0%,100%{box-shadow:0 0 0 4px rgba(79,142,247,0.25)}
    50%    {box-shadow:0 0 0 10px rgba(79,142,247,0)}
  }
  .mock-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
  .mock-block {
    background:rgba(255,255,255,0.04); border-radius:8px; padding:10px 12px;
    border:1px solid rgba(255,255,255,0.07); font-size:0.7rem; color:rgba(255,255,255,0.4);
  }
  .mock-block strong { display:block; font-size:0.82rem; color:rgba(255,255,255,0.75); margin-bottom:4px; font-weight:600; }
  .mock-tag {
    display:inline-block; font-size:0.6rem; padding:2px 7px; border-radius:4px;
    margin:2px 2px 0 0; font-weight:600;
  }
  .mock-tag.r{background:rgba(248,113,113,0.15);color:#f87171}
  .mock-tag.b{background:rgba(79,142,247,0.15);color:#7eb8ff}

  /* floating mini cards */
  .float-card {
    position:absolute; background:rgba(255,255,255,0.06);
    border:1px solid rgba(255,255,255,0.1); border-radius:12px;
    backdrop-filter:blur(16px); padding:10px 16px;
    display:flex; align-items:center; gap:10px;
    font-size:0.78rem; color:#fff; white-space:nowrap;
    box-shadow:0 8px 24px rgba(0,0,0,0.3);
    animation: floatCard 4s ease-in-out infinite;
  }
  .float-card-icon{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:0.85rem}
  .float-card.fc-1{bottom:-20px;left:-30px;animation-delay:0s}
  .float-card.fc-2{top:10px;left:-60px;animation-delay:-2s}
  @keyframes floatCard {
    0%,100%{transform:translateY(0)}
    50%    {transform:translateY(-10px)}
  }

  @keyframes fadeSlideDown {
    from{opacity:0;transform:translateY(-20px)}
    to  {opacity:1;transform:translateY(0)}
  }

  /* ── Trusted strip ─────────────────────────────────────────── */
  .trust-strip {
    border-top: 1px solid rgba(255,255,255,0.06);
    border-bottom: 1px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.02);
    padding: 22px 40px;
    display: flex; align-items: center; justify-content: center; gap: 48px;
    flex-wrap: wrap;
  }
  .trust-item { color: rgba(255,255,255,0.25); font-size: 0.8rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; }
  .trust-item i { margin-right: 7px; color: rgba(255,255,255,0.15); }
  .trust-divider { width: 1px; height: 24px; background: rgba(255,255,255,0.08); }

  /* ── Section base ──────────────────────────────────────────── */
  section { padding: 100px 40px; }

  .section-label {
    display: inline-block; color: var(--accent); font-size: 0.78rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 14px;
  }
  .section-title {
    font-size: clamp(1.9rem, 3.5vw, 2.8rem); font-weight: 800; letter-spacing: -0.03em;
    color: #fff; line-height: 1.15; margin-bottom: 16px;
  }
  .section-desc { font-size: 1rem; color: rgba(255,255,255,0.45); line-height: 1.75; max-width: 540px; }

  /* scroll reveal */
  .reveal { opacity:0; transform:translateY(30px); transition: opacity 0.7s ease, transform 0.7s ease; }
  .reveal.visible { opacity:1; transform:none; }

  /* ── Features ──────────────────────────────────────────────── */
  #features { background: #080f1c; }

  .feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px; margin-top: 60px;
  }
  .feat-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 16px; padding: 30px;
    transition: all 0.3s ease;
    position: relative; overflow: hidden;
  }
  .feat-card::before {
    content:''; position:absolute; inset:0; border-radius:16px;
    background: linear-gradient(135deg, rgba(79,142,247,0.07), transparent);
    opacity:0; transition: opacity 0.3s;
  }
  .feat-card:hover { border-color: rgba(79,142,247,0.35); transform: translateY(-6px); box-shadow:0 20px 40px rgba(0,0,0,0.4); }
  .feat-card:hover::before { opacity:1; }
  .feat-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; margin-bottom: 20px;
  }
  .feat-icon.blue  { background:rgba(79,142,247,0.15); color:#4f8ef7; }
  .feat-icon.purple{ background:rgba(124,58,237,0.15); color:#a78bfa; }
  .feat-icon.green { background:rgba(16,185,129,0.15); color:#34d399; }
  .feat-icon.amber { background:rgba(245,158,11,0.15); color:#fbbf24; }
  .feat-icon.red   { background:rgba(239,68,68,0.15);  color:#f87171; }
  .feat-icon.teal  { background:rgba(20,184,166,0.15); color:#2dd4bf; }
  .feat-title { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 10px; }
  .feat-desc  { font-size: 0.875rem; color: rgba(255,255,255,0.45); line-height: 1.7; }

  /* ── Stats counter ─────────────────────────────────────────── */
  #stats { background: linear-gradient(135deg, #1a2e4a 0%, #0d1f38 100%); }
  .stats-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 40px;
    text-align: center; margin-top: 50px;
  }
  .stat-n  { font-size: 3rem; font-weight: 800; color: #fff; line-height: 1; }
  .stat-n .accent { color: var(--accent); }
  .stat-lb { font-size: 0.85rem; color: rgba(255,255,255,0.45); margin-top: 8px; }
  .stat-bar { height: 3px; border-radius: 2px; background: rgba(255,255,255,0.1); margin: 12px auto 0; width: 60px; overflow: hidden; }
  .stat-bar-fill { height: 100%; background: var(--accent); border-radius: 2px; width: 0; transition: width 1.8s ease; }

  /* ── How it works ──────────────────────────────────────────── */
  #how { background: #060e1a; }
  .steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap: 30px; margin-top: 60px; }
  .step  { text-align: center; position: relative; }
  .step-num {
    width: 60px; height: 60px; border-radius: 50%;
    background: linear-gradient(135deg, rgba(79,142,247,0.3), rgba(124,58,237,0.2));
    border: 1px solid rgba(79,142,247,0.35);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; font-weight: 800; color: var(--accent);
    margin: 0 auto 20px;
  }
  .step-connector {
    display: none;
  }
  .step-title { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 10px; }
  .step-desc  { font-size: 0.875rem; color: rgba(255,255,255,0.4); line-height: 1.7; }

  /* ── CTA ────────────────────────────────────────────────────── */
  #cta {
    background: linear-gradient(135deg, #1a2e4a, #0f172a);
    text-align: center; padding: 100px 40px;
    position: relative; overflow: hidden;
  }
  #cta::before {
    content:''; position:absolute; inset:0;
    background: radial-gradient(ellipse 70% 60% at 50% 50%, rgba(79,142,247,0.15), transparent 70%);
  }
  .cta-box { position: relative; z-index: 1; max-width: 600px; margin: 0 auto; }
  .btn-cta {
    display: inline-flex; align-items: center; gap: 10px;
    background: var(--accent); color: #fff;
    padding: 16px 40px; border-radius: 12px; font-size: 1rem; font-weight: 700;
    text-decoration: none; transition: all 0.25s;
    box-shadow: 0 10px 30px rgba(79,142,247,0.45);
    margin-top: 36px;
  }
  .btn-cta:hover { background:#3a7de8; transform:translateY(-3px); box-shadow:0 16px 40px rgba(79,142,247,0.6); color:#fff; }

  /* ── Footer ─────────────────────────────────────────────────── */
  footer {
    background: #040a14;
    border-top: 1px solid rgba(255,255,255,0.05);
    padding: 40px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;
  }
  footer .foot-brand { display:flex; align-items:center; gap:10px; }
  footer .foot-icon  { width:30px;height:30px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem; }
  footer .foot-name  { font-size:0.875rem;font-weight:700;color:#fff; }
  footer .foot-copy  { font-size:0.75rem;color:rgba(255,255,255,0.25); }
  footer .foot-links { display:flex;gap:28px; }
  footer .foot-link  { font-size:0.8rem;color:rgba(255,255,255,0.35);text-decoration:none;transition:color 0.2s; }
  footer .foot-link:hover { color:rgba(255,255,255,0.75); }

  /* ── Responsive ─────────────────────────────────────────────── */
  @media(max-width: 900px) {
    .hero { padding: 100px 24px 60px; }
    .hero-visual { display: none; }
    .lp-nav { padding: 16px 24px; }
    .lp-nav.scrolled { padding: 12px 24px; }
    section { padding: 70px 24px; }
    .trust-strip { padding: 20px 24px; gap: 24px; }
    .trust-divider { display: none; }
    footer { padding: 28px 24px; }
  }
  @media(max-width: 500px) {
    .nav-cta .btn-nav-ghost { display: none; }
    .hero-actions { flex-direction: column; }
    .btn-hero-primary, .btn-hero-secondary { text-align: center; justify-content: center; }
  }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="lp-nav" id="lp-nav">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-icon"><i class="bi bi-shield-fill-check"></i></div>
    <div>
      <div class="nav-logo-text">MDM System</div>
      <div class="nav-logo-sub">Device Management</div>
    </div>
  </a>
  <div class="nav-cta">
    <?php if ($loggedIn): ?>
      <a href="dashboard.php" class="btn-nav-ghost"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
      <a href="logout.php"    class="btn-nav-solid"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    <?php else: ?>
      <a href="login.php" class="btn-nav-ghost">Sign In</a>
      <a href="login.php" class="btn-nav-solid"><i class="bi bi-arrow-right-circle me-1"></i>Get Started</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>

  <div class="hero-content">
    <div class="hero-badge"><span class="dot"></span>Enterprise-Grade MDM Platform</div>
    <h1 class="hero-title">
      Control Every Device.<br>
      <span class="grad-text">From One Place.</span>
    </h1>
    <p class="hero-desc">
      Monitor, manage, and secure your entire fleet of Android tablets in real time.
      Set policies, block apps, track locations, and enforce curfews — all from a single dashboard.
    </p>
    <div class="hero-actions">
      <?php if ($loggedIn): ?>
        <a href="dashboard.php" class="btn-hero-primary"><i class="bi bi-speedometer2"></i>Open Dashboard</a>
      <?php else: ?>
        <a href="login.php" class="btn-hero-primary"><i class="bi bi-arrow-right-circle"></i>Get Started Free</a>
        <a href="#features" class="btn-hero-secondary"><i class="bi bi-play-circle"></i>See Features</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Dashboard Mockup -->
  <div class="hero-visual">
    <div class="mockup-window">
      <div class="mockup-bar">
        <div class="mockup-dot"></div><div class="mockup-dot"></div><div class="mockup-dot"></div>
        <div class="mockup-url">MDM System &mdash; Dashboard</div>
      </div>
      <div class="mockup-body">
        <div class="mock-stat-row">
          <div class="mock-stat"><div class="mock-stat-n green">12</div><div class="mock-stat-l">Online</div></div>
          <div class="mock-stat"><div class="mock-stat-n amber">3</div><div class="mock-stat-l">Idle</div></div>
          <div class="mock-stat"><div class="mock-stat-n red">1</div><div class="mock-stat-l">Offline</div></div>
        </div>
        <div class="mock-map">
          <div class="mock-map-grid"></div>
          <div class="mock-pin"></div>
          <div class="mock-pin"></div>
          <div class="mock-pin"></div>
          <div class="mock-pin"></div>
        </div>
        <div class="mock-row">
          <div class="mock-block">
            <strong>Blocked Apps</strong>
            <span class="mock-tag r">Play Store</span>
            <span class="mock-tag r">YouTube</span>
            <span class="mock-tag r">Chrome</span>
          </div>
          <div class="mock-block">
            <strong>Active Policy</strong>
            <span class="mock-tag b">Curfew 22:00</span>
            <span class="mock-tag b">VPN On</span>
            <span class="mock-tag b">Kiosk</span>
          </div>
        </div>
      </div>
    </div>
    <!-- Floating cards -->
    <div class="float-card fc-1">
      <div class="float-card-icon" style="background:rgba(74,222,128,0.15);color:#4ade80"><i class="bi bi-check-circle-fill"></i></div>
      <div><div style="font-weight:700;font-size:0.8rem">All Devices Synced</div><div style="font-size:0.68rem;color:rgba(255,255,255,0.45)">2 seconds ago</div></div>
    </div>
    <div class="float-card fc-2">
      <div class="float-card-icon" style="background:rgba(79,142,247,0.15);color:#4f8ef7"><i class="bi bi-lock-fill"></i></div>
      <div><div style="font-weight:700;font-size:0.8rem">Curfew Enforced</div><div style="font-size:0.68rem;color:rgba(255,255,255,0.45)">5 devices locked</div></div>
    </div>
  </div>
</section>

<!-- Trust strip -->
<div class="trust-strip">
  <span class="trust-item"><i class="bi bi-shield-check"></i>End-to-End Secure</span>
  <div class="trust-divider"></div>
  <span class="trust-item"><i class="bi bi-clock-history"></i>Real-Time Sync</span>
  <div class="trust-divider"></div>
  <span class="trust-item"><i class="bi bi-geo-alt-fill"></i>Live GPS Tracking</span>
  <div class="trust-divider"></div>
  <span class="trust-item"><i class="bi bi-android2"></i>Android 8.0+</span>
  <div class="trust-divider"></div>
  <span class="trust-item"><i class="bi bi-wifi-off"></i>Offline Support</span>
</div>

<!-- Features -->
<section id="features">
  <div style="max-width:1100px;margin:0 auto">
    <div class="reveal">
      <span class="section-label">Features</span>
      <h2 class="section-title">Everything you need to<br>manage your fleet</h2>
      <p class="section-desc">A complete toolkit for IT administrators to monitor, control, and secure managed Android devices without ever touching them.</p>
    </div>
    <div class="feature-grid">
      <div class="feat-card reveal" style="transition-delay:0.05s">
        <div class="feat-icon blue"><i class="bi bi-geo-alt-fill"></i></div>
        <div class="feat-title">Live Location Tracking</div>
        <div class="feat-desc">Monitor device locations on a real-time map with 30-second refresh. Locations queue locally when offline and flush automatically when reconnected.</div>
      </div>
      <div class="feat-card reveal" style="transition-delay:0.1s">
        <div class="feat-icon red"><i class="bi bi-ban"></i></div>
        <div class="feat-title">App Blocking</div>
        <div class="feat-desc">Permanently block apps by package name, or schedule time-based blocks for specific days and hours. Supports whitelist mode to allow only approved apps.</div>
      </div>
      <div class="feat-card reveal" style="transition-delay:0.15s">
        <div class="feat-icon purple"><i class="bi bi-moon-stars-fill"></i></div>
        <div class="feat-title">Curfew Mode</div>
        <div class="feat-desc">Automatically lock devices at a configured time every night. Devices stay locked until an admin password is entered, preventing off-hours use.</div>
      </div>
      <div class="feat-card reveal" style="transition-delay:0.2s">
        <div class="feat-icon green"><i class="bi bi-shield-lock-fill"></i></div>
        <div class="feat-title">VPN &amp; DNS Filtering</div>
        <div class="feat-desc">Route all device traffic through a managed VPN. Block specific websites and domains at the DNS level — effective across all browsers and apps.</div>
      </div>
      <div class="feat-card reveal" style="transition-delay:0.25s">
        <div class="feat-icon amber"><i class="bi bi-phone-fill"></i></div>
        <div class="feat-title">Kiosk Mode</div>
        <div class="feat-desc">Lock devices to a single application. Pin designated apps so users cannot exit, switch apps, or access device settings without admin credentials.</div>
      </div>
      <div class="feat-card reveal" style="transition-delay:0.3s">
        <div class="feat-icon teal"><i class="bi bi-terminal-fill"></i></div>
        <div class="feat-title">Remote Commands</div>
        <div class="feat-desc">Send lock, reboot, wipe, or push-rules commands from the dashboard. Commands execute within 30 seconds on the next device poll cycle.</div>
      </div>
    </div>
  </div>
</section>

<!-- Stats -->
<section id="stats">
  <div style="max-width:1100px;margin:0 auto;text-align:center">
    <div class="reveal">
      <span class="section-label">By the Numbers</span>
      <h2 class="section-title">Built for scale</h2>
    </div>
    <div class="stats-grid">
      <div class="reveal" style="transition-delay:0.0s">
        <div class="stat-n"><span class="counter" data-target="30">0</span><span class="accent">s</span></div>
        <div class="stat-lb">Policy sync interval</div>
        <div class="stat-bar"><div class="stat-bar-fill" data-width="90"></div></div>
      </div>
      <div class="reveal" style="transition-delay:0.1s">
        <div class="stat-n"><span class="counter" data-target="100">0</span><span class="accent">%</span></div>
        <div class="stat-lb">Offline resilience</div>
        <div class="stat-bar"><div class="stat-bar-fill" data-width="100"></div></div>
      </div>
      <div class="reveal" style="transition-delay:0.2s">
        <div class="stat-n"><span class="counter" data-target="6">0</span><span class="accent">+</span></div>
        <div class="stat-lb">Enforcement layers</div>
        <div class="stat-bar"><div class="stat-bar-fill" data-width="70"></div></div>
      </div>
      <div class="reveal" style="transition-delay:0.3s">
        <div class="stat-n"><span class="counter" data-target="24">0</span><span class="accent">/7</span></div>
        <div class="stat-lb">Background protection</div>
        <div class="stat-bar"><div class="stat-bar-fill" data-width="100"></div></div>
      </div>
    </div>
  </div>
</section>

<!-- How it works -->
<section id="how">
  <div style="max-width:1100px;margin:0 auto">
    <div class="reveal" style="text-align:center;margin-bottom:0">
      <span class="section-label">How It Works</span>
      <h2 class="section-title">Up and running in minutes</h2>
      <p class="section-desc" style="margin:0 auto">No complex setup. No MDM certificates. Just install, register, and take control.</p>
    </div>
    <div class="steps">
      <div class="step reveal" style="transition-delay:0.0s">
        <div class="step-num">1</div>
        <div class="step-title">Install the Agent</div>
        <div class="step-desc">Download and install the MDM Agent APK on each Android device. Grant device admin and accessibility permissions.</div>
      </div>
      <div class="step reveal" style="transition-delay:0.1s">
        <div class="step-num">2</div>
        <div class="step-title">Auto-Register</div>
        <div class="step-desc">The agent automatically registers each device with the server and begins syncing policies every 30 seconds.</div>
      </div>
      <div class="step reveal" style="transition-delay:0.2s">
        <div class="step-num">3</div>
        <div class="step-title">Set Policies</div>
        <div class="step-desc">Configure app blocks, curfew schedules, VPN rules, and remote commands from the web dashboard — no technical expertise needed.</div>
      </div>
      <div class="step reveal" style="transition-delay:0.3s">
        <div class="step-num">4</div>
        <div class="step-title">Monitor &amp; Enforce</div>
        <div class="step-desc">Watch devices check in on the live map. Policies enforce automatically. Get full visibility with logs and app inventory reports.</div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section id="cta">
  <div class="cta-box reveal">
    <span class="section-label">Ready to start?</span>
    <h2 class="section-title" style="margin-bottom:12px">Take control of your devices today</h2>
    <p style="color:rgba(255,255,255,0.45);font-size:1rem;line-height:1.7">
      Sign in to the MDM dashboard and start managing your fleet in minutes.
    </p>
    <a href="<?= $loggedIn ? 'dashboard.php' : 'login.php' ?>" class="btn-cta">
      <i class="bi bi-arrow-right-circle-fill"></i>
      <?= $loggedIn ? 'Open Dashboard' : 'Sign In to Dashboard' ?>
    </a>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="foot-brand">
    <div class="foot-icon"><i class="bi bi-shield-fill-check"></i></div>
    <div>
      <div class="foot-name">MDM System</div>
      <div class="foot-copy">&copy; <?= date('Y') ?> Mobile Device Management</div>
    </div>
  </div>
  <div class="foot-links">
    <a href="#features" class="foot-link">Features</a>
    <a href="#how"      class="foot-link">How it works</a>
    <a href="login.php" class="foot-link">Sign In</a>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Navbar scroll effect
const nav = document.getElementById('lp-nav');
window.addEventListener('scroll', () => {
  nav.classList.toggle('scrolled', window.scrollY > 40);
}, { passive: true });

// Scroll reveal
const revealEls = document.querySelectorAll('.reveal');
const revealObs = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); revealObs.unobserve(e.target); } });
}, { threshold: 0.12 });
revealEls.forEach(el => revealObs.observe(el));

// Counter animation
function animateCounter(el) {
  const target = parseInt(el.dataset.target);
  const duration = 1800;
  const start = performance.now();
  requestAnimationFrame(function step(now) {
    const progress = Math.min((now - start) / duration, 1);
    // ease-out cubic
    const eased = 1 - Math.pow(1 - progress, 3);
    el.textContent = Math.floor(eased * target);
    if (progress < 1) requestAnimationFrame(step);
    else el.textContent = target;
  });
}

// Progress bars + counters trigger on stats section visibility
const statsSection = document.getElementById('stats');
let statsTriggered = false;
const statsObs = new IntersectionObserver((entries) => {
  if (entries[0].isIntersecting && !statsTriggered) {
    statsTriggered = true;
    document.querySelectorAll('.counter').forEach(animateCounter);
    document.querySelectorAll('.stat-bar-fill').forEach(bar => {
      bar.style.width = bar.dataset.width + '%';
    });
  }
}, { threshold: 0.3 });
statsObs.observe(statsSection);

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const id = a.getAttribute('href').slice(1);
    const el = document.getElementById(id);
    if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  });
});
</script>
</body>
</html>
