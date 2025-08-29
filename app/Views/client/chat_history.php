<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/client.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/client-responsive.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/client-chat-history.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/date.css?v=' . time()) ?>">
<?= $this->endSection() ?>

<?php
// Helper function to build query string from current filters (excluding page parameter)
function buildQueryString() {
    $params = [];
    if (!empty($_GET['status'])) $params['status'] = $_GET['status'];
    if (!empty($_GET['date_from'])) $params['date_from'] = $_GET['date_from'];
    if (!empty($_GET['date_to'])) $params['date_to'] = $_GET['date_to'];
    if (!empty($_GET['search'])) $params['search'] = $_GET['search'];
    
    return empty($params) ? '' : '&' . http_build_query($params);
}
?>

<?= $this->section('content') ?>
<!-- Alerts -->
<?php if (session()->getFlashdata('error')): ?>
    <div class="container" style="padding-top: 1rem;">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= session()->getFlashdata('error') ?>
        </div>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="container" style="padding-top: 1rem;">
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?= session()->getFlashdata('success') ?>
        </div>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="client-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1>
                    <i class="bi bi-clock-history"></i>
                    <?= esc($title) ?>
                </h1>
                <p class="subtitle">View your past chat conversations and interactions</p>
            </div>
            <div class="header-actions">
                <a href="<?= base_url('client/dashboard') ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Back to Dashboard
                </a>
                <div class="user-badge">
                    <i class="bi bi-person-circle"></i>
                    <?= esc($user['username']) ?>
                    <span class="role-badge role-<?= $user['type'] ?? 'client' ?>">
                        <?= ucfirst($user['type'] ?? 'client') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Stats Overview -->
    <div class="stats-grid four-column fade-in">
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="bi bi-chat-dots-fill"></i>
            </div>
            <div class="stat-label">Total Sessions</div>
            <div class="stat-value"><?= isset($allSessions) ? count($allSessions) : count($sessions) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="stat-label">Completed</div>
            <div class="stat-value"><?= isset($allSessions) ? count(array_filter($allSessions, fn($s) => $s['status'] === 'closed')) : count(array_filter($sessions, fn($s) => $s['status'] === 'closed')) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="stat-label">Active</div>
            <div class="stat-value"><?= isset($allSessions) ? count(array_filter($allSessions, fn($s) => $s['status'] === 'active')) : count(array_filter($sessions, fn($s) => $s['status'] === 'active')) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-clock"></i>
            </div>
            <div class="stat-label">Waiting</div>
            <div class="stat-value"><?= isset($allSessions) ? count(array_filter($allSessions, fn($s) => $s['status'] === 'waiting')) : count(array_filter($sessions, fn($s) => $s['status'] === 'waiting')) ?></div>
        </div>
    </div>

    <!-- Chat Sessions Table -->
    <?php if (!empty($sessions)): ?>
    <div class="table-container fade-in">
        <div style="padding: 1.5rem 1.5rem 0;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title mb-0">
                    <i class="bi bi-chat-text"></i>
                    Chat Sessions
                </h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-secondary" id="filterBtn">
                        <i class="bi bi-funnel"></i>
                        Filter
                    </button>
                    <button class="btn btn-secondary" id="exportBtn">
                        <i class="bi bi-download"></i>
                        Export
                    </button>
                </div>
            </div>

            <!-- Filter Panel (initially hidden) -->
            <div id="filterPanel" class="filter-panel" style="display: none;">
                <form method="GET" class="filter-form">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="active" <?= isset($filters['status']) && $filters['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="waiting" <?= isset($filters['status']) && $filters['status'] == 'waiting' ? 'selected' : '' ?>>Waiting</option>
                                    <option value="closed" <?= isset($filters['status']) && $filters['status'] == 'closed' ? 'selected' : '' ?>>Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-control" value="<?= esc($filters['date_from'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-control" value="<?= esc($filters['date_to'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Customer name..." value="<?= esc($filters['search'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="<?= base_url('client/chat-history') ?>" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Accepted By</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Closed At</th>
                    <th>Client Last Reply</th>
                    <th>Agent Last Reply</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $session): ?>
                <tr>
                    <td>
                        <a href="<?= base_url('client/chat-history/view/' . $session['id']) ?>" class="session-link">
                            #<?= $session['id'] ?>
                        </a>
                    </td>
                    <td><?= esc($session['customer_name'] ?? 'Anonymous') ?></td>
                    <td><?= esc($session['customer_fullname'] ?? $session['customer_name'] ?? 'Anonymous') ?></td>
                    <td>
                        <?php if (!empty($session['accepted_by'])): ?>
                            <?= esc($session['accepted_by']) ?>
                        <?php elseif (!empty($session['agent_name'])): ?>
                            <?= esc($session['agent_name']) ?>
                        <?php elseif ($session['status'] !== 'waiting'): ?>
                            <span style="color: var(--text-secondary); font-style: italic;">Unassigned</span>
                        <?php else: ?>
                            <span style="color: var(--text-secondary); font-style: italic;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $statusClass = match($session['status']) {
                            'active' => 'active',
                            'waiting' => 'waiting', 
                            'closed' => 'closed',
                            default => 'closed'
                        };
                        ?>
                        <span class="status-badge <?= $statusClass ?>">
                            <?= strtoupper($session['status']) ?>
                        </span>
                    </td>
                    <td><?= date('M d, Y H:i', strtotime($session['created_at'])) ?></td>
                    <td>
                        <?php if ($session['closed_at']): ?>
                            <?= date('M d, Y H:i', strtotime($session['closed_at'])) ?>
                        <?php else: ?>
                            <span style="color: var(--text-secondary); font-style: italic;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($session['last_customer_message_time'])): ?>
                            <?= date('M d, Y H:i', strtotime($session['last_customer_message_time'])) ?>
                        <?php else: ?>
                            <span style="color: var(--text-secondary); font-style: italic;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($session['last_agent_message_time'])): ?>
                            <?= date('M d, Y H:i', strtotime($session['last_agent_message_time'])) ?>
                        <?php else: ?>
                            <span style="color: var(--text-secondary); font-style: italic;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= base_url('client/chat-history/view/' . $session['id']) ?>" 
                           class="btn btn-sm btn-info" title="View Details">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (isset($pagination) && $pagination['hasPages']): ?>
        <div class="d-flex justify-content-between align-items-center mt-3 px-3 pb-3">
            <div>
                <small class="text-muted">
                    Showing page <?= $pagination['currentPage'] ?> of <?= $pagination['totalPages'] ?> 
                    (<?= $pagination['totalRecords'] ?> total sessions)
                </small>
            </div>
            <nav>
                <ul class="pagination mb-0">
                    <?php if ($pagination['hasPrevious']): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= $pagination['baseUrl'] ?>?page=<?= $pagination['previousPage'] ?><?= buildQueryString() ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </span>
                        </li>
                    <?php endif; ?>
                    
                    <?php 
                    $startPage = max(1, $pagination['currentPage'] - 2);
                    $endPage = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                    
                    // Show first page if we're not starting from 1
                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= $pagination['baseUrl'] ?>?page=1<?= buildQueryString() ?>">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $pagination['currentPage'] ? 'active' : '' ?>">
                            <?php if ($i == $pagination['currentPage']): ?>
                                <span class="page-link"><?= $i ?></span>
                            <?php else: ?>
                                <a class="page-link" href="<?= $pagination['baseUrl'] ?>?page=<?= $i ?><?= buildQueryString() ?>"><?= $i ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>
                    
                    <?php 
                    // Show last page if we're not ending at the last page
                    if ($endPage < $pagination['totalPages']): ?>
                        <?php if ($endPage < $pagination['totalPages'] - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= $pagination['baseUrl'] ?>?page=<?= $pagination['totalPages'] ?><?= buildQueryString() ?>"><?= $pagination['totalPages'] ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($pagination['hasNext']): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= $pagination['baseUrl'] ?>?page=<?= $pagination['nextPage'] ?><?= buildQueryString() ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </span>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- No Chat Sessions State -->
    <div class="no-data slide-up">
        <div class="no-data-icon">
            <i class="bi bi-chat-text"></i>
        </div>
        <h3>No Chat Sessions Found</h3>
        <p>
            You don't have any chat sessions yet. Once customers start chatting through your API keys, 
            their conversations will appear here.
        </p>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Chat history is automatically generated when customers interact with your LiveChat widget.
        </div>
        <a href="<?= base_url('client/api-keys') ?>" class="btn btn-primary">
            <i class="bi bi-key"></i>
            View My API Keys
        </a>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    const filterBtn = document.getElementById('filterBtn');
    const filterPanel = document.getElementById('filterPanel');
    const exportBtn = document.getElementById('exportBtn');

    if (filterBtn && filterPanel) {
        filterBtn.addEventListener('click', function() {
            if (filterPanel.style.display === 'none' || filterPanel.style.display === '') {
                filterPanel.style.display = 'block';
                filterBtn.innerHTML = '<i class="bi bi-funnel-fill"></i> Filter';
            } else {
                filterPanel.style.display = 'none';
                filterBtn.innerHTML = '<i class="bi bi-funnel"></i> Filter';
            }
        });
    }

    // Export functionality
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            // Get current filter parameters
            const urlParams = new URLSearchParams(window.location.search);
            const exportUrl = new URL('<?= base_url('chat-history/export') ?>', window.location.origin);
            
            // Add filter parameters to export URL
            urlParams.forEach((value, key) => {
                exportUrl.searchParams.append(key, value);
            });
            
            // Trigger download
            window.location.href = exportUrl.toString();
        });
    }

    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined') {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                alert.style.display = 'none';
            }
        }, 5000);
    });

    // Update stats with animation every 30 seconds
    function updateStats() {
        // This could fetch updated stats via AJAX if needed
        // For now, just add a visual indicator that data is fresh
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.style.transform = 'scale(1.02)';
            setTimeout(() => {
                card.style.transform = 'scale(1)';
            }, 200);
        });
    }

    // Update stats every 30 seconds
    setInterval(updateStats, 30000);
});
</script>
<?= $this->endSection() ?>
