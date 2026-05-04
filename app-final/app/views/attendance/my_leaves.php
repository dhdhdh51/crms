<?php
/** @var array $leaves */
?>
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-umbrella-beach"></i> My Leaves</h1>
    <p class="page-subtitle">Apply for leave and track your applications</p>
  </div>
  <a href="<?= url('attendance/my') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fa fa-fingerprint"></i> Mark Attendance
  </a>
</div>

<?php if ($flash = \Core\Session::getFlash('success')): ?>
<div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \Core\Session::getFlash('error')): ?>
<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?= e($flash) ?></div>
<?php endif; ?>

<!-- Apply for leave form -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header" style="padding:14px 20px;font-weight:600">
    <i class="fa fa-paper-plane"></i> Apply for Leave
  </div>
  <div class="card-body" style="padding:20px 24px">
    <form method="POST" action="<?= url('attendance/my-leaves') ?>">
      <?= csrf_field() ?>
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        <div class="form-group" style="flex:1;min-width:160px">
          <label class="form-label">Leave Type <span style="color:#ef4444">*</span></label>
          <select name="leave_type" class="form-control" required>
            <option value="annual">Annual Leave</option>
            <option value="sick">Sick Leave</option>
            <option value="unpaid">Unpaid Leave</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group" style="flex:1;min-width:160px">
          <label class="form-label">From Date <span style="color:#ef4444">*</span></label>
          <input type="date" name="from_date" class="form-control" required
                 min="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group" style="flex:1;min-width:160px">
          <label class="form-label">To Date <span style="color:#ef4444">*</span></label>
          <input type="date" name="to_date" class="form-control" required
                 min="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason / Details</label>
        <textarea name="reason" class="form-control" rows="3"
                  placeholder="Please describe the reason for your leave…"></textarea>
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="fa fa-paper-plane"></i> Submit Application
      </button>
    </form>
  </div>
</div>

<!-- My leave history -->
<div class="card table-card">
  <div class="card-header" style="padding:14px 20px;font-weight:600">
    <i class="fa fa-history"></i> My Leave History
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Leave Type</th>
          <th>From</th>
          <th>To</th>
          <th>Days</th>
          <th>Reason</th>
          <th>Status</th>
          <th>Remarks</th>
          <th>Applied On</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($leaves)): ?>
        <tr><td colspan="9" class="text-center text-muted">No leave requests found.</td></tr>
        <?php else: ?>
        <?php foreach ($leaves as $i => $l): ?>
        <?php
          $statusBadge = [
            'pending'  => 'badge-warning',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
          ][$l['status']] ?? 'badge-secondary';
          $typeColor = [
            'annual' => '#3b82f6', 'sick' => '#ef4444', 'unpaid' => '#f59e0b', 'other' => '#8b5cf6',
          ][$l['leave_type']] ?? '#94a3b8';
        ?>
        <tr>
          <td class="text-muted"><?= $i + 1 ?></td>
          <td>
            <span style="background:<?= $typeColor ?>1a;color:<?= $typeColor ?>;
                         padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600">
              <?= ucfirst($l['leave_type']) ?>
            </span>
          </td>
          <td><?= date('d M Y', strtotime($l['from_date'])) ?></td>
          <td><?= date('d M Y', strtotime($l['to_date'])) ?></td>
          <td><strong><?= $l['days_count'] ?></strong></td>
          <td class="text-muted" style="max-width:160px;white-space:normal"><?= e($l['reason'] ?: '—') ?></td>
          <td><span class="badge <?= $statusBadge ?>"><?= ucfirst($l['status']) ?></span></td>
          <td class="text-muted" style="max-width:160px;white-space:normal">
            <?= e($l['review_note'] ?: ($l['status'] === 'pending' ? 'Awaiting review…' : '—')) ?>
          </td>
          <td class="text-muted"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
