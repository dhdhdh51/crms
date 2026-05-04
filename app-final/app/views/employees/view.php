<?php
/** @var array $employee */
/** @var array $leadStats */
/** @var int   $visitCount */

$canManageFace  = \Core\Session::can(['admin', 'super_admin', 'hr']);
$faceRegistered = !empty($employee['face_descriptor']);
?>
<div class="page-header">
  <div>
    <h1 class="page-title"><?= e($employee['name']) ?></h1>
    <p class="page-subtitle"><a href="<?= url('employees') ?>">Employees</a> / <?= e($employee['name']) ?></p>
  </div>
  <?php if (\Core\Session::can(['admin','super_admin','hr'])): ?>
  <a href="<?= url('employees/'.$employee['id'].'/edit') ?>" class="btn btn-secondary btn-sm">
    <i class="fa fa-pen"></i> Edit
  </a>
  <?php endif; ?>
</div>

<?php if ($flash = \Core\Session::getFlash('success')): ?>
<div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= e($flash) ?></div>
<?php endif; ?>

<div class="detail-grid">

  <!-- Left column -->
  <div>
    <div class="card detail-card" style="margin-bottom:16px">
      <div class="card-body text-center" style="padding:2rem">
        <div class="avatar-lg"><?= strtoupper(substr($employee['name'],0,1)) ?></div>
        <h2 style="margin:.75rem 0 .25rem"><?= e($employee['name']) ?></h2>
        <p class="text-muted"><?= e($employee['designation'] ?? '') ?></p>
        <span class="role-badge role-<?= e($employee['role_slug']) ?>"><?= e($employee['role_name']) ?></span>
        <div style="margin-top:14px">
          <?php if ($faceRegistered): ?>
            <span style="background:#22c55e1a;color:#22c55e;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600">
              <i class="fa fa-face-smile"></i> Face Registered
            </span>
            <?php if (!empty($employee['face_updated_at'])): ?>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px">
              Updated <?= date('d M Y', strtotime($employee['face_updated_at'])) ?>
            </div>
            <?php endif; ?>
          <?php else: ?>
            <span style="background:#ef44441a;color:#ef4444;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600">
              <i class="fa fa-face-frown"></i> Face Not Registered
            </span>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body" style="border-top:1px solid var(--border)">
        <dl class="detail-list">
          <dt>Employee ID</dt><dd><?= e($employee['employee_id']) ?></dd>
          <dt>Email</dt>      <dd><?= e($employee['email'] ?: '–') ?></dd>
          <dt>Phone</dt>      <dd><?= e($employee['phone'] ?: '–') ?></dd>
          <dt>Department</dt> <dd><?= e($employee['department'] ?? '–') ?></dd>
          <dt>Joined</dt>     <dd><?= !empty($employee['join_date']) ? date('d M Y', strtotime($employee['join_date'])) : '–' ?></dd>
          <dt>Status</dt>     <dd><span class="badge <?= $employee['is_active']?'badge-success':'badge-secondary' ?>"><?= $employee['is_active']?'Active':'Inactive' ?></span></dd>
          <dt>Last Login</dt> <dd><?= formatDate($employee['last_login'] ?? null, 'd M Y h:i A') ?></dd>
        </dl>
      </div>
    </div>

    <?php if ($canManageFace): ?>
    <!-- FACE REGISTRATION — admin / hr only -->
    <div class="card">
      <div class="card-header" style="padding:14px 20px">
        <div style="display:flex;align-items:center;justify-content:space-between">
          <span style="font-weight:700"><i class="fa fa-camera"></i> Face Registration</span>
          <?php if ($faceRegistered): ?>
            <span style="background:#22c55e1a;color:#22c55e;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600">✓ Registered</span>
          <?php else: ?>
            <span style="background:#ef44441a;color:#ef4444;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600">Not Set</span>
          <?php endif; ?>
        </div>
        <p style="margin:4px 0 0;font-size:12px;color:var(--text-muted)">Only <strong>Admin</strong> &amp; <strong>HR</strong> can register employee faces.</p>
      </div>
      <div class="card-body" style="padding:20px">
        <div style="position:relative;background:#111;border-radius:10px;overflow:hidden;margin-bottom:14px">
          <video id="faceVideo" autoplay muted playsinline style="width:100%;max-height:220px;display:block;object-fit:cover"></video>
          <canvas id="faceCanvas" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none"></canvas>
          <div id="faceOverlay" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.65);color:#fff;flex-direction:column;gap:8px">
            <i class="fa fa-camera" style="font-size:32px;opacity:.5"></i>
            <span style="font-size:13px;opacity:.7">Camera not started</span>
          </div>
        </div>
        <div id="faceStatus" style="font-size:13px;color:var(--text-muted);text-align:center;min-height:18px;margin-bottom:12px"></div>
        <div style="display:flex;gap:8px">
          <button id="startCamBtn" class="btn btn-outline-secondary btn-sm" style="flex:1" onclick="startCamera()">
            <i class="fa fa-video"></i> Start Camera
          </button>
          <button id="captureBtn" class="btn btn-primary btn-sm" style="flex:1" onclick="captureFace()" disabled>
            <i class="fa fa-user-check"></i> Capture &amp; Save
          </button>
        </div>
        <div id="faceResult" style="display:none;margin-top:12px"></div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right column: performance -->
  <div>
    <div class="card detail-card">
      <div class="card-header" style="padding:14px 20px;font-weight:700"><i class="fa fa-chart-bar"></i> Performance Summary</div>
      <div class="card-body">
        <div class="stats-grid cols-2" style="gap:12px">
          <div class="stat-card stat-blue">
            <div class="stat-icon"><i class="fa fa-users"></i></div>
            <div class="stat-info">
              <div class="stat-value"><?= $leadStats['total'] ?? 0 ?></div>
              <div class="stat-label">Total Leads</div>
            </div>
          </div>
          <div class="stat-card stat-green">
            <div class="stat-icon"><i class="fa fa-handshake"></i></div>
            <div class="stat-info">
              <div class="stat-value"><?= $leadStats['closed'] ?? 0 ?></div>
              <div class="stat-label">Closed Deals</div>
            </div>
          </div>
          <div class="stat-card stat-purple">
            <div class="stat-icon"><i class="fa fa-calendar-check"></i></div>
            <div class="stat-info">
              <div class="stat-value"><?= $visitCount ?></div>
              <div class="stat-label">Site Visits</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php if ($canManageFace): ?>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
