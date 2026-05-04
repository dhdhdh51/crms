<?php /* Login page — Maroon + Gold + Warm White theme */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — VastuVeda CRM</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
<style>
:root{
  --maroon:      #800000;
  --maroon-dark: #5a0000;
  --maroon-deep: #3d0000;
  --gold:        #C9A84C;
  --gold-light:  #E8C97E;
  --gold-dark:   #A8853A;
  --warm-white:  #FAF7F2;
  --warm-card:   #FFFDF9;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;min-height:100vh;overflow:hidden}

/* ── Split Layout ───────────────────────────────────── */
.lp-wrap{display:grid;grid-template-columns:1fr 480px;min-height:100vh}
@media(max-width:900px){.lp-wrap{grid-template-columns:1fr}.lp-left{display:none}}

/* ── Left Panel ─────────────────────────────────────── */
.lp-left{
  position:relative;overflow:hidden;
  background:linear-gradient(135deg,var(--maroon-deep) 0%,var(--maroon-dark) 40%,var(--maroon) 75%,#6b0000 100%);
}
.lp-left-content{
  position:relative;z-index:2;height:100%;display:flex;flex-direction:column;
  justify-content:center;align-items:flex-start;padding:60px;
}
.lp-tagline{font-size:42px;font-weight:800;color:#fff;line-height:1.2;margin-bottom:16px}
.lp-tagline span{color:var(--gold)}
.lp-desc{font-size:15px;color:rgba(255,255,255,.55);max-width:380px;line-height:1.7;margin-bottom:40px}
.lp-stats{display:flex;gap:32px}
.lp-stat-val{font-size:26px;font-weight:700;color:var(--gold)}
.lp-stat-lbl{font-size:11px;color:rgba(255,255,255,.4);margin-top:2px;text-transform:uppercase;letter-spacing:1px}

/* Floating shapes */
.sh{position:absolute;border-radius:50%;filter:blur(80px);opacity:.2}
.sh1{width:400px;height:400px;background:var(--gold);top:-80px;right:-80px;animation:pulse1 7s ease-in-out infinite}
.sh2{width:300px;height:300px;background:var(--maroon-dark);bottom:-60px;left:-60px;animation:pulse2 9s ease-in-out infinite}
.sh3{width:200px;height:200px;background:var(--gold-light);top:50%;left:40%;animation:pulse3 11s ease-in-out infinite}
@keyframes pulse1{0%,100%{transform:scale(1)}50%{transform:scale(1.15)}}
@keyframes pulse2{0%,100%{transform:scale(1)}50%{transform:scale(1.1)}}
@keyframes pulse3{0%,100%{transform:scale(1) translate(0,0)}50%{transform:scale(1.2) translate(20px,-20px)}}

/* Grid overlay */
.lp-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.08) 1px,transparent 1px);background-size:40px 40px;z-index:1}

/* ── Right Panel ────────────────────────────────────── */
.lp-right{
  background:var(--warm-white);display:flex;align-items:center;justify-content:center;
  padding:40px 48px;position:relative;overflow:hidden;
}
.lp-right::before{
  content:'';position:absolute;top:-120px;right:-120px;width:300px;height:300px;
  background:linear-gradient(135deg,var(--gold-light),rgba(128,0,0,.15));border-radius:50%;opacity:.3;filter:blur(60px);
}

/* ── Form Card ──────────────────────────────────────── */
.lp-card{width:100%;max-width:380px;position:relative;z-index:2;animation:slideUp .5s ease}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

