<?php
/** @var array|false $existing */
/** @var array|false $office */
/** @var array       $history */
/** @var string      $month */

$user = \Core\Session::user();
?>
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-fingerprint"></i> My Attendance</h1>
    <p class="page-subtitle"><?= date('l, d F Y') ?> &nbsp;·&nbsp; <span id="live-time"></span></p>
  </div>
  <a href="<?= url('attendance/my-leaves') ?>" class="btn btn-outline-primary btn-sm">
    <i class="fa fa-umbrella-beach"></i> My Leaves
  </a>
</div>

<?php if ($flash = \Core\Session::getFlash('success')): ?>
<div class="alert alert-success"><?= e($flash) ?></div>
<?php endif; ?>

<?php if ($existing): ?>
<!-- Already marked today -->
<div class="card" style="border-left:4px solid #22c55e;margin-bottom:20px">
  <div class="card-body" style="padding:20px 24px">
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <div style="font-size:40px">
        <?= match($existing['status']) {
          'present'  => '✅',
          'absent'   => '❌',
          'late'     => '⏰',
          'half_day' => '🕐',
          default    => '📋',
        } ?>
      </div>
      <div>
        <div style="font-size:20px;font-weight:700;color:#22c55e">
          Attendance Marked — <?= ucfirst(str_replace('_', ' ', $existing['status'])) ?>
        </div>
        <?php if ($existing['check_in']): ?>
        <div style="color:var(--text-muted);margin-top:4px">
          <i class="fa fa-clock"></i>
          Checked in at <?= date('h:i A', strtotime($existing['check_in'])) ?>
          <?php if ($existing['check_out']): ?>
          &nbsp;·&nbsp; Checked out at <?= date('h:i A', strtotime($existing['check_out'])) ?>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($existing['location_verified']): ?>
        <div style="color:#22c55e;margin-top:4px;font-size:13px">
          <i class="fa fa-location-dot"></i> GPS Verified
          <?php if ($existing['distance_meters']): ?>
          &nbsp;(<?= round($existing['distance_meters']) ?>m from office)
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- GPS Status box -->
<div id="geo-box" class="card" style="margin-bottom:20px;border-left:4px solid #94a3b8;transition:border-color .3s">
  <div class="card-body" style="padding:18px 24px;display:flex;align-items:center;gap:18px">
    <div style="font-size:32px" id="geo-icon">📡</div>
    <div>
      <div style="font-size:16px;font-weight:600" id="geo-title">Detecting your location…</div>
      <div style="color:var(--text-muted);font-size:13px;margin-top:2px" id="geo-sub">
        Please allow location access when prompted by your browser.
      </div>
    </div>
    <?php if ($office): ?>
    <div style="margin-left:auto;text-align:right">
      <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">Distance</div>
      <div style="font-size:28px;font-weight:700;color:var(--text-primary)" id="distance-display">—</div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($office): ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 24px;display:flex;gap:32px;flex-wrap:wrap">
    <div>
      <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">Required Location</div>
      <div style="font-weight:600;margin-top:2px"><?= e($office['name']) ?></div>
      <div style="font-size:12px;color:var(--text-muted)">Within <?= $office['radius_meters'] ?>m radius</div>
    </div>
    <div>
      <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">Coordinates</div>
      <div style="font-weight:600;margin-top:2px;font-size:13px"><?= $office['latitude'] ?>, <?= $office['longitude'] ?></div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Mark form -->
<div class="card" style="margin-bottom:28px">
  <div class="card-header" style="padding:14px 20px;font-weight:600">
    <i class="fa fa-map-pin"></i> Submit Attendance for Today
  </div>
  <div class="card-body" style="padding:20px 24px">
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px">
      <div class="form-group" style="flex:1;min-width:180px">
        <label class="form-label">Status</label>
        <select id="att-status" class="form-control">
          <option value="present">Present</option>
          <option value="late">Late</option>
          <option value="half_day">Half Day</option>
        </select>
      </div>
      <div class="form-group" style="flex:2;min-width:220px">
        <label class="form-label">Note (optional)</label>
        <input type="text" id="att-note" class="form-control" placeholder="Working from office…">
      </div>
    </div>
    <button id="mark-btn" class="btn btn-primary" onclick="submitAttendance()" disabled>
      <i class="fa fa-map-pin"></i> Mark Attendance
    </button>
    <div id="result-msg" style="margin-top:16px;display:none"></div>
  </div>
</div>
<?php endif; ?>

