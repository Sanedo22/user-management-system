<?php
require_once 'repository.php';
class RoleService
{

    private $repo;

    public function __construct($db)
    {
        $this->repo = new repository($db, 'roles');
    }

    //get all roles
    public function getAllRoles($showDeleted = false){
    if ($showDeleted) {
        // show all (active + deleted)
        $sql = "SELECT * FROM roles ORDER BY id DESC";
        $stmt = $this->repo->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // only active
    return $this->repo->getAll();
}


    //get one role
    public function getRole($id)
    {
        return $this->repo->getOne($id);
    }

    //create role
    public function createRole($name, $slug, $status)
    {
        $errors = $this->validateRole($name, $slug, $status);

        if (count($errors) > 0) {
            return array(
                'success' => false,
                'errors' => $errors
            );
        }
        $data = array(
            'name' => $name,
            'slug' => $slug,
            'status' => $status,
        );

        $newId = $this->repo->insert($data);

        if ($newId) {
            return array(
                'success' => true,
                'message' => 'Role created successfully',
                'id' => $newId
            );
        }

        return array(
            'success' => false,
            'errors' => array('Failed to create role')
        );
    }

    //update role
    public function updateRole($id, $name, $slug, $status)
    {
        $existing = $this->repo->getById($id);
        if (!$existing) {
            return array(
                'success' => false,
                'errors' => array('Role not found')
            );
        }

        $errors = $this->validateRole($name, $slug, $status, $id);
        if (count($errors) > 0) {
            return array(
                'success' => false,
                'errors' => $errors
            );
        }
        $data = array(
            'name' => $name,
            'slug' => $slug,
            'status' => $status,
        );
        $updated = $this->repo->update($id, $data);

        if ($updated) {
            return array(
                'success' => true,
                'message' => 'Role updated successfully'
            );
        }
        return array(
            'success' => false,
            'errors' => array('Failed to update role')
        );
    }

    //delete role
    public function deleteRole($id)
    {
        $existing = $this->repo->getOne($id);
        if (!$existing) {
            return array(
                'success' => false,
                'errors' => array('Role not found')
            );
        }

        $deleted = $this->repo->delete($id);
        if ($deleted) {
            return array(
                'success' => true,
                'message' => 'Role deleted successfully'
            );
        }
        return array(
            'success' => false,
            'errors' => array('Failed to delete role')
        );
    }

    //restore role
    public function restoreRole($id)
    {
        $existing = $this->repo->getById($id);
        if (!$existing) {
            return array(
                'success' => false,
                'errors' => array('Role not found')
            );
        }

        $restored = $this->repo->restore($id);
        if ($restored) {
            return array(
                'success' => true,
                'message' => 'Role restored successfully'
            );
        }
        return array(
            'success' => false,
            'errors' => array('Failed to restore role')
        );
    }

    //validate role data
    private function validateRole($name, $slug, $status, $skipId = null)
    {
        $errors = array();

        // validate name
        if (empty($name)) {
            $errors[] = "Name is required";
        } else if (strlen($name) < 3) {
            $errors[] = "Name must be at least 3 characters long";
        } else if (strlen($name) > 50) {
            $errors[] = "Name must not exceed 50 characters";
        } else if ($this->repo->exists('name', $name, $skipId)) {
            $errors[] = "Name already exists";
        }

        // validate slug
        if (empty($slug)) {
            $errors[] = "Slug is required";
        } else if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors[] = "Slug can only contain lowercase letters, numbers, and hyphens";
        }

        // validate status
        if ($status !== '0' && $status !== '1' && $status !== 0 && $status !== 1) {
            $errors[] = "Status must be either Active or Inactive";
        }

        return $errors;
    }


    //generate slug from name
    public function generateSlug($name)
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return rtrim($slug, '-');
    }
}
