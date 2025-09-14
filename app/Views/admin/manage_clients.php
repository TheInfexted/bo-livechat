<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/manage-clients.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/responsive.css?v=' . time()) ?>">
<div class="admin-clients">
    <div class="dashboard-header">
        <h2>Manage Clients</h2>
        <div class="header-actions">
            <a href="<?= base_url('admin') ?>" class="btn btn-secondary">← Back to Dashboard</a>
            <?php if ($user['role'] === 'admin'): ?>
                <button class="btn btn-primary" onclick="openAddModal()">+ Add New Client</button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="clients-list">
        <table class="agents-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($clients)): ?>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?= esc($client['username']) ?></td>
                        <td><?= !empty($client['email']) ? esc($client['email']) : '<em>No email</em>' ?></td>
                        <td>
                            <span class="status-badge status-<?= $client['status'] ?? 'active' ?>">
                                <?= ucfirst($client['status'] ?? 'active') ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($client['created_at'])) ?></td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <button class="btn btn-sm btn-primary" onclick="openEditModal(<?= $client['id'] ?>, '<?= esc($client['username']) ?>', '<?= esc($client['email']) ?>', '<?= esc($client['status']) ?>')" title="Edit Client"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-danger" onclick="deleteClient(<?= $client['id'] ?>, '<?= esc($client['username']) ?>')" title="Delete Client"><i class="bi bi-trash"></i></button>
                            <?php else: ?>
                                <span class="text-muted">View Only</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No clients found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Client Modal -->
<div id="editClientModal" class="modal" style="display: none;">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h3>Edit Client</h3>
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="admin-modal-body">
            <form id="editClientForm">
                <input type="hidden" id="editClientId" name="client_id">
                <div class="form-group">
                    <label for="editUsername">Username</label>
                    <input type="text" id="editUsername" name="username" required>
                </div>
                <div class="form-group">
                    <label for="editEmail">Email (optional)</label>
                    <input type="email" id="editEmail" name="email">
                </div>
                <div class="form-group">
                    <label for="editStatus">Status</label>
                    <select id="editStatus" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editPassword">New Password (leave blank to keep current)</label>
                    <input type="password" id="editPassword" name="password" placeholder="Enter new password">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Client Modal -->
<div id="addClientModal" class="modal" style="display: none;">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h3>Add New Client</h3>
            <span class="close-modal" onclick="closeAddModal()">&times;</span>
        </div>
        <div class="admin-modal-body">
            <form id="addClientForm">
                <div class="form-group">
                    <label for="addUsername">Username</label>
                    <input type="text" id="addUsername" name="username" required>
                </div>
                <div class="form-group">
                    <label for="addEmail">Email (optional)</label>
                    <input type="email" id="addEmail" name="email">
                </div>
                <div class="form-group">
                    <label for="addStatus">Status</label>
                    <select id="addStatus" name="status" required>
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="addPassword">Password</label>
                    <input type="password" id="addPassword" name="password" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions
function openEditModal(clientId, username, email, status) {
    document.getElementById('editClientId').value = clientId;
    document.getElementById('editUsername').value = username;
    document.getElementById('editEmail').value = email || ''; // Handle null/empty emails
    document.getElementById('editStatus').value = status;
    document.getElementById('editPassword').value = '';
    document.getElementById('editClientModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editClientModal').style.display = 'none';
    document.getElementById('editClientForm').reset();
}

function openAddModal() {
    document.getElementById('addClientForm').reset();
    document.getElementById('addClientModal').style.display = 'block';
}

function closeAddModal() {
    document.getElementById('addClientModal').style.display = 'none';
    document.getElementById('addClientForm').reset();
}

// Close modals when clicking outside
window.onclick = function(event) {
    const editModal = document.getElementById('editClientModal');
    const addModal = document.getElementById('addClientModal');
    
    if (event.target === editModal) {
        closeEditModal();
    }
    if (event.target === addModal) {
        closeAddModal();
    }
}

// Form submission handlers
document.addEventListener('DOMContentLoaded', function() {
    // Edit client form submission
    document.getElementById('editClientForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('/admin/manage-clients/edit', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Client updated successfully!');
                closeEditModal();
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the client.');
        });
    });
    
    // Add client form submission
    document.getElementById('addClientForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('/admin/manage-clients/add', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Client added successfully!');
                closeAddModal();
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the client.');
        });
    });
});

function deleteClient(clientId, username) {
    if (!confirm(`Are you sure you want to delete client "${username}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('client_id', clientId);
    
    fetch('/admin/manage-clients/delete', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Client deleted successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the client.');
    });
}
</script>
<?= $this->endSection() ?> 