<!-- Personal history this month -->
<div class="card table-card">
  <div class="card-header" style="padding:14px 20px;font-weight:600">
    <i class="fa fa-history"></i> My Attendance — <?= date('F Y', strtotime($month . '-01')) ?>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr><th>Date</th><th>Day</th><th>Status</th><th>Check-in</th><th>Check-out</th><th>GPS</th><th>Notes</th></tr>
      </thead>
      <tbody>
        <?php if (empty($history)): ?>
          <tr><td colspan="7" class="text-center text-muted">No records this month.</td></tr>
        <?php else: ?>
        <?php foreach ($history as $r): ?>
        <?php
          $badgeMap = [
            'present'  => 'badge-success',
            'absent'   => 'badge-danger',
            'late'     => 'badge-warning',
            'half_day' => 'badge-info',
            'holiday'  => 'badge-secondary',
          ];
          $bc = $badgeMap[$r['status']] ?? 'badge-secondary';
        ?>
        <tr>
          <td><?= date('d M Y', strtotime($r['date'])) ?></td>
          <td class="text-muted"><?= date('D', strtotime($r['date'])) ?></td>
          <td><span class="badge <?= $bc ?>"><?= ucfirst(str_replace('_', ' ', $r['status'])) ?></span></td>
          <td><?= $r['check_in']  ? date('h:i A', strtotime($r['check_in']))  : '—' ?></td>
          <td><?= $r['check_out'] ? date('h:i A', strtotime($r['check_out'])) : '—' ?></td>
          <td>
            <?php if ($r['location_verified']): ?>
              <span class="badge badge-success"><i class="fa fa-location-dot"></i> <?= round($r['distance_meters'] ?? 0) ?>m</span>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="text-muted"><?= e($r['notes'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Live clock
function updateClock() {
  const el = document.getElementById('live-time');
  if (el) el.textContent = new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
setInterval(updateClock, 1000); updateClock();

<?php if (!$existing): ?>
let userLat = null, userLon = null;
const OfficeLat     = <?= (float)($office['latitude']      ?? 0) ?>;
const OfficeLon     = <?= (float)($office['longitude']     ?? 0) ?>;
const AllowedRadius = <?= (int)($office['radius_meters']   ?? 200) ?>;

function haversine(la1,lo1,la2,lo2){
  const R=6371000,f1=la1*Math.PI/180,f2=la2*Math.PI/180,
        df=(la2-la1)*Math.PI/180,dl=(lo2-lo1)*Math.PI/180,
        a=Math.sin(df/2)**2+Math.cos(f1)*Math.cos(f2)*Math.sin(dl/2)**2;
  return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

function updateGeoUI(lat, lon) {
  userLat = lat; userLon = lon;
  const dist    = haversine(lat, lon, OfficeLat, OfficeLon);
  const allowed = dist <= AllowedRadius;
  const distEl  = document.getElementById('distance-display');
  if (distEl) distEl.textContent = Math.round(dist) + 'm';

  const box   = document.getElementById('geo-box');
  const icon  = document.getElementById('geo-icon');
  const title = document.getElementById('geo-title');
  const sub   = document.getElementById('geo-sub');
  const btn   = document.getElementById('mark-btn');

  if (allowed) {
    box.style.borderLeftColor = '#22c55e';
    icon.textContent  = '✅';
    title.textContent = 'You are within the office area';
    sub.textContent   = `Distance: ${Math.round(dist)}m — You can mark your attendance`;
    if (btn) btn.disabled = false;
  } else {
    box.style.borderLeftColor = '#ef4444';
    icon.textContent  = '❌';
    title.textContent = 'You are outside the allowed area';
    sub.textContent   = `You are ${Math.round(dist)}m away. Must be within ${AllowedRadius}m of the office.`;
    if (btn) btn.disabled = true;
  }
}

if (!navigator.geolocation) {
  const isHttp = location.protocol === 'http:';
  document.getElementById('geo-icon').textContent  = '⚠️';
  document.getElementById('geo-title').textContent = 'Location Not Available';
  document.getElementById('geo-sub').textContent   = isHttp
    ? 'Location requires HTTPS. Please access this site over a secure connection (https://).'
    : 'Your browser does not support location services.';
} else {
  navigator.geolocation.getCurrentPosition(
    pos => updateGeoUI(pos.coords.latitude, pos.coords.longitude),
    err => {
      document.getElementById('geo-icon').textContent  = '⚠️';
      let title = 'Location Access Failed';
      let sub   = 'Please enable location permissions to mark attendance.';
      if (err.code === 1) {
        title = 'Location Permission Denied';
        sub   = 'You denied location access. Please allow location in your browser settings and reload.';
      } else if (err.code === 2) {
        title = 'Location Unavailable';
        sub   = 'Your location could not be determined. Check your device GPS or network.';
      } else if (err.code === 3) {
        title = 'Location Request Timed Out';
        sub   = 'Could not get your location in time. Please try reloading the page.';
      }
      document.getElementById('geo-title').textContent = title;
      document.getElementById('geo-sub').textContent   = sub;
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

function submitAttendance() {
  if (!userLat || !userLon) { alert('Location not detected yet. Please wait.'); return; }
  const btn = document.getElementById('mark-btn');
  const res = document.getElementById('result-msg');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Submitting…';

  const fd = new FormData();
  fd.append('mark_attendance', '1');
  fd.append('latitude',  userLat);
  fd.append('longitude', userLon);
  fd.append('status',    document.getElementById('att-status').value);
  fd.append('note',      document.getElementById('att-note').value);

  fetch(window.location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      res.style.display = 'block';
      res.className     = data.success ? 'alert alert-success' : 'alert alert-danger';
      res.textContent   = data.message;
      if (data.success) {
        btn.innerHTML = '<i class="fa fa-check"></i> Marked!';
        setTimeout(() => location.reload(), 2000);
      } else {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fa fa-map-pin"></i> Mark Attendance';
      }
    })
    .catch(() => {
      res.style.display = 'block';
      res.className     = 'alert alert-danger';
      res.textContent   = 'Network error. Please try again.';
      btn.disabled  = false;
      btn.innerHTML = '<i class="fa fa-map-pin"></i> Mark Attendance';
    });
}
<?php endif; ?>
</script>
