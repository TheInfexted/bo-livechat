<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> - <?= esc($user['username']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/client-keyword-responses.css?v=' . time()) ?>">
</head>
<body>
    <!-- Header -->
    <div class="client-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>
                        <i class="bi bi-chat-square-text"></i>
                        <?= esc($title) ?>
                    </h1>
                    <p class="subtitle">Manage automated keyword responses for your chat sessions</p>
                </div>
                <div class="header-actions">
                    <div class="user-badge">
                        <i class="bi bi-person-circle"></i>
                        <?= esc($user['username']) ?>
                        <span class="role-badge role-<?= $user['type'] ?? 'client' ?>">
                            <?= ucfirst($user['type'] ?? 'client') ?>
                        </span>
                    </div>
                    <a href="<?= base_url('client/dashboard') ?>" class="btn btn-outline-light me-3">
                        <i class="bi bi-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <!-- Alerts -->
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger fade-in">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>
            
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success fade-in">
                    <i class="bi bi-check-circle-fill"></i>
                    <?= session()->getFlashdata('success') ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Action Header -->
        <div class="action-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="section-header">
                        <i class="bi bi-robot"></i>
                        Automated Responses
                    </h2>
                    <p class="section-description">
                        Set up keyword-triggered responses that automatically reply to your customers
                    </p>
                </div>
                <button class="btn btn-primary fade-in" onclick="showAddModal()">
                    <i class="bi bi-plus-lg"></i>
                    New Response
                </button>
            </div>
        </div>
        
        <!-- Keyword Responses Table -->
        <div class="main-content">
            <div class="keyword-responses-container fade-in">
            <div class="responses-table">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <i class="bi bi-key-fill me-2"></i>
                                    Keyword
                                </th>
                                <th>
                                    <i class="bi bi-chat-quote me-2"></i>
                                    Response Preview
                                </th>
                                <th>
                                    <i class="bi bi-toggle-on me-2"></i>
                                    Status
                                </th>
                                <th>
                                    <i class="bi bi-calendar3 me-2"></i>
                                    Created
                                </th>
                                <th>
                                    <i class="bi bi-gear me-2"></i>
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($responses)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="empty-state-content">
                                            <i class="bi bi-robot empty-state-icon"></i>
                                            <h4 class="empty-state-title">No Automated Responses Yet</h4>
                                            <p class="empty-state-description">
                                                Create your first keyword response to automatically reply to common customer inquiries.
                                            </p>
                                            <button class="btn btn-primary" onclick="showAddModal()">
                                                <i class="bi bi-plus-lg"></i>
                                                Create First Response
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($responses as $response): ?>
                                    <tr>
                                        <td>
                                            <strong class="keyword-text">
                                                <?= esc($response['keyword']) ?>
                                            </strong>
                                        </td>
                                        <td class="response-preview">
                                            <?= esc(strlen($response['response']) > 100 ? substr($response['response'], 0, 100) . '...' : $response['response']) ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $response['is_active'] ? 'active' : 'inactive' ?>">
                                                <i class="bi bi-<?= $response['is_active'] ? 'check-circle-fill' : 'pause-circle-fill' ?>"></i>
                                                <?= $response['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="date-text">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?= date('M d, Y', strtotime($response['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="editResponse(<?= $response['id'] ?>)" title="Edit Response">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteResponse(<?= $response['id'] ?>)" title="Delete Response">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Info Card -->
            <?php if (!empty($responses)): ?>
            <div class="info-card fade-in">
                <div class="info-card-icon">
                    <i class="bi bi-info-circle-fill"></i>
                </div>
                <div>
                    <h4 class="info-card-title">How Keyword Responses Work</h4>
                    <p class="info-card-text">
                        When customers send messages containing your specified keywords, the system will automatically send your predefined responses. 
                        Keywords are case-insensitive and will trigger when found anywhere in the customer's message. Only active responses will be sent automatically.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="responseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">
                    <i class="bi bi-plus-circle-fill"></i>
                    Add New Response
                </h3>
                <button class="close" onclick="closeModal()" type="button" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form id="responseForm" method="post" action="<?= base_url('client/save-keyword-response') ?>">
                <input type="hidden" id="responseId" name="id" value="">
                
                <div class="form-group">
                    <label for="keyword">
                        <i class="bi bi-key-fill me-1"></i>
                        Keyword or Phrase
                    </label>
                    <input type="text" id="keyword" name="keyword" required 
                           placeholder="e.g., hello, refund, help, support" class="form-control">
                    <small class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        Enter a word or phrase that will trigger this response. Keywords are case-insensitive.
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="response">
                        <i class="bi bi-chat-quote me-1"></i>
                        Automated Response Message
                    </label>
                    <textarea id="response" name="response" required rows="4" 
                              placeholder="Enter the message that will be sent automatically when the keyword is detected..." 
                              class="form-control"></textarea>
                    <small class="form-text">
                        <i class="bi bi-lightbulb me-1"></i>
                        Write a helpful response that addresses the customer's likely concern or question.
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <span>
                            <i class="bi bi-toggle-on me-1"></i>
                            Active (response will be sent automatically)
                        </span>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="bi bi-x-circle"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i>
                        Save Response
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function showAddModal() {
            document.getElementById('responseModal').style.display = 'block';
            document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> Add New Response';
            document.getElementById('responseForm').reset();
            document.getElementById('responseId').value = '';
            document.getElementById('is_active').checked = true;
            
            // Add fade-in animation
            const modal = document.getElementById('responseModal');
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.transition = 'opacity 0.3s ease';
            }, 10);
        }

        function editResponse(id) {
            // Show loading state
            const modal = document.getElementById('responseModal');
            modal.style.display = 'block';
            document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit Response <div class="loading-spinner loading-spinner-inline"></div>';
            
            // Fetch response data and populate modal
            fetch(`<?= base_url('client/get-keyword-response') ?>/${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit Response';
                    document.getElementById('responseId').value = data.id;
                    document.getElementById('keyword').value = data.keyword;
                    document.getElementById('response').value = data.response;
                    document.getElementById('is_active').checked = data.is_active == 1;
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading response data. Please try again.');
                    closeModal();
                });
        }

        function deleteResponse(id) {
            if (confirm('Are you sure you want to delete this automated response? This action cannot be undone.')) {
                // Show loading state
                const deleteBtn = event.target.closest('button');
                const originalContent = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<div class="loading-spinner"></div>';
                deleteBtn.disabled = true;
                
                fetch('<?= base_url('client/delete-keyword-response') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Add fade-out animation before reload
                        const row = deleteBtn.closest('tr');
                        row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(-20px)';
                        
                        setTimeout(() => {
                            location.reload();
                        }, 300);
                    } else {
                        alert('Error deleting response. Please try again.');
                        deleteBtn.innerHTML = originalContent;
                        deleteBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting response. Please try again.');
                    deleteBtn.innerHTML = originalContent;
                    deleteBtn.disabled = false;
                });
            }
        }

        function closeModal() {
            const modal = document.getElementById('responseModal');
            modal.style.transition = 'opacity 0.3s ease';
            modal.style.opacity = '0';
            
            setTimeout(() => {
                modal.style.display = 'none';
                modal.style.opacity = '1';
                modal.style.transition = '';
            }, 300);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('responseModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Handle escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('responseModal');
                if (modal.style.display === 'block') {
                    closeModal();
                }
            }
        });

        // Form validation
        document.getElementById('responseForm').addEventListener('submit', function(event) {
            const keyword = document.getElementById('keyword').value.trim();
            const response = document.getElementById('response').value.trim();
            
            if (!keyword || !response) {
                event.preventDefault();
                alert('Please fill in both the keyword and response fields.');
                return;
            }
            
            if (keyword.length > 255) {
                event.preventDefault();
                alert('Keyword cannot be longer than 255 characters.');
                return;
            }
            
            // Show loading state on submit button
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="loading-spinner"></div> Saving...';
            submitBtn.disabled = true;
        });

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>

</body>
</html>
