<?php
/** @var array  $leaves */
/** @var string $filter */
/** @var array  $counts */
?>
<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fa fa-umbrella-beach"></i> Leave Requests</h1>
    <p class="page-subtitle">Review and manage employee leave applications</p>
  </div>
  <a href="<?= url('attendance') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fa fa-calendar-check"></i> Attendance Sheet
  </a>
</div>

<?php if ($flash = \Core\Session::getFlash('success')): ?>
<div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \Core\Session::getFlash('error')): ?>
<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?= e($flash) ?></div>
<?php endif; ?>

<!-- Filter tabs -->
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
  <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $f => $l): ?>
  <a href="<?= url('attendance/leaves?filter='.$f) ?>"
     class="btn btn-sm <?= $filter === $f ? 'btn-primary' : 'btn-outline-secondary' ?>">
    <?= $l ?>
    <?php if (isset($counts[$f]) && $counts[$f] > 0): ?>
      <span style="background:<?= $f==='pending' ? '#ef4444' : 'rgba(255,255,255,.25)' ?>;
                   color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px">
        <?= $counts[$f] ?>
      </span>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<div class="card table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Employee</th>
          <th>Leave Type</th>
          <th>From</th>
          <th>To</th>
          <th>Days</th>
          <th>Reason</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($leaves)): ?>
        <tr><td colspan="9" class="text-center text-muted">No <?= $filter ?> leave requests found.</td></tr>
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
            <div class="user-cell">
              <div class="user-avatar-sm"><?= strtoupper(substr($l['name'], 0, 1)) ?></div>
              <div>
                <div style="font-weight:600"><?= e($l['name']) ?></div>
                <div class="table-sub"><?= e($l['employee_id']) ?> · <?= e($l['department'] ?: '—') ?></div>
              </div>
            </div>
          </td>
          <td>
            <span style="background:<?= $typeColor ?>1a;color:<?= $typeColor ?>;
                         padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;text-transform:capitalize">
              <?= ucfirst($l['leave_type']) ?>
            </span>
          </td>
          <td><?= date('d M Y', strtotime($l['from_date'])) ?></td>
          <td><?= date('d M Y', strtotime($l['to_date'])) ?></td>
          <td><strong><?= $l['days_count'] ?></strong></td>
          <td class="text-muted" style="max-width:180px;white-space:normal"><?= e($l['reason'] ?: '—') ?></td>
          <td><span class="badge <?= $statusBadge ?>"><?= ucfirst($l['status']) ?></span></td>
          <td class="table-actions">
            <?php if ($l['status'] === 'pending'): ?>
            <!-- Approve / Reject modal triggers -->
            <button class="action-btn action-btn-success" title="Approve"
                    onclick="openReview(<?= $l['id'] ?>, 'approve')">
              <i class="fa fa-check"></i>
            </button>
            <button class="action-btn action-btn-danger" title="Reject"
                    onclick="openReview(<?= $l['id'] ?>, 'reject')">
              <i class="fa fa-times"></i>
            </button>
            <?php else: ?>
            <span class="text-muted" style="font-size:12px">
              <?= $l['status'] === 'approved' ? '✅ Done' : '❌ Done' ?>
            </span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Review modal -->
<div id="reviewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;
     align-items:center;justify-content:center">
  <div class="card" style="width:420px;max-width:95vw;padding:0;border-radius:16px;overflow:hidden">
    <div id="modalHeader" class="card-header" style="padding:18px 24px;font-weight:700;font-size:16px"></div>
    <div class="card-body" style="padding:20px 24px">
      <form id="reviewForm" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" id="reviewAction">
        <div class="form-group">
          <label class="form-label">Note / Remarks (optional)</label>
          <textarea name="review_note" class="form-control" rows="3"
                    placeholder="Add a note for the employee…"></textarea>
        </div>
        <div style="display:flex;gap:10px;margin-top:16px">
          <button type="submit" id="reviewSubmitBtn" class="btn btn-primary" style="flex:1">Confirm</button>
          <button type="button" class="btn btn-outline-secondary" onclick="closeModal()" style="flex:1">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openReview(id, action) {
  const modal  = document.getElementById('reviewModal');
  const header = document.getElementById('modalHeader');
  const btn    = document.getElementById('reviewSubmitBtn');
  document.getElementById('reviewForm').action   = '<?= url('attendance/leaves/') ?>' + id + '/review';
  document.getElementById('reviewAction').value  = action;
  if (action === 'approve') {
    header.textContent = '✅ Approve Leave Request';
    btn.className      = 'btn btn-success';
    btn.textContent    = 'Approve';
  } else {
    header.textContent = '❌ Reject Leave Request';
    btn.className      = 'btn btn-danger';
    btn.textContent    = 'Reject';
  }
  modal.style.display = 'flex';
}
function closeModal() {
  document.getElementById('reviewModal').style.display = 'none';
}
document.getElementById('reviewModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>
