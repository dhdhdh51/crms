<?php
/** @var string $date */
/** @var array  $employees */
?>
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-calendar-check"></i> Attendance</h1>
    <p class="page-subtitle">View &amp; manually mark employee attendance</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center">
    <a href="<?= url('attendance/report') ?>" class="btn btn-outline-primary btn-sm">
      <i class="fa fa-chart-bar"></i> Reports
    </a>
    <?php if (\Core\Session::can(['admin','super_admin','hr'])): ?>
    <a href="<?= url('attendance/leaves') ?>" class="btn btn-outline-warning btn-sm">
      <i class="fa fa-umbrella-beach"></i> Leave Requests
    </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($flash = \Core\Session::getFlash('success')): ?>
<div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \Core\Session::getFlash('error')): ?>
<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?= e($flash) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" action="<?= url('attendance') ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
      <label style="font-weight:600;color:var(--text-secondary);font-size:13px">DATE</label>
      <input type="date" name="date" class="form-control" style="width:180px"
             value="<?= e($date) ?>" onchange="this.form.submit()">
      <span style="color:var(--text-muted);font-size:13px"><?= date('l', strtotime($date)) ?></span>
      <div style="margin-left:auto;display:flex;gap:8px">
        <a href="<?= url('attendance?date='.date('Y-m-d', strtotime($date.' -1 day'))) ?>"
           class="btn btn-sm btn-outline-secondary"><i class="fa fa-chevron-left"></i> Prev</a>
        <a href="<?= url('attendance?date='.date('Y-m-d')) ?>"
           class="btn btn-sm btn-outline-secondary">Today</a>
        <a href="<?= url('attendance?date='.date('Y-m-d', strtotime($date.' +1 day'))) ?>"
           class="btn btn-sm btn-outline-secondary">Next <i class="fa fa-chevron-right"></i></a>
      </div>
    </form>
  </div>
</div>

<form method="POST" action="<?= url('attendance?date='.$date) ?>">
  <?= csrf_field() ?>

  <div class="card table-card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px">
      <span style="font-weight:600">
        <i class="fa fa-users"></i>
        <?= count($employees) ?> Employees
        — <?= date('d M Y', strtotime($date)) ?>
      </span>
      <div style="display:flex;gap:8px">
        <button type="button" class="btn btn-sm btn-outline-success" onclick="markAll('present')">
          <i class="fa fa-check"></i> All Present
        </button>
        <button type="submit" class="btn btn-sm btn-primary">
          <i class="fa fa-save"></i> Save Attendance
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Employee</th>
            <th>Department</th>
            <th>Status</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Notes</th>
            <th>GPS</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($employees)): ?>
            <tr><td colspan="8" class="text-center text-muted">No active employees found.</td></tr>
          <?php else: ?>
          <?php foreach ($employees as $i => $emp): ?>
          <tr>
            <td class="text-muted"><?= $i + 1 ?></td>
            <td>
              <div class="user-cell">
                <div class="user-avatar-sm"><?= strtoupper(substr($emp['name'], 0, 1)) ?></div>
                <div>
                  <div style="font-weight:600"><?= e($emp['name']) ?></div>
                  <div class="table-sub"><?= e($emp['employee_id']) ?></div>
                </div>
              </div>
            </td>
            <td><?= e($emp['department'] ?: '—') ?></td>
            <td>
              <select name="records[<?= $emp['id'] ?>][status]"
                      class="form-control att-status-select"
                      data-uid="<?= $emp['id'] ?>"
                      style="min-width:130px">
                <option value="">— Not Marked —</option>
                <?php foreach (['present'=>'✅ Present','absent'=>'❌ Absent','late'=>'⏰ Late','half_day'=>'🕐 Half Day','holiday'=>'🎉 Holiday'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($emp['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td>
              <input type="time" name="records[<?= $emp['id'] ?>][check_in]"
                     class="form-control" style="width:115px"
                     value="<?= e($emp['check_in'] ?? '') ?>">
            </td>
            <td>
              <input type="time" name="records[<?= $emp['id'] ?>][check_out]"
                     class="form-control" style="width:115px"
                     value="<?= e($emp['check_out'] ?? '') ?>">
            </td>
            <td>
              <input type="text" name="records[<?= $emp['id'] ?>][notes]"
                     class="form-control" style="width:140px"
                     placeholder="Optional note"
                     value="<?= e($emp['notes'] ?? '') ?>">
            </td>
            <td class="text-center">
              <?php if ($emp['location_verified']): ?>
                <span class="badge badge-success" title="<?= round($emp['distance_meters'] ?? 0) ?>m from office">
                  <i class="fa fa-location-dot"></i> GPS
                </span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div style="padding:14px 20px;text-align:right;border-top:1px solid var(--border-color)">
      <button type="submit" class="btn btn-primary">
        <i class="fa fa-save"></i> Save Attendance
      </button>
    </div>
  </div>
</form>

<!-- Quick stats bar -->
<?php if (!empty($employees)): ?>
<?php
$present  = count(array_filter($employees, fn($e) => ($e['status'] ?? '') === 'present'));
$absent   = count(array_filter($employees, fn($e) => ($e['status'] ?? '') === 'absent'));
$late     = count(array_filter($employees, fn($e) => ($e['status'] ?? '') === 'late'));
$halfDay  = count(array_filter($employees, fn($e) => ($e['status'] ?? '') === 'half_day'));
$notMarked = count(array_filter($employees, fn($e) => empty($e['status'])));
?>
<div style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap">
  <?php foreach ([
    ['Present',   $present,   '#22c55e'],
    ['Absent',    $absent,    '#ef4444'],
    ['Late',      $late,      '#f59e0b'],
    ['Half Day',  $halfDay,   '#8b5cf6'],
    ['Not Marked',$notMarked, '#94a3b8'],
  ] as [$label, $count, $color]): ?>
  <div class="card" style="flex:1;min-width:120px;padding:14px 18px;text-align:center">
    <div style="font-size:26px;font-weight:700;color:<?= $color ?>"><?= $count ?></div>
    <div style="font-size:12px;color:var(--text-muted);margin-top:2px"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function markAll(status) {
  document.querySelectorAll('.att-status-select').forEach(s => s.value = status);
}
</script>