const MODELS_URL = '<?= url('assets/face-models') ?>';
const SAVE_URL   = '<?= url('employees/' . $employee['id'] . '/update-face') ?>';
let stream = null, modelsLoaded = false;

function setStatus(msg) {
  const el = document.getElementById('faceStatus'); if (el) el.textContent = msg;
}

async function loadModels() {
  setStatus('Loading face models...');
  try {
    await Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_URL),
      faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODELS_URL),
      faceapi.nets.faceRecognitionNet.loadFromUri(MODELS_URL),
    ]);
    modelsLoaded = true;
    setStatus('Models ready. Position face in frame.');
  } catch (e) { setStatus('Failed to load models: ' + e.message); }
}

async function startCamera() {
  const btn = document.getElementById('startCamBtn');
  btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Starting...';

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    const isHttp = location.protocol === 'http:';
    setStatus(isHttp
      ? 'Camera requires HTTPS. Please access this site over a secure connection (https://).'
      : 'Camera not supported by your browser.');
    btn.disabled = false; btn.innerHTML = '<i class="fa fa-video"></i> Start Camera';
    return;
  }

  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
    const video = document.getElementById('faceVideo');
    video.srcObject = stream;
    document.getElementById('faceOverlay').style.display = 'none';
    await loadModels();
    document.getElementById('captureBtn').disabled = false;
    btn.innerHTML = '<i class="fa fa-video-slash"></i> Stop Camera';
    btn.onclick = stopCamera; btn.disabled = false;
    detectionLoop();
  } catch (err) {
    let msg = 'Camera access failed. ';
    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
      msg += 'Permission denied — please allow camera access in your browser settings.';
    } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
      msg += 'No camera found on this device.';
    } else if (err.name === 'NotReadableError') {
      msg += 'Camera is in use by another application.';
    } else {
      msg += err.message;
    }
    setStatus(msg);
    btn.disabled = false; btn.innerHTML = '<i class="fa fa-video"></i> Start Camera';
  }
}

function stopCamera() {
  if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
  document.getElementById('faceVideo').srcObject = null;
  document.getElementById('faceOverlay').style.display = 'flex';
  document.getElementById('captureBtn').disabled = true;
  const btn = document.getElementById('startCamBtn');
  btn.innerHTML = '<i class="fa fa-video"></i> Start Camera'; btn.onclick = startCamera;
  setStatus('Camera stopped.');
}

async function detectionLoop() {
  const video = document.getElementById('faceVideo');
  const canvas = document.getElementById('faceCanvas');
  const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 });
  async function detect() {
    if (!stream || !modelsLoaded) return;
    if (video.readyState === 4) {
      canvas.width = video.videoWidth; canvas.height = video.videoHeight;
      const ctx = canvas.getContext('2d'); ctx.clearRect(0, 0, canvas.width, canvas.height);
      const det = await faceapi.detectSingleFace(video, opts).withFaceLandmarks(true);
      if (det) {
        const r = faceapi.resizeResults(det, { width: canvas.width, height: canvas.height });
        faceapi.draw.drawDetections(canvas, r);
        faceapi.draw.drawFaceLandmarks(canvas, r);
        setStatus('Face detected! Click "Capture & Save" to register.');
      } else { setStatus('No face detected. Look directly at camera.'); }
    }
    if (stream) setTimeout(detect, 600);
  }
  detect();
}

async function captureFace() {
  if (!modelsLoaded || !stream) { alert('Camera not ready.'); return; }
  const btn = document.getElementById('captureBtn');
  const res = document.getElementById('faceResult');
  btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
  setStatus('Analyzing face...'); res.style.display = 'none';

  const video = document.getElementById('faceVideo');
  const opts  = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 });
  const det   = await faceapi.detectSingleFace(video, opts).withFaceLandmarks(true).withFaceDescriptor();

  if (!det) {
    setStatus('No face detected. Look directly at the camera.');
    btn.disabled = false; btn.innerHTML = '<i class="fa fa-user-check"></i> Capture & Save'; return;
  }

  const descriptor = Array.from(det.descriptor);
  setStatus('Saving...');
  try {
    const resp = await fetch(SAVE_URL, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ descriptor })
    });
    const data = await resp.json();
    res.style.display = 'block';
    if (data.success) {
      res.className = 'alert alert-success';
      res.innerHTML = '<i class="fa fa-check-circle"></i> ' + data.message;
      setStatus('Face registered!');
      setTimeout(() => location.reload(), 1500);
    } else {
      res.className = 'alert alert-danger';
      res.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + data.message;
      setStatus(data.message);
      btn.disabled = false; btn.innerHTML = '<i class="fa fa-user-check"></i> Capture & Save';
    }
  } catch (e) {
    res.style.display = 'block'; res.className = 'alert alert-danger';
    res.innerHTML = 'Network error. Please try again.';
    setStatus('Network error.'); btn.disabled = false; btn.innerHTML = '<i class="fa fa-user-check"></i> Capture & Save';
  }
}
</script>
<?php endif; ?>