/* Brand */
.lp-brand{display:flex;align-items:center;gap:14px;margin-bottom:36px}
.lp-logo{
  width:46px;height:46px;border-radius:12px;
  background:linear-gradient(135deg,var(--maroon),var(--maroon-dark));
  display:flex;align-items:center;justify-content:center;font-size:20px;color:var(--gold);
  box-shadow:0 4px 14px rgba(128,0,0,.35);
}
.lp-brand-text .lp-bname{font-size:18px;font-weight:800;color:var(--maroon-dark);letter-spacing:.5px}
.lp-brand-text .lp-bsub{font-size:10px;color:#78716c;letter-spacing:3px;text-transform:uppercase;margin-top:1px}

/* Heading */
.lp-heading{font-size:26px;font-weight:800;color:var(--maroon-dark);margin-bottom:4px}
.lp-subhead{font-size:13px;color:#78716c;margin-bottom:28px}

/* Error */
.lp-error{
  display:flex;align-items:center;gap:10px;padding:12px 14px;margin-bottom:20px;
  background:#fff1f2;border:1px solid #fecdd3;border-radius:10px;
  color:#be123c;font-size:13px;font-weight:500;
}

/* Field */
.lp-field{margin-bottom:16px}
.lp-label{display:block;font-size:12px;font-weight:600;color:#44403c;margin-bottom:6px;letter-spacing:.3px}
.lp-input-wrap{position:relative}
.lp-input-wrap .lp-ico{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  color:#a8a29e;font-size:14px;pointer-events:none;transition:color .2s;
}
.lp-input-wrap:focus-within .lp-ico{color:var(--maroon)}
.lp-input-wrap input{
  width:100%;padding:13px 44px;border:1.5px solid #e7e0d8;border-radius:10px;
  font-family:'Inter',sans-serif;font-size:14px;color:var(--maroon-dark);outline:none;
  background:var(--warm-card);transition:border-color .2s,box-shadow .2s;
}
.lp-input-wrap input::placeholder{color:#c4b8b0}
.lp-input-wrap input:focus{border-color:var(--maroon);box-shadow:0 0 0 3px rgba(128,0,0,.1)}
.lp-pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#a8a29e;cursor:pointer;padding:4px;font-size:13px;transition:color .2s}
.lp-pw-toggle:hover{color:#44403c}

/* Remember + Forgot */
.lp-row{display:flex;align-items:center;justify-content:space-between;margin:4px 0 22px}
.lp-remember{display:flex;align-items:center;gap:8px;font-size:13px;color:#78716c;cursor:pointer}
.lp-remember input{width:14px;height:14px;accent-color:var(--maroon);cursor:pointer}
.lp-forgot{font-size:13px;color:var(--gold-dark);font-weight:600;text-decoration:none}
.lp-forgot:hover{color:var(--maroon)}

/* Submit button */
.lp-btn{
  width:100%;padding:14px;border:none;border-radius:10px;cursor:pointer;
  font-family:'Inter',sans-serif;font-size:14px;font-weight:700;letter-spacing:.5px;
  background:linear-gradient(135deg,var(--maroon),var(--maroon-dark));color:var(--gold);
  box-shadow:0 4px 14px rgba(128,0,0,.4);transition:all .2s;margin-bottom:16px;
  display:flex;align-items:center;justify-content:center;gap:8px;
}
.lp-btn:hover{background:linear-gradient(135deg,var(--maroon-dark),var(--maroon-deep));box-shadow:0 6px 20px rgba(128,0,0,.5);transform:translateY(-1px)}
.lp-btn:active{transform:translateY(0)}

/* OR divider */
.lp-or{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.lp-or hr{flex:1;border:none;border-top:1px solid #e7e0d8}
.lp-or span{font-size:11px;color:#a8a29e;letter-spacing:1px}

/* Support */
.lp-support{
  width:100%;padding:12px;border:1.5px solid #e7e0d8;border-radius:10px;
  background:var(--warm-card);font-family:'Inter',sans-serif;font-size:13px;font-weight:500;
  color:#57534e;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;
  text-decoration:none;transition:all .2s;
}
.lp-support:hover{border-color:var(--maroon);color:var(--maroon);background:#fff5f5}

/* Footer */
.lp-footer{text-align:center;margin-top:28px;font-size:11px;color:#a8a29e;letter-spacing:.5px}

/* Decorative dots */
.lp-dots{position:absolute;bottom:40px;right:48px;display:grid;grid-template-columns:repeat(5,8px);gap:6px}
.lp-dots span{width:6px;height:6px;border-radius:50%;background:#e7e0d8}
.lp-dots span:nth-child(odd){background:var(--gold-light)}
</style>
</head>
<body>

<div class="lp-wrap">

  <!-- ── Left decorative panel ── -->
  <div class="lp-left">
    <div class="sh sh1"></div>
    <div class="sh sh2"></div>
    <div class="sh sh3"></div>
    <div class="lp-grid"></div>
    <div class="lp-left-content">
      <div class="lp-tagline">Manage your<br>realty business<br>with <span>precision</span></div>
      <div class="lp-desc">VastuVeda CRM — the complete platform for leads, projects, attendance, payroll and beyond.</div>
      <div class="lp-stats">
        <div>
          <div class="lp-stat-val">500+</div>
          <div class="lp-stat-lbl">Active Leads</div>
        </div>
        <div>
          <div class="lp-stat-val">98%</div>
          <div class="lp-stat-lbl">Attendance Rate</div>
        </div>
        <div>
          <div class="lp-stat-val">₹50Cr+</div>
          <div class="lp-stat-lbl">Sales Tracked</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Right login panel ── -->
  <div class="lp-right">
    <div class="lp-card">

      <div class="lp-brand">
        <div class="lp-logo"><i class="fa fa-building-columns"></i></div>
        <div class="lp-brand-text">
          <div class="lp-bname">VASTUVEDA</div>
          <div class="lp-bsub">REALTY CRM</div>
        </div>
      </div>

      <div class="lp-heading">Welcome back</div>
      <div class="lp-subhead">Sign in to your CRM account</div>

      <?php $err = \Core\Session::flash('error'); ?>
      <?php if ($err): ?>
      <div class="lp-error">
        <i class="fa fa-circle-xmark"></i> <?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="<?= url('login') ?>" novalidate>
        <?= csrf_field() ?>

        <div class="lp-field">
          <label class="lp-label">Employee ID or Email</label>
          <div class="lp-input-wrap">
            <i class="fa fa-user lp-ico"></i>
            <input type="text" name="employee_id" placeholder="EMP001 or email@company.com"
                   value="<?= old('employee_id') ?>" required autocomplete="username">
          </div>
        </div>

        <div class="lp-field">
          <label class="lp-label">Password</label>
          <div class="lp-input-wrap">
            <i class="fa fa-lock lp-ico"></i>
            <input type="password" id="pwField" name="password"
                   placeholder="Enter your password" required autocomplete="current-password">
            <button type="button" class="lp-pw-toggle" onclick="togglePw()" tabindex="-1">
              <i class="fa fa-eye" id="pwIcon"></i>
            </button>
          </div>
        </div>

        <div class="lp-row">
          <label class="lp-remember"><input type="checkbox" name="remember"> Remember me</label>
          <a href="#" class="lp-forgot">Forgot password?</a>
        </div>

        <button type="submit" class="lp-btn">
          <i class="fa fa-right-to-bracket"></i> Sign In
        </button>
      </form>

      <div class="lp-or"><hr><span>OR</span><hr></div>

      <a href="mailto:support@vastuveda.com" class="lp-support">
        <i class="fa fa-headset"></i> Contact IT Support
      </a>

      <div class="lp-footer">© <?= date('Y') ?> VastuVeda Realty · All rights reserved</div>
    </div>

    <!-- Decorative dots -->
    <div class="lp-dots">
      <?php for($i=0;$i<15;$i++): ?><span></span><?php endfor; ?>
    </div>
  </div>

</div>

<script>
function togglePw() {
  const f = document.getElementById('pwField');
  const i = document.getElementById('pwIcon');
  f.type = f.type === 'password' ? 'text' : 'password';
  i.className = f.type === 'password' ? 'fa fa-eye' : 'fa fa-eye-slash';
}
</script>
</body>
</html>
