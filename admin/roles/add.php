<?php
// include required files
require_once '../../config/database.php';
require_once '../../includes/helpers.php';
require_once '../../includes/RoleService.php';

// start session
startSession();

// set page title
$pageTitle = "Add New Role";

// get database connection
$database = new Database();
$db = $database->getConnection();

// create service
$roleRepo = new RoleRepository($db);
$roleService = new RoleService($roleRepo);

// initialize variables
$name = '';
$slug = '';
$status = '1'; // default to active
$errors = array();

// handle form submission
if(isPostRequest()) {

    // get form data
    $name = getPostData('name');
    $slug = getPostData('slug');
    $status = getPostData('status');

    // verify CSRF token
    $csrfToken = getPostData('csrf_token');
    if(!verifyCsrfToken($csrfToken)) {
        $errors[] = "Invalid request. Please try again.";
    }

    // if no errors so far, proceed with creation
    if(empty($errors)) {

        // if slug is empty, generate unique slug from name
        if(empty($slug) && !empty($name)) {
            $slug = $roleService->generateUniqueSlug($name);
        }

        // create role
        $result = $roleService->createNewRole($name, $slug, $status);

        if($result['success']) {
            // success - set flash message and redirect
            setFlashMessage('success', 'Role created successfully!');
            redirect('list.php');
        } else {
            // errors from service
            $errors = $result['errors'];
        }
    }
}

// include header
include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2><i class="fas fa-plus"></i> Add New Role</h2>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Roles List
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-tag"></i> Role Information</h5>
            </div>
            <div class="card-body">

                <!-- Display Errors -->
                <?php displayErrors($errors); ?>

                <!-- Add Role Form -->
                <form method="POST" action="" id="addRoleForm">

                    <!-- CSRF Token -->
                    <?php echo csrfField(); ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Role Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?php echo htmlspecialchars($name); ?>" required
                               placeholder="Enter role name (e.g., Administrator)">
                        <div class="form-text">The display name for the role.</div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="1" <?php echo $status == '1' ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $status == '0' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <div class="form-text">Choose whether this role should be active or inactive.</div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-secondary me-md-2" onclick="window.location.href='list.php'">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Role
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<!-- Auto-generate slug script -->
<script>
document.getElementById('name').addEventListener('input', function() {
    var name = this.value;
    var slug = name.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '') // remove special chars
        .replace(/\s+/g, '-') // replace spaces with hyphens
        .replace(/-+/g, '-') // replace multiple hyphens with single
        .replace(/^-|-$/g, ''); // remove leading/trailing hyphens

    document.getElementById('slug').value = slug;
});
</script>

<?php
// include footer
include '../../includes/footer.php';
?>
