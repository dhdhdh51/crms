<?php
/** @var array $roles */
$canCaptureFace = \Core\Session::can(['admin', 'super_admin', 'hr']);
?>
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-user-plus"></i> Add Employee</h1>
    <p class="page-subtitle"><a href="<?= url('employees') ?>">Employees</a> / Add New</p>
  </div>
</div>

<?php if ($flash = \Core\Session::getFlash('error')): ?>
<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?= e($flash) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('employees/store') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="face_descriptor" id="face_descriptor_input">

  <div style="display:flex;gap:20px;align-items:flex-start">

    <!-- ── LEFT: Form Fields ── -->
    <div class="card form-card" style="flex:1">
      <div class="card-header" style="padding:14px 20px;font-weight:700;border-bottom:1px solid var(--border)">
        <i class="fa fa-id-card"></i> Employee Details
      </div>
      <div class="card-body" style="padding:24px">
        <div class="form-grid">
          <div class="form-group">
            <label>Employee ID <span style="color:#ef4444">*</span></label>
            <input type="text" name="employee_id" class="form-control" placeholder="e.g. EMP006" required>
          </div>
          <div class="form-group">
            <label>Full Name <span style="color:#ef4444">*</span></label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" placeholder="employee@company.com">
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="tel" name="phone" class="form-control" placeholder="10-digit mobile">
          </div>
          <div class="form-group">
            <label>Role <span style="color:#ef4444">*</span></label>
            <select name="role_id" class="form-control">
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Designation</label>
            <input type="text" name="designation" class="form-control" placeholder="e.g. Sales Executive">
          </div>
          <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" class="form-control" placeholder="e.g. Sales">
          </div>
          <div class="form-group">
            <label>Join Date</label>
            <input type="date" name="join_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label>Password <span style="color:#ef4444">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required minlength="6">
          </div>
        </div>

        <?php if ($canCaptureFace): ?>
        <div id="faceFormStatus" style="display:none;margin-top:14px;padding:10px 14px;border-radius:8px;
             font-size:13px;font-weight:600;background:#22c55e1a;border:1px solid #22c55e40;color:#22c55e">
          <i class="fa fa-check-circle"></i> Face captured — will be saved with employee record.
        </div>
        <?php endif; ?>

        <div class="form-actions" style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-user-plus"></i> Create Employee
          </button>
          <a href="<?= url('employees') ?>" class="btn btn-ghost">Cancel</a>
        </div>
      </div>
    </div>

    <?php if ($canCaptureFace): ?>
    <!-- ── RIGHT: Face Capture Panel ── -->
    <div class="card" style="width:320px;flex-shrink:0;position:sticky;top:76px">
      <div class="card-header" style="padding:14px 16px;border-bottom:1px solid var(--border)">
        <div style="font-weight:700;font-size:14px"><i class="fa fa-camera"></i> Face Registration</div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px">Admin / HR only · Optional during creation</div>
        <span id="faceBadge" style="display:inline-block;margin-top:6px;padding:2px 10px;border-radius:10px;
              font-size:11px;font-weight:700;background:#e2e8f0;color:#64748b">NOT CAPTURED</span>
      </div>
      <div class="card-body" style="padding:16px">

        <!-- Webcam -->
        <div style="position:relative;background:#111;border-radius:8px;overflow:hidden;margin-bottom:10px">
          <video id="faceVideo" autoplay muted playsinline
                 style="width:100%;height:180px;display:block;object-fit:cover"></video>
          <canvas id="faceCanvas"
                  style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none"></canvas>
          <div id="faceOverlay"
               style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
                      background:rgba(0,0,0,.75);color:#fff;flex-direction:column;gap:6px">
            <i class="fa fa-camera" style="font-size:26px;opacity:.4"></i>
            <span style="font-size:11px;opacity:.55">Click Start Camera</span>
          </div>
        </div>

        <div id="faceMsg" style="font-size:12px;color:var(--text-muted);text-align:center;min-height:16px;margin-bottom:10px"></div>

        <div style="display:flex;gap:6px;margin-bottom:10px">
          <button type="button" id="startCamBtn" class="btn btn-outline-secondary btn-sm" style="flex:1" onclick="startCamera()">
            <i class="fa fa-video"></i> Start
          </button>
          <button type="button" id="captureBtn" class="btn btn-primary btn-sm" style="flex:1" onclick="captureFace()" disabled>
            <i class="fa fa-user-check"></i> Capture
          </button>
          <button type="button" id="resetBtn" class="btn btn-sm" onclick="resetFace()" title="Clear"
                  style="display:none;padding:6px 10px;background:#fee2e2;color:#dc2626;border:1px solid #fecaca;border-radius:6px">
            <i class="fa fa-xmark"></i>
          </button>
        </div>

        <div id="capturedPreview" style="display:none;text-align:center;padding:10px;
             background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;margin-bottom:10px">
          <i class="fa fa-face-smile" style="font-size:22px;color:#22c55e"></i>
          <div style="font-size:12px;font-weight:700;color:#22c55e;margin-top:4px">Face Captured!</div>
          <div style="font-size:11px;color:#64748b">Saved with employee on submit</div>
        </div>

        <div style="padding:8px 10px;background:#f8fafc;border-radius:6px;font-size:11px;color:#64748b;line-height:1.5">
          <i class="fa fa-circle-info" style="color:#3b82f6"></i>
          Face capture is optional. You can also register it later from the employee profile page.
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /flex -->
</form>

