<?php
/** @var string $month */
/** @var array  $months */
/** @var array  $summary */
/** @var array  $log */
?>
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-chart-bar"></i> Attendance Report</h1>
    <p class="page-subtitle">Monthly analytics &amp; export</p>
  </div>
  <div style="display:flex;gap:8px">
    <a href="<?= url('attendance') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fa fa-calendar-check"></i> Mark Attendance
    </a>
    <a href="<?= url('attendance/report?month='.$month.'&export=1') ?>" class="btn btn-success btn-sm">
      <i class="fa fa-file-csv"></i> Export CSV
    </a>
  </div>
</div>

<!-- Month / Department filter -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" action="<?= url('attendance/report') ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
      <label class="form-label" style="margin:0;font-weight:600;font-size:13px">Month</label>
      <select name="month" class="form-control" style="width:180px" onchange="this.form.submit()">
        <?php foreach ($months as $v => $l): ?>
        <option value="<?= $v ?>" <?= $month === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</div>

<!-- Summary cards -->
<?php
$totalPresent = array_sum(array_column($summary, 'present'));
$totalAbsent  = array_sum(array_column($summary, 'absent'));
$totalLate    = array_sum(array_column($summary, 'late'));
$totalHalf    = array_sum(array_column($summary, 'half_day'));
$grandTotal   = $totalPresent + $totalAbsent + $totalLate + $totalHalf;
$avgRate      = $grandTotal > 0 ? round($totalPresent / $grandTotal * 100) : 0;
?>
<div style="display:flex;gap:14px;margin-bottom:24px;flex-wrap:wrap">
  <?php foreach ([
    ['Present',  $totalPresent, '#22c55e', 'fa-circle-check'],
    ['Absent',   $totalAbsent,  '#ef4444', 'fa-circle-xmark'],
    ['Late',     $totalLate,    '#f59e0b', 'fa-clock'],
    ['Half Day', $totalHalf,    '#8b5cf6', 'fa-circle-half-stroke'],
    ['Avg Rate', $avgRate . '%','#3b82f6', 'fa-percent'],
  ] as [$lbl, $val, $col, $ico]): ?>
  <div class="card" style="flex:1;min-width:130px;padding:16px 20px">
    <div style="display:flex;align-items:center;gap:10px">
      <div style="width:36px;height:36px;background:<?= $col ?>1a;border-radius:8px;
                  display:flex;align-items:center;justify-content:center;color:<?= $col ?>;font-size:16px">
        <i class="fa <?= $ico ?>"></i>
      </div>
      <div>
        <div style="font-size:22px;font-weight:700;color:<?= $col ?>"><?= $val ?></div>
        <div style="font-size:11px;color:var(--text-muted)"><?= $lbl ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Per-employee summary table -->
<div class="card table-card" style="margin-bottom:28px">
  <div class="card-header" style="padding:14px 20px;font-weight:600">
    <i class="fa fa-table"></i>
    Employee Summary — <?= date('F Y', strtotime($month . '-01')) ?>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Employee</th>
          <th>Department</th>
          <th style="color:#22c55e">Present</th>
          <th style="color:#ef4444">Absent</th>
          <th style="color:#f59e0b">Late</th>
          <th style="color:#8b5cf6">Half Day</th>
          <th>Attendance Rate</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($summary)): ?>
        <tr><td colspan="8" class="text-center text-muted">No data for this period.</td></tr>
        <?php else: ?>
        <?php foreach ($summary as $i => $r):
          $total = $r['total_marked'] > 0 ? (int)$r['total_marked'] : 1;
          $rate  = round(((int)$r['present']) / $total * 100);
          $rateColor = $rate >= 80 ? '#22c55e' : ($rate >= 60 ? '#f59e0b' : '#ef4444');
        ?>
        <tr>
          <td class="text-muted"><?= $i + 1 ?></td>
          <td>
            <div class="user-cell">
              <div class="user-avatar-sm"><?= strtoupper(substr($r['name'], 0, 1)) ?></div>
              <div>
                <div style="font-weight:600"><?= e($r['name']) ?></div>
                <div class="table-sub"><?= e($r['employee_id']) ?></div>
              </div>
            </div>
          </td>
          <td class="text-muted"><?= e($r['department'] ?: '—') ?></td>
          <td style="color:#22c55e;font-weight:600"><?= (int)$r['present'] ?></td>
          <td style="color:#ef4444;font-weight:600"><?= (int)$r['absent'] ?></td>
          <td style="color:#f59e0b;font-weight:600"><?= (int)$r['late'] ?></td>
          <td style="color:#8b5cf6;font-weight:600"><?= (int)$r['half_day'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:80px;height:6px;background:var(--border-color);border-radius:3px;overflow:hidden">
                <div style="width:<?= $rate ?>%;height:100%;background:<?= $rateColor ?>;border-radius:3px"></div>
              </div>
              <span style="font-size:13px;font-weight:600;color:<?= $rateColor ?>"><?= $rate ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Detailed log -->
<div class="card table-card">
  <div class="card-header" style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between">
    <span style="font-weight:600"><i class="fa fa-list"></i> Detailed Log (latest 300 records)</span>
    <a href="<?= url('attendance/report?month='.$month.'&export=1') ?>" class="btn btn-sm btn-success">
      <i class="fa fa-download"></i> Export CSV
    </a>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Employee</th>
          <th>Dept</th>
          <th>Status</th>
          <th>Check-in</th>
          <th>Check-out</th>
          <th>Method</th>
          <th>GPS</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($log)): ?>
        <tr><td colspan="8" class="text-center text-muted">No records for this period.</td></tr>
        <?php else: ?>
        <?php foreach ($log as $r):
          $badgeMap = [
            'present'  => ['badge-success',   'Present'],
            'absent'   => ['badge-danger',    'Absent'],
            'late'     => ['badge-warning',   'Late'],
            'half_day' => ['badge-info',      'Half Day'],
            'holiday'  => ['badge-secondary', 'Holiday'],
          ];
          [$bc, $bl] = $badgeMap[$r['status']] ?? ['badge-secondary', ucfirst($r['status'])];
        ?>
        <tr>
          <td><?= date('d M Y', strtotime($r['date'])) ?></td>
          <td>
            <div style="font-weight:600"><?= e($r['name']) ?></div>
            <div class="table-sub"><?= e($r['employee_id']) ?></div>
          </td>
          <td class="text-muted"><?= e($r['department'] ?: '—') ?></td>
          <td><span class="badge <?= $bc ?>"><?= $bl ?></span></td>
          <td class="text-muted"><?= $r['check_in']  ? date('h:i A', strtotime($r['check_in']))  : '—' ?></td>
          <td class="text-muted"><?= $r['check_out'] ? date('h:i A', strtotime($r['check_out'])) : '—' ?></td>
          <td>
            <span class="badge <?= ($r['method'] ?? '') === 'face' ? 'badge-info' : 'badge-secondary' ?>">
              <?= ($r['method'] ?? '') === 'face' ? '📱 GPS' : '✋ Manual' ?>
            </span>
          </td>
          <td>
            <?php if ($r['location_verified']): ?>
              <span class="badge badge-success"><i class="fa fa-location-dot"></i> <?= round($r['distance_meters'] ?? 0) ?>m</span>
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
</div>
