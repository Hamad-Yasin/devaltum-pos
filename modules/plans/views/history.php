<?php ob_start(); ?>
<?php
$planName = (string) ($store['plan_name'] ?? 'Pro');
$payLogoMap = [
    'easypaisa' => '/assets/img/payment/easypaisa.png',
    'jazzcash'  => '/assets/img/payment/jazzcash.png',
    'nayapay'   => '/assets/img/payment/nayapay.png',
    'meezan'    => '/assets/img/payment/meezan.webp',
    'bank'      => '/assets/img/bank.svg',
];
?>

<style>
.history-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding-bottom: 40px;
}

/* ── KPI Stat Cards ────────────────────────── */
.history-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}
.history-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.history-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
}
.history-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
}
.history-stat-icon .material-symbols-rounded {
    font-size: 24px;
}
.history-stat-info {
    flex: 1;
    min-width: 0;
}
.history-stat-label {
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 4px;
    display: block;
}
.history-stat-val {
    font-size: 22px;
    font-weight: 900;
    color: #0f172a;
    line-height: 1.1;
}

/* ── Table Card ────────────────────────────── */
.history-table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}
.history-table-header {
    padding: 18px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.history-table-title {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.history-table-responsive {
    width: 100%;
    overflow-x: auto;
    scrollbar-width: thin;
}
.history-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}
.history-table th {
    background: #f8fafc;
    color: #475569;
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 14px 20px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.history-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13.5px;
    color: #1e293b;
    vertical-align: middle;
}
.history-table tr:hover td {
    background: #fbfdff;
}

/* ── Badges ────────────────────────────────── */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}
.status-pill .material-symbols-rounded {
    font-size: 15px;
}
.status-pill.pending {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}
.status-pill.approved, .status-pill.completed, .status-pill.active {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.status-pill.rejected, .status-pill.declined {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

/* ── Pagination ────────────────────────────── */
.history-pagination {
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    border-top: 1px solid #f1f5f9;
    background: #fcfdfe;
}
.pagination-info {
    font-size: 13px;
    color: #64748b;
    font-weight: 600;
}
.pagination-nav {
    display: flex;
    align-items: center;
    gap: 6px;
}
.page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 10px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #334155;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.15s ease;
}
.page-btn:hover:not(.disabled) {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
}
.page-btn.active {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
}
.page-btn.disabled {
    opacity: 0.5;
    pointer-events: none;
    cursor: not-allowed;
}

/* ── Empty State ───────────────────────────── */
.history-empty {
    padding: 60px 20px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}
.history-empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 20px;
    background: #f1f5f9;
    display: grid;
    place-items: center;
    color: #94a3b8;
    margin-bottom: 6px;
}
.history-empty-icon .material-symbols-rounded {
    font-size: 32px;
}
.history-empty-title {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
}
.history-empty-text {
    font-size: 13.5px;
    color: #64748b;
    max-width: 360px;
    line-height: 1.5;
}

