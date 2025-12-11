<?php
// include required files
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
require_once '../../includes/RoleService.php';

// start session
startSession();

// set page title
$pageTitle = "Roles Management";

// get database connection
$database = new Database();
$db = $database->getConnection();

// create service
$roleRepo = new RoleRepository($db);
$roleService = new RoleService($roleRepo);

// get filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // items per page
$filterName = isset($_GET['name']) ? sanitizeInput($_GET['name']) : '';
$filterStatus = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// get roles list
$result = $roleService->getRolesList($page, $limit, $filterName, $filterStatus);
$roles = $result['roles'];
$totalPages = $result['total_pages'];
$totalCount = $result['total_count'];

// include header
include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2><i class="fas fa-user-tag"></i> Roles Management</h2>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Role
            </a>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="row">
    <div class="col-12">
        <div class="filter-section">
            <form method="GET" action="" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Role Name</label>
                        <input type="text" name="name" class="form-control" 
                               placeholder="Search by name" value="<?php echo htmlspecialchars($filterName); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="1" <?php echo $filterStatus === '1' ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $filterStatus === '0' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Roles Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Roles List 
                    <span class="badge bg-light text-dark"><?php echo $totalCount; ?> Total</span>
                </h5>
            </div>
            <div class="card-body">
                
                <?php if(count($roles) > 0): ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($roles as $role): ?>
                            <tr>
                                <td><?php echo $role->getId(); ?></td>
                                <td><?php echo htmlspecialchars($role->getName()); ?></td>
                                <td><code><?php echo htmlspecialchars($role->getSlug()); ?></code></td>
                                <td><?php echo getStatusBadge($role->getStatus()); ?></td>
                                <td><?php echo formatDateShort($role->getCreatedAt()); ?></td>
                                <td class="table-actions">
                                    <a href="view.php?id=<?php echo $role->getId(); ?>" 
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $role->getId(); ?>" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="deleteRole(<?php echo $role->getId(); ?>)" 
                                            class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        
                        <!-- Previous Button -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&name=<?php echo $filterName; ?>&status=<?php echo $filterStatus; ?>">
                                Previous
                            </a>
                        </li>
                        
                        <!-- Page Numbers -->
                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&name=<?php echo $filterName; ?>&status=<?php echo $filterStatus; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <!-- Next Button -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&name=<?php echo $filterName; ?>&status=<?php echo $filterStatus; ?>">
                                Next
                            </a>
                        </li>
                        
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No roles found. 
                        <a href="add.php">Add your first role</a>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<!-- Delete Role Script -->
<script>
function deleteRole(roleId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You want to delete this role?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // redirect to delete page
            window.location.href = 'delete.php?id=' + roleId;
        }
    });
}
</script>

<?php
// include footer
include '../../includes/footer.php';
?>