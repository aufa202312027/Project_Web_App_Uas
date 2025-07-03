<?php
/**
 * Admin Users Management - List Users
 * Halaman untuk mengelola data users
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check admin access
requireAdmin();

// Page configuration
$page_title = 'User Management - ' . APP_NAME;
$is_admin_page = true;
$include_datatables = true;

// Handle search and filters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = RECORDS_PER_PAGE;

// Build query conditions
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter)) {
    if ($status_filter === 'active') {
        $where_conditions[] = "is_active = 1";
    } elseif ($status_filter === 'inactive') {
        $where_conditions[] = "is_active = 0";
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
$total_records = getRecord($count_sql, $params)['total'];
$total_pages = ceil($total_records / $per_page);

// Get users with pagination
$offset = ($page - 1) * $per_page;
$sql = "SELECT id, username, email, full_name, role, is_active, created_at, updated_at 
        FROM users $where_clause 
        ORDER BY created_at DESC 
        LIMIT $per_page OFFSET $offset";

$users = getRecords($sql, $params);

// Get role statistics
$role_stats = [
    'admin' => getRecord("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1")['count'],
    'user' => getRecord("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND is_active = 1")['count'],
    'total_active' => getRecord("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'],
    'total_inactive' => getRecord("SELECT COUNT(*) as count FROM users WHERE is_active = 0")['count']
];

// Check for flash messages
$flash = getFlashMessage();

// Include header
include '../../includes/header.php';
?>

<main class="d-flex">
    <?php include '../../includes/admin_sidebar.php'; ?>
    
    <div class="admin-content flex-grow-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-users me-2"></i>User Management</h2>
                <p class="text-muted mb-0">Manage system users and their permissions</p>
            </div>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New User
            </a>
        </div>
        
        <!-- Flash Messages -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card-admin">
                    <div class="stat-header">
                        <div class="stat-title">Total Active Users</div>
                        <div class="stat-icon-admin">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($role_stats['total_active']) ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> Active
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card-admin success">
                    <div class="stat-header">
                        <div class="stat-title">Administrators</div>
                        <div class="stat-icon-admin">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($role_stats['admin']) ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-check"></i> Admin
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card-admin warning">
                    <div class="stat-header">
                        <div class="stat-title">Regular Users</div>
                        <div class="stat-icon-admin">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($role_stats['user']) ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-user"></i> Users
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card-admin danger">
                    <div class="stat-header">
                        <div class="stat-title">Inactive Users</div>
                        <div class="stat-icon-admin">
                            <i class="fas fa-user-slash"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($role_stats['total_inactive']) ?></div>
                    <div class="stat-change negative">
                        <i class="fas fa-times"></i> Inactive
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters and Search -->
        <div class="admin-table-container">
            <div class="table-header">
                <h5 class="table-title">
                    <i class="fas fa-users me-2"></i>Users List
                    <span class="badge bg-secondary ms-2"><?= number_format($total_records) ?></span>
                </h5>
                
                <div class="table-controls">
                    <form method="GET" class="d-flex gap-2 align-items-center">
                        <div class="search-box">
                            <input type="text" name="search" class="form-control form-control-sm" 
                                   placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        
                        <select name="role" class="form-select form-select-sm" style="width: auto;">
                            <option value="">All Roles</option>
                            <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>User</option>
                        </select>
                        
                        <select name="status" class="form-select form-select-sm" style="width: auto;">
                            <option value="">All Status</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-filter"></i>
                        </button>
                        
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i>
                        </a>
                    </form>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table admin-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Avatar</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="admin-empty-state">
                                                <i class="fas fa-users empty-icon"></i>
                                                <div class="empty-title">No Users Found</div>
                                                <div class="empty-description">
                                                    <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
                                                        Try adjusting your search criteria or filters.
                                                    <?php else: ?>
                                                        Start by adding your first user.
                                                    <?php endif; ?>
                                                </div>
                                                <a href="add.php" class="btn btn-primary mt-3">
                                                    <i class="fas fa-plus me-2"></i>Add User
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td>
                                                <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 35px; height: 35px; font-size: 14px;">
                                                    <?= strtoupper(substr($user['full_name'] ?: $user['username'], 0, 2)) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($user['full_name'] ?: '-') ?></td>
                                            <td>
                                                <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($user['email']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if (!empty($user['phone'])): ?>
                                                    <a href="tel:<?= htmlspecialchars($user['phone']) ?>" class="text-decoration-none">
                                                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($user['phone']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $user['role'] === 'admin' ? 'bg-success' : 'bg-primary' ?>">
                                                    <i class="fas fa-<?= $user['role'] === 'admin' ? 'shield-alt' : 'user' ?> me-1"></i>
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                                    <i class="fas fa-<?= $user['is_active'] ? 'check' : 'times' ?> me-1"></i>
                                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= formatDate($user['created_at'], 'd M Y') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="edit.php?id=<?= $user['id'] ?>" 
                                                       class="btn btn-sm btn-action edit" 
                                                       title="Edit User">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($user['id'] != getCurrentUserId()): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-action delete" 
                                                                title="Delete User"
                                                                onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" 
                                                            class="btn btn-sm btn-action view" 
                                                            title="View Details"
                                                            onclick="viewUser(<?= $user['id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-container">
                            <div class="pagination-info">
                                Showing <strong><?= number_format(($page - 1) * $per_page + 1) ?></strong> to 
                                <strong><?= number_format(min($page * $per_page, $total_records)) ?></strong> of 
                                <strong><?= number_format($total_records) ?></strong> entries
                            </div>
                            
                            <?= generatePagination($page, $total_pages, [
                                'search' => $search,
                                'role' => $role_filter,
                                'status' => $status_filter
                            ]) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- User Details Modal -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>User Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to <strong>permanently delete</strong> user <strong id="deleteUserName"></strong>?</p>
                    <p class="text-danger small">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Warning:</strong> This action cannot be undone. The user and all associated data will be permanently removed from the system.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="delete.php" style="display: inline;">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Permanently Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php 
    $page_js = "
        // Initialize DataTable
        if (typeof $.fn.DataTable !== 'undefined') {
            $('#usersTable').DataTable({
                responsive: true,
                pageLength: " . RECORDS_PER_PAGE . ",
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [1, 9] }
                ],
                language: {
                    search: 'Search users:',
                    lengthMenu: 'Show _MENU_ users per page',
                    info: 'Showing _START_ to _END_ of _TOTAL_ users',
                    emptyTable: 'No users found'
                }
            });
        }
        
        // Confirm delete function
        function confirmDelete(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = username;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // View user details
        function viewUser(userId) {
            const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
            const content = document.getElementById('userDetailsContent');
            
            content.innerHTML = '<div class=\"text-center\"><div class=\"spinner-border text-primary\" role=\"status\"><span class=\"visually-hidden\">Loading...</span></div></div>';
            modal.show();
            
            // Load user details via AJAX
            fetch('process.php?action=get_user&id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        content.innerHTML = `
                            <div class=\"row g-3\">
                                <div class=\"col-md-6\">
                                    <label class=\"form-label fw-bold\">Username:</label>
                                    <div>\${user.username}</div>
                                </div>
                                <div class=\"col-md-6\">
                                    <label class=\"form-label fw-bold\">Full Name:</label>
                                    <div>\${user.full_name || '-'}</div>
                                </div>
                                <div class=\"col-md-6\">
                                    <label class=\"form-label fw-bold\">Email:</label>
                                    <div><a href=\"mailto:\${user.email}\">\${user.email}</a></div>
                                </div>
                                <div class=\"col-md-6\">
                                    <label class=\"form-label fw-bold\">Role:</label>
                                    <div><span class=\"badge \${user.role === 'admin' ? 'bg-success' : 'bg-primary'}\">\${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</span></div>
                                </div>
                                <div class=\"col-md-6\">
                                    <label class=\"form-label fw-bold\">Status:</label>
                                    <div><span class=\"badge \${user.is_active ? 'bg-success' : 'bg-danger'}\">\${user.is_active ? 'Active' : 'Inactive'}</span></div>
                                </div>
                                <div class=\"col-md-6\">
                                    <label class=\"form-label fw-bold\">Phone:</label>
                                    <div>\${user.phone || '-'}</div>
                                </div>
                                <div class=\"col-md-6\">
                                    <label class=\"form-label fw-bold\">Created:</label>
                                    <div>\${new Date(user.created_at).toLocaleDateString()}</div>
                                </div>
                                <div class=\"col-md-6\">
                                    <label class=\"form-label fw-bold\">Last Updated:</label>
                                    <div>\${new Date(user.updated_at).toLocaleDateString()}</div>
                                </div>
                            </div>
                            <div class=\"mt-3 d-flex gap-2\">
                                <a href=\"edit.php?id=\${user.id}\" class=\"btn btn-primary btn-sm\">
                                    <i class=\"fas fa-edit me-1\"></i>Edit User
                                </a>
                                <button type=\"button\" class=\"btn btn-secondary btn-sm\" data-bs-dismiss=\"modal\">Close</button>
                            </div>
                        `;
                    } else {
                        content.innerHTML = '<div class=\"alert alert-danger\">Error loading user details.</div>';
                    }
                })
                .catch(error => {
                    content.innerHTML = '<div class=\"alert alert-danger\">Error loading user details.</div>';
                });
        }
        
        // Auto-submit form on filter change
        document.querySelectorAll('select[name=\"role\"], select[name=\"status\"]').forEach(select => {
            select.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });
    ";
    ?>
</body>
</html>