/* ── Proof Modal ───────────────────────────── */
.proof-modal {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(6px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.proof-modal.active {
    display: flex;
}
.proof-modal-content {
    background: #fff;
    border-radius: 20px;
    max-width: 500px;
    width: 100%;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    animation: modalIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.proof-modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.proof-modal-body {
    padding: 20px;
    text-align: center;
    background: #f8fafc;
}
.proof-modal-body img {
    max-width: 100%;
    max-height: 65vh;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    object-fit: contain;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1>Payment History</h1>
        <p class="page-subtitle">Track your subscription payments, verification records, and approval history.</p>
    </div>
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/pay" class="btn btn-primary" style="background: linear-gradient(135deg, #0ea5e9, #10b981); border:none; box-shadow: 0 4px 14px rgba(14,165,233,0.3); border-radius:12px; font-weight:700; height:40px; padding:0 16px; display:inline-flex; align-items:center; gap:6px; font-size:13px;">
            <span class="material-symbols-rounded" style="font-size:18px;">payments</span> Pay Here
        </a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/index" class="btn btn-secondary" style="border-radius:12px; font-weight:700; display:inline-flex; align-items:center; gap:6px; height:40px; padding:0 16px; font-size:13px;">
            <span class="material-symbols-rounded" style="font-size:18px;">arrow_back</span> Back to Plan
        </a>
    </div>
</div>

<div class="history-page">

    <!-- KPI Stats Cards -->
    <div class="history-stats-grid">
        <div class="history-stat-card">
            <div class="history-stat-icon" style="background: #eff6ff; color: #2563eb;">
                <span class="material-symbols-rounded">receipt_long</span>
            </div>
            <div class="history-stat-info">
                <span class="history-stat-label">Total Submissions</span>
                <div class="history-stat-val"><?= number_format($stats['total_count'] ?? 0) ?></div>
            </div>
        </div>

        <div class="history-stat-card">
            <div class="history-stat-icon" style="background: #ecfdf5; color: #10b981;">
                <span class="material-symbols-rounded">check_circle</span>
            </div>
            <div class="history-stat-info">
                <span class="history-stat-label">Approved Payments</span>
                <div class="history-stat-val">Rs <?= number_format($stats['approved_sum'] ?? 0) ?></div>
            </div>
        </div>

        <div class="history-stat-card">
            <div class="history-stat-icon" style="background: #fffbeb; color: #d97706;">
                <span class="material-symbols-rounded">hourglass_top</span>
            </div>
            <div class="history-stat-info">
                <span class="history-stat-label">Pending Approval</span>
                <div class="history-stat-val"><?= number_format($stats['pending_count'] ?? 0) ?></div>
            </div>
        </div>

        <div class="history-stat-card">
            <div class="history-stat-icon" style="background: #fef2f2; color: #ef4444;">
                <span class="material-symbols-rounded">cancel</span>
            </div>
            <div class="history-stat-info">
                <span class="history-stat-label">Rejected / Failed</span>
                <div class="history-stat-val"><?= number_format($stats['rejected_count'] ?? 0) ?></div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="history-table-card">
        <div class="history-table-header">
            <div class="history-table-title">
                <span class="material-symbols-rounded" style="color: #2563eb;">history</span>
                <span>Payment Records</span>
            </div>
            <div style="font-size: 13px; color: #64748b; font-weight: 600;">
                Showing 10 entries per page
            </div>
        </div>

        <?php if (!empty($transactions)): ?>
            <div class="history-table-responsive">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Trx ID / Ref</th>
                            <th>Method</th>
                            <th>Plan / Details</th>
                            <th>Amount Paid</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Proof</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                            <?php
                            $st = strtolower(trim((string) ($tx['status'] ?? 'pending')));
                            $badgeClass = 'pending';
                            $badgeText = 'Pending Approval';
                            $badgeIcon = 'hourglass_empty';

                            if ($st === 'approved' || $st === 'completed' || $st === 'active' || $st === 'paid') {
                                $badgeClass = 'approved';
                                $badgeText = 'Approved';
                                $badgeIcon = 'check_circle';
                            } elseif ($st === 'rejected' || $st === 'declined' || $st === 'failed') {
                                $badgeClass = 'rejected';
                                $badgeText = 'Rejected';
                                $badgeIcon = 'cancel';
                            }

                            $methodName = (string) ($tx['resolved_method_name'] ?? $tx['method'] ?? 'Manual');
                            $methodSlug = strtolower($methodName);
                            $logoSrc = '';
                            foreach ($payLogoMap as $k => $p) {
                                if (str_contains($methodSlug, $k)) {
                                    $logoSrc = (defined('BASE_URL') ? BASE_URL : '') . $p;
                                    break;
                                }
                            }
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; font-family: monospace; font-size: 13px; color: #0f172a;">
                                        <?= htmlspecialchars($tx['gateway_ref'] ?: ('#TX-' . $tx['id'])) ?>
                                    </div>
                                    <small style="color: #94a3b8; font-size: 11px;">ID: #<?= (int) $tx['id'] ?></small>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <?php if ($logoSrc): ?>
                                            <img src="<?= $logoSrc ?>" alt="<?= htmlspecialchars($methodName) ?>" style="width: 24px; height: 24px; object-fit: contain;">
                                        <?php else: ?>
                                            <span class="material-symbols-rounded" style="font-size: 20px; color: #64748b;">account_balance</span>
                                        <?php endif; ?>
                                        <span style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($methodName) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a;">
                                        <?= htmlspecialchars($tx['plan_title'] ?: $planName) ?>
                                    </div>
                                    <?php if (!empty($tx['notes'])): ?>
                                        <small style="color: #64748b; font-size: 11.5px; display: block; max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($tx['notes']) ?>">
                                            <?= htmlspecialchars($tx['notes']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size: 15px; font-weight: 900; color: #0f172a;">
                                        Rs <?= number_format((float) ($tx['amount'] ?? 0)) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #334155;">
                                        <?= date('d M, Y', strtotime($tx['created_at'])) ?>
                                    </div>
                                    <small style="color: #94a3b8; font-size: 11.5px;">
                                        <?= date('h:i A', strtotime($tx['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="status-pill <?= $badgeClass ?>">
                                        <span class="material-symbols-rounded"><?= $badgeIcon ?></span>
                                        <?= $badgeText ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($tx['receipt_screenshot'])): ?>
                                        <?php $fullImgPath = (defined('BASE_URL') ? BASE_URL : '') . '/' . ltrim($tx['receipt_screenshot'], '/'); ?>
                                        <button type="button" class="btn btn-secondary" onclick="openProofModal('<?= htmlspecialchars($fullImgPath) ?>')" style="padding: 4px 10px; font-size: 11.5px; height: 28px; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                            <span class="material-symbols-rounded" style="font-size: 15px;">image</span> View
                                        </button>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1; font-size: 12px; font-weight: 600;">None</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="history-pagination">
                <div class="pagination-info">
                    Showing <strong><?= min($totalRows, $limit * ($currentPage - 1) + 1) ?></strong> to <strong><?= min($totalRows, $limit * $currentPage) ?></strong> of <strong><?= number_format($totalRows) ?></strong> entries
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination-nav">
                        <!-- Prev -->
                        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/history?page=<?= max(1, $currentPage - 1) ?>" class="page-btn <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                            <span class="material-symbols-rounded" style="font-size: 18px;">chevron_left</span>
                        </a>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <?php if ($p == 1 || $p == $totalPages || ($p >= $currentPage - 1 && $p <= $currentPage + 1)): ?>
                                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/history?page=<?= $p ?>" class="page-btn <?= $p == $currentPage ? 'active' : '' ?>">
                                    <?= $p ?>
                                </a>
                            <?php elseif ($p == $currentPage - 2 || $p == $currentPage + 2): ?>
                                <span style="padding: 0 4px; color: #94a3b8;">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- Next -->
                        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/history?page=<?= min($totalPages, $currentPage + 1) ?>" class="page-btn <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                            <span class="material-symbols-rounded" style="font-size: 18px;">chevron_right</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Empty State -->
            <div class="history-empty">
                <div class="history-empty-icon">
                    <span class="material-symbols-rounded">receipt_long</span>
                </div>
                <div class="history-empty-title">No Payment History Found</div>
                <div class="history-empty-text">
                    You haven't submitted any subscription plan payment records yet.
                </div>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/pay" class="btn btn-primary" style="margin-top: 8px; border-radius: 12px; font-weight: 700; height: 42px; padding: 0 20px; display: inline-flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-rounded">payments</span> Make a Payment Now
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Screenshot Proof Modal -->
<div id="proofModal" class="proof-modal" onclick="closeProofModal(event)">
    <div class="proof-modal-content" onclick="event.stopPropagation()">
        <div class="proof-modal-header">
            <strong style="font-size: 15px; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                <span class="material-symbols-rounded" style="color: #2563eb;">image</span> Payment Receipt Screenshot
            </strong>
            <button type="button" onclick="closeProofModal()" style="border: none; background: transparent; cursor: pointer; color: #64748b; font-size: 20px; display: flex; align-items: center;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="proof-modal-body">
            <img id="proofModalImg" src="" alt="Payment Proof">
        </div>
    </div>
</div>

<script>
function openProofModal(src) {
    const modal = document.getElementById('proofModal');
    const img = document.getElementById('proofModalImg');
    if (modal && img) {
        img.src = src;
        modal.classList.add('active');
    }
}
function closeProofModal(e) {
    const modal = document.getElementById('proofModal');
    if (modal) {
        modal.classList.remove('active');
    }
}
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
