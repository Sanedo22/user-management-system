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

<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 20px;
    background-color: #f4f4f4;
}
.container {
    max-width: 800px;
    margin: 0 auto;
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.header h2 {
    margin: 0;
}
.btn {
    display: inline-block;
    padding: 10px 20px;
    text-decoration: none;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}
.btn-secondary {
    background-color: #6c757d;
    color: white;
}
.btn-primary {
    background-color: #007bff;
    color: white;
}
.form-group {
    margin-bottom: 15px;
}
label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
input[type="text"], select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.form-text {
    font-size: 12px;
    color: #666;
}
.text-danger {
    color: red;
}
.button-group {
    text-align: right;
    margin-top: 20px;
}
.button-group button {
    margin-left: 10px;
}
</style>

<div class="container">
    <div class="header">
        <h2>Add New Role</h2>
        <a href="list.php" class="btn btn-secondary">Back to Roles List</a>
    </div>

    <div>
        <!-- Display Errors -->
        <?php displayErrors($errors); ?>

        <!-- Add Role Form -->
        <form method="POST" action="" id="addRoleForm">

            <!-- CSRF Token -->
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label for="name">
                    Role Name <span class="text-danger">*</span>
                </label>
                <input type="text" id="name" name="name"
                       value="<?php echo htmlspecialchars($name); ?>" required
                       placeholder="Enter role name">
                <div class="form-text">The display name for the role.</div>
            </div>

            <div class="form-group">
                <label for="status">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" required>
                    <option value="1" <?php echo $status == '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $status == '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <div class="form-text">Choose whether this role should be active or inactive.</div>
            </div>

            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='list.php'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Role</button>
            </div>

        </form>

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