<?php if ($canCaptureFace): ?>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
const MODELS_URL = '<?= url('assets/face-models') ?>';
let stream = null, modelsLoaded = false;

function setMsg(m) { const el = document.getElementById('faceMsg'); if(el) el.textContent = m; }

async function loadModels() {
  setMsg('Loading AI models...');
  try {
    await Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_URL),
      faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODELS_URL),
      faceapi.nets.faceRecognitionNet.loadFromUri(MODELS_URL),
    ]);
    modelsLoaded = true;
    setMsg('Ready. Look at the camera.');
    runDetection();
  } catch(e) { setMsg('Model error: ' + e.message); }
}

async function startCamera() {
  const btn = document.getElementById('startCamBtn');
  btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    const isHttp = location.protocol === 'http:';
    setMsg(isHttp
      ? 'Camera requires HTTPS. Please access this site over a secure connection (https://).'
      : 'Camera not supported by your browser.');
    btn.disabled = false; btn.innerHTML = '<i class="fa fa-video"></i> Start';
    return;
  }

  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode:'user' }, audio: false });
    document.getElementById('faceVideo').srcObject = stream;
    document.getElementById('faceOverlay').style.display = 'none';
    await loadModels();
    document.getElementById('captureBtn').disabled = false;
    btn.innerHTML = '<i class="fa fa-video-slash"></i> Stop';
    btn.onclick = stopCamera; btn.disabled = false;
  } catch(e) {
    let msg = 'Camera access failed. ';
    if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') {
      msg += 'Permission denied — please allow camera access in your browser settings.';
    } else if (e.name === 'NotFoundError' || e.name === 'DevicesNotFoundError') {
      msg += 'No camera found on this device.';
    } else if (e.name === 'NotReadableError') {
      msg += 'Camera is in use by another application.';
    } else {
      msg += e.message;
    }
    setMsg(msg);
    btn.disabled = false; btn.innerHTML = '<i class="fa fa-video"></i> Start';
  }
}

function stopCamera() {
  if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
  document.getElementById('faceVideo').srcObject = null;
  document.getElementById('faceOverlay').style.display = 'flex';
  document.getElementById('captureBtn').disabled = true;
  const btn = document.getElementById('startCamBtn');
  btn.innerHTML = '<i class="fa fa-video"></i> Start'; btn.onclick = startCamera;
  setMsg('Camera off.');
}

async function runDetection() {
  const video = document.getElementById('faceVideo');
  const canvas = document.getElementById('faceCanvas');
  const opts = new faceapi.TinyFaceDetectorOptions({ inputSize:320, scoreThreshold:0.5 });
  async function tick() {
    if (!stream || !modelsLoaded) return;
    if (video.readyState === 4) {
      canvas.width = video.videoWidth; canvas.height = video.videoHeight;
      canvas.getContext('2d').clearRect(0,0,canvas.width,canvas.height);
      const det = await faceapi.detectSingleFace(video, opts).withFaceLandmarks(true);
      if (det) {
        const r = faceapi.resizeResults(det, { width:canvas.width, height:canvas.height });
        faceapi.draw.drawDetections(canvas, r);
        faceapi.draw.drawFaceLandmarks(canvas, r);
        setMsg('Face detected! Click Capture.');
      } else { setMsg('No face detected. Look straight at camera.'); }
    }
    if (stream) setTimeout(tick, 600);
  }
  tick();
}

async function captureFace() {
  if (!modelsLoaded || !stream) { alert('Camera not ready.'); return; }
  const btn = document.getElementById('captureBtn');
  btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
  setMsg('Analyzing...');

  const video = document.getElementById('faceVideo');
  const opts  = new faceapi.TinyFaceDetectorOptions({ inputSize:320, scoreThreshold:0.5 });
  const det   = await faceapi.detectSingleFace(video, opts).withFaceLandmarks(true).withFaceDescriptor();

  if (!det) {
    setMsg('No face found. Try again.');
    btn.disabled = false; btn.innerHTML = '<i class="fa fa-user-check"></i> Capture'; return;
  }

  document.getElementById('face_descriptor_input').value = JSON.stringify(Array.from(det.descriptor));
  stopCamera();
  document.getElementById('capturedPreview').style.display = 'block';
  document.getElementById('resetBtn').style.display = 'inline-block';
  document.getElementById('faceFormStatus').style.display = 'block';
  const badge = document.getElementById('faceBadge');
  badge.style.background = '#dcfce7'; badge.style.color = '#16a34a';
  badge.textContent = '✓ CAPTURED';
  setMsg('');
}

function resetFace() {
  document.getElementById('face_descriptor_input').value = '';
  document.getElementById('capturedPreview').style.display = 'none';
  document.getElementById('resetBtn').style.display = 'none';
  document.getElementById('faceFormStatus').style.display = 'none';
  const badge = document.getElementById('faceBadge');
  badge.style.background = '#e2e8f0'; badge.style.color = '#64748b';
  badge.textContent = 'NOT CAPTURED';
  setMsg('Cleared. You can capture again.');
}
</script>
<?php endif; ?>
