<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/keyword-responses.css?v=' . time()) ?>">
<div class="admin-dashboard">
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
    
    <div class="dashboard-header">
        <h2><?= esc($title) ?></h2>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="showAddModal()">Add New Response</button>
            <a href="<?= base_url('admin') ?>" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <div class="keyword-responses-container">
        <div class="responses-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>Response</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($responses)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No automated responses found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($responses as $response): ?>
                            <tr>
                                <td><strong><?= esc($response['keyword']) ?></strong></td>
                                <td class="response-preview"><?= esc(substr($response['response'], 0, 100)) ?><?= strlen($response['response']) > 100 ? '...' : '' ?></td>
                                <td>
                                    <span class="status-badge <?= $response['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $response['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($response['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="editResponse(<?= $response['id'] ?>)">Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteResponse(<?= $response['id'] ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="responseModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Response</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form id="responseForm" method="post" action="<?= base_url('admin/save-keyword-response') ?>">
            <input type="hidden" id="responseId" name="id" value="">
            
            <div class="form-group">
                <label for="keyword">Keyword/Phrase:</label>
                <input type="text" id="keyword" name="keyword" required 
                       placeholder="e.g., hello, refund, help" class="form-control">
                <small class="form-text">Keywords are case-insensitive and will trigger when found in customer messages</small>
            </div>
            
            <div class="form-group">
                <label for="response">Auto Response:</label>
                <textarea id="response" name="response" required rows="4" 
                          placeholder="Enter the automated response message..." class="form-control"></textarea>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                    Active (response will be sent automatically)
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Response</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>


<script>
function showAddModal() {
    document.getElementById('responseModal').style.display = 'block';
    document.getElementById('modalTitle').textContent = 'Add New Response';
    document.getElementById('responseForm').reset();
    document.getElementById('responseId').value = '';
    document.getElementById('is_active').checked = true;
}

function editResponse(id) {
    // Fetch response data and populate modal
    fetch(`<?= base_url('admin/get-keyword-response') ?>/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('responseModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Response';
            document.getElementById('responseId').value = data.id;
            document.getElementById('keyword').value = data.keyword;
            document.getElementById('response').value = data.response;
            document.getElementById('is_active').checked = data.is_active == 1;
        })
        .catch(error => {
            alert('Error loading response data');
            console.error('Error:', error);
        });
}

function deleteResponse(id) {
    if (confirm('Are you sure you want to delete this automated response?')) {
        fetch('<?= base_url('admin/delete-keyword-response') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting response');
            }
        })
        .catch(error => {
            alert('Error deleting response');
            console.error('Error:', error);
        });
    }
}

function closeModal() {
    document.getElementById('responseModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('responseModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?= $this->endSection() ?>
