<?php
require_once '../../includes/repo/repository.php';

class RoleService
{
    private $repo;

    public function __construct($db)
    {
        $this->repo = new Repository($db, 'roles');
    }

    // get all roles
    public function getAllRoles($showDeleted = false)
    {
        if ($showDeleted) {
            // active + deleted
            $sql = "SELECT * FROM roles ORDER BY id DESC";
            $stmt = $this->repo->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // only active
        return $this->repo->getAll();
    }

    // get active role only
    public function getRole($id)
    {
        return $this->repo->getOne($id);
    }

    public function createRole($name, $slug, $status)
    {
        $errors = $this->validateRole($name, $slug, $status);

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors
            ];
        }

        $data = [
            'name'   => $name,
            'slug'   => $slug,
            'status' => 0
        ];

        $id = $this->repo->insert($data);

        if ($id) {
            return [
                'success' => true,
                'message' => 'Role created successfully',
                'id'      => $id
            ];
        }

        return [
            'success' => false,
            'errors'  => ['Failed to create role']
        ];
    }

    public function updateRole($id, $name, $slug, $status)
    {
        // fetch even if deleted
        $existing = $this->repo->getById($id);

        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['Role not found']
            ];
        }

        if ($existing['name'] === 'Super Admin' && (int)$status === 0) {
            return [
                'success' => false,
                'errors'  => ['Super Admin role cannot be deactivated']
            ];
        }

        $errors = $this->validateRole($name, $slug, $status, $id);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors
            ];
        }

        $updated = $this->repo->update($id, [
            'name'   => $name,
            'slug'   => $slug
        ]);

        if ($updated) {
            return [
                'success' => true,
                'message' => 'Role updated successfully'
            ];
        }

        return [
            'success' => false,
            'errors'  => ['Failed to update role']
        ];
    }

    public function deleteRole($id)
    {
        // only active role can be deleted
        $existing = $this->repo->getOne($id);

        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['Role not found']
            ];
        }

        if ($existing['name'] === 'Super Admin') {
            return [
                'success' => false,
                'errors'  => ['Super Admin role cannot be deleted']
            ];
        }

        if ($this->repo->delete($id)) {
            return [
                'success' => true,
                'message' => 'Role deleted successfully'
            ];
        }

        return [
            'success' => false,
            'errors'  => ['Failed to delete role']
        ];
    }

    public function restoreRole($id)
    {
        // ONLY deleted role
        $existing = $this->repo->getDeletedById($id);

        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['Role not found or already active']
            ];
        }

        if ($existing['name'] === 'Super Admin') {
            return [
                'success' => false,
                'errors'  => ['Super Admin role cannot be restored']
            ];
        }

        // restore deleted_at
        $restored = $this->repo->restore($id);

        if ($restored) {

            $this->repo->update($id, [
                'status' => 1
            ]);

            return [
                'success' => true,
                'message' => 'Role restored successfully'
            ];
        }

        return [
            'success' => false,
            'errors'  => ['Failed to restore role']
        ];
    }


    private function validateRole($name, $slug, $status, $skipId = null)
    {
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required';
        } elseif (strlen($name) < 3) {
            $errors[] = 'Name must be at least 3 characters long';
        } elseif (strlen($name) > 50) {
            $errors[] = 'Name must not exceed 50 characters';
        } elseif ($this->repo->exists('name', $name, $skipId)) {
            $errors[] = 'Name already exists';
        }

        if (empty($slug)) {
            $errors[] = 'Slug is required';
        } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors[] = 'Slug can only contain lowercase letters, numbers, and hyphens';
        }

        if ($status !== '0' && $status !== '1' && $status !== 0 && $status !== 1) {
            $errors[] = 'Status must be either Active or Inactive';
        }

        return $errors;
    }

    public function generateSlug($name)
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return rtrim($slug, '-');
    }

    public function isRoleAssignedToUsers($roleId)
    {
        $sql = "SELECT COUNT(*) 
            FROM users 
            WHERE role_id = ?
              AND deleted_at IS NULL";

        $stmt = $this->repo->db->prepare($sql);
        $stmt->execute([$roleId]);

        return $stmt->fetchColumn() > 0;
    }


    public function syncRoleStatus($roleId)
    {
        $isAssigned = $this->isRoleAssignedToUsers($roleId);

        $status = $isAssigned ? 1 : 0;

        $sql = "UPDATE roles SET status = ? WHERE id = ?";
        $stmt = $this->repo->db->prepare($sql);
        $stmt->execute([$status, $roleId]);
    }

    // RoleService.php
    public function getRolesWithActiveUserCount()
    {
        $sql = "
        SELECT r.*,
               COUNT(u.id) AS active_users
        FROM roles r
        LEFT JOIN users u
            ON u.role_id = r.id
           AND u.deleted_at IS NULL
        GROUP BY r.id
        ORDER BY r.id DESC
    ";

        $stmt = $this->repo->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
