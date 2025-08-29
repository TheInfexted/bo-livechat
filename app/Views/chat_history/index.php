<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/chat-history.css') ?>?v=<?= time() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/responsive.css?v=' . time()) ?>">

<div class="chat-history-container">
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>
    
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <h2><?= esc($title) ?></h2>
            <div class="header-actions">
                <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>
    </div>

    <!-- API Key Selection -->
    <div class="api-key-selection fade-in">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="card-title">
                        <i class="bi bi-key me-2"></i>
                        Select API Key
                    </h5>
                    <p class="text-muted mb-0">Choose an API key to view its chat history. You can filter and search through all chat sessions for the selected client.</p>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="apiKeySelect">
                        <option value="">Select an API Key...</option>
                        <?php foreach ($api_keys as $key): ?>
                            <option value="<?= esc($key['api_key']) ?>">
                                <?= esc($key['client_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat History Management -->
    <div id="chatHistorySection" class="d-none slide-up">
        <div class="chat-history-section">
            <!-- Filters -->
            <div class="filters-section">
                <div class="filters-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search:</label>
                            <input type="text" id="search" name="search" placeholder="Username, full name, or agent...">
                        </div>
                        
                        <div class="filter-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <option value="waiting">Waiting</option>
                                <option value="active">Active</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from">Date From:</label>
                            <input type="date" id="date_from" name="date_from">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">Date To:</label>
                            <input type="date" id="date_to" name="date_to">
                        </div>
                        
                        <div class="filter-actions">
                            <button type="button" class="btn btn-primary filter-button" id="applyFilters">Filter</button>
                            <button type="button" class="btn btn-secondary filter-button" id="clearFilters">Clear</button>
                            <button type="button" class="btn btn-success" id="exportBtn">Export CSV</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Chat History Table -->
            <div class="table-container" id="tableContainer">
                <table class="chat-history-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Agent</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Closed</th>
                            <th>Client Last Reply</th>
                            <th>Agent Last Reply</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="chatHistoryTableBody">
                        <!-- Table rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div id="paginationContainer" class="pagination-container">
                <!-- Pagination will be populated by JavaScript -->
            </div>
        </div>

        <!-- No Chat History State -->
        <div id="noChatHistoryState" class="no-data d-none">
            <div class="no-data-icon">
                <i class="bi bi-chat-left-text"></i>
            </div>
            <h3>No Chat Sessions Found</h3>
            <p>No chat sessions were found for this API key. Chat sessions will appear here once customers start using the chat widget.</p>
        </div>
    </div>

    <!-- Initial State -->
    <div id="initialState" class="no-data">
        <div class="no-data-icon">
            <i class="bi bi-key"></i>
        </div>
        <h3>Select an API Key</h3>
        <p>Choose an API key from the dropdown above to view its chat history. Each API key represents a different client and their chat sessions.</p>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const apiKeySelect = document.getElementById('apiKeySelect');
    const chatHistorySection = document.getElementById('chatHistorySection');
    const initialState = document.getElementById('initialState');
    const chatHistoryTableBody = document.getElementById('chatHistoryTableBody');
    const noChatHistoryState = document.getElementById('noChatHistoryState');
    const tableContainer = document.getElementById('tableContainer');
    const paginationContainer = document.getElementById('paginationContainer');
    
    let currentApiKey = '';
    let currentPage = 1;
    let currentFilters = {};

    // API Key selection handler
    apiKeySelect.addEventListener('change', function() {
        const selectedApiKey = this.value;
        if (selectedApiKey) {
            currentApiKey = selectedApiKey;
            currentPage = 1;
            currentFilters = {};
            clearFilters();
            loadChatHistory(selectedApiKey, 1, {});
            
            // Show chat history section with animation
            chatHistorySection.classList.remove('d-none');
            chatHistorySection.classList.add('slide-up');
            initialState.classList.add('d-none');
        } else {
            chatHistorySection.classList.add('d-none');
            initialState.classList.remove('d-none');
        }
    });
    
    // Filter handlers
    document.getElementById('applyFilters').addEventListener('click', function() {
        if (!currentApiKey) {
            showAlert('warning', 'Please select an API key first');
            return;
        }
        
        currentFilters = {
            search: document.getElementById('search').value,
            status: document.getElementById('status').value,
            date_from: document.getElementById('date_from').value,
            date_to: document.getElementById('date_to').value
        };
        currentPage = 1;
        loadChatHistory(currentApiKey, 1, currentFilters);
    });
    
    document.getElementById('clearFilters').addEventListener('click', function() {
        if (!currentApiKey) return;
        
        clearFilters();
        currentFilters = {};
        currentPage = 1;
        loadChatHistory(currentApiKey, 1, {});
    });
    
    // Export functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        if (!currentApiKey) {
            showAlert('warning', 'Please select an API key first');
            return;
        }
        
        const params = new URLSearchParams({
            api_key: currentApiKey,
            ...currentFilters
        });
        const exportUrl = '<?= base_url("chat-history/export") ?>?' + params.toString();
        window.open(exportUrl, '_blank');
    });
    
    function clearFilters() {
        document.getElementById('search').value = '';
        document.getElementById('status').value = '';
        document.getElementById('date_from').value = '';
        document.getElementById('date_to').value = '';
    }
    
    function loadChatHistory(apiKey, page = 1, filters = {}) {
        // Show loading state
        chatHistoryTableBody.innerHTML = '<tr><td colspan="10" class="text-center py-4"><i class="bi bi-arrow-repeat spinner-border-sm me-2"></i>Loading chat history...</td></tr>';
        paginationContainer.innerHTML = '';
        
        const params = new URLSearchParams({
            api_key: apiKey,
            page: page,
            ...filters
        });
        
        fetch(`<?= base_url('chat-history/get-chat-history-for-api-key') ?>?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayChatHistory(data.chats);
                    displayPagination(data.pagination);
                } else {
                    showAlert('error', data.error || 'Failed to load chat history');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Failed to load chat history');
            });
    }
    
    function displayChatHistory(chats) {
        if (chats.length === 0) {
            chatHistoryTableBody.innerHTML = '';
            tableContainer.classList.add('d-none');
            paginationContainer.classList.add('d-none');
            noChatHistoryState.classList.remove('d-none');
            return;
        }

        tableContainer.classList.remove('d-none');
        paginationContainer.classList.remove('d-none');
        noChatHistoryState.classList.add('d-none');

        chatHistoryTableBody.innerHTML = chats.map(chat => `
            <tr class="chat-row status-${escapeHtml(chat.status)} fade-in">
                <td class="chat-id">#${escapeHtml(chat.id)}</td>
                <td class="username">
                    <span class="username-text">${escapeHtml(chat.username)}</span>
                </td>
                <td class="fullname">
                    ${chat.fullname ? escapeHtml(chat.fullname) : '<span class="anonymous">Anonymous</span>'}
                </td>
                <td class="agent">
                    ${chat.agent_name ? escapeHtml(chat.agent_name) : '<span class="unassigned">Unassigned</span>'}
                </td>
                <td class="status">
                    <span class="status-badge status-${escapeHtml(chat.status)}">
                        ${chat.status.charAt(0).toUpperCase() + chat.status.slice(1)}
                    </span>
                </td>
                <td class="date-time" title="${escapeHtml(chat.created_at)}">
                    ${new Date(chat.created_at).toLocaleDateString('en-US', {month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'})}
                </td>
                <td class="date-time" title="${escapeHtml(chat.closed_at || '')}">
                    ${chat.closed_at ? new Date(chat.closed_at).toLocaleDateString('en-US', {month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}) : '<span class="not-closed">-</span>'}
                </td>
                <td class="date-time" title="${escapeHtml(chat.client_last_reply || '')}">
                    ${chat.client_last_reply ? new Date(chat.client_last_reply).toLocaleDateString('en-US', {month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}) : '<span class="no-reply">-</span>'}
                </td>
                <td class="date-time" title="${escapeHtml(chat.agent_last_reply || '')}">
                    ${chat.agent_last_reply ? new Date(chat.agent_last_reply).toLocaleDateString('en-US', {month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}) : '<span class="no-reply">-</span>'}
                </td>
                <td class="actions">
                    <a href="<?= base_url('chat-history/view/') ?>${chat.id}" class="btn btn-sm btn-info" title="View Chat">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
        `).join('');
    }
    
    function displayPagination(pagination) {
        if (!pagination.hasPages) {
            paginationContainer.innerHTML = '';
            return;
        }
        
        let paginationHtml = '<nav aria-label="Page navigation"><ul class="pagination">';
        
        // Previous buttons
        if (pagination.hasPrevious) {
            paginationHtml += `
                <li class="page-item">
                    <a href="#" class="page-link" data-page="1" aria-label="First">
                        <span aria-hidden="true">« First</span>
                    </a>
                </li>
                <li class="page-item">
                    <a href="#" class="page-link" data-page="${pagination.previousPage}" aria-label="Previous">
                        <span aria-hidden="true">‹ Prev</span>
                    </a>
                </li>
            `;
        }
        
        // Page numbers
        const startPage = Math.max(1, pagination.currentPage - 2);
        const endPage = Math.min(pagination.totalPages, pagination.currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === pagination.currentPage ? ' active' : '';
            paginationHtml += `
                <li class="page-item${activeClass}">
                    <a href="#" class="page-link" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        // Next buttons
        if (pagination.hasNext) {
            paginationHtml += `
                <li class="page-item">
                    <a href="#" class="page-link" data-page="${pagination.nextPage}" aria-label="Next">
                        <span aria-hidden="true">Next ›</span>
                    </a>
                </li>
                <li class="page-item">
                    <a href="#" class="page-link" data-page="${pagination.totalPages}" aria-label="Last">
                        <span aria-hidden="true">Last »</span>
                    </a>
                </li>
            `;
        }
        
        paginationHtml += '</ul></nav>';
        paginationContainer.innerHTML = paginationHtml;
        
        // Add click handlers for pagination
        paginationContainer.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page && page !== currentPage) {
                    currentPage = page;
                    loadChatHistory(currentApiKey, page, currentFilters);
                }
            });
        });
    }
    
    function showAlert(type, message) {
        // Remove existing alerts
        document.querySelectorAll('.alert').forEach(alert => alert.remove());
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : type === 'error' ? 'exclamation-triangle-fill' : 'info-circle-fill'}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.querySelector('.chat-history-container').insertAdjacentElement('afterbegin', alertDiv);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                const alert = new bootstrap.Alert(alertDiv);
                alert.close();
            }
        }, 5000);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Auto-dismiss existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
});
</script>
<?= $this->endSection() ?>
