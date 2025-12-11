<?php
// include repository
require_once 'RolesRepository.php';

// RoleService class - handles business logic and validation
class RoleService {
    
    // repository object
    private $roleRepository;
    
    // constructor - gets repository
    public function __construct($roleRepository) {
        $this->roleRepository = $roleRepository;
    }
    
    // validate role data
    public function validateRole($name, $slug, $status, $id = null) {
        
        $errors = array();
        
        // validate name
        if(empty($name)) {
            $errors[] = "Role name is required";
        } else if(strlen($name) < 3) {
            $errors[] = "Role name must be at least 3 characters";
        } else if(strlen($name) > 100) {
            $errors[] = "Role name must not exceed 100 characters";
        }
        
        // check if name already exists
        if(!empty($name) && $this->roleRepository->isNameExists($name, $id)) {
            $errors[] = "Role name already exists";
        }
        
        // validate slug
        if(empty($slug)) {
            $errors[] = "Slug is required";
        } else if(!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors[] = "Slug must contain only lowercase letters, numbers and hyphens";
        }
        
        // validate status
        if($status !== '0' && $status !== '1') {
            $errors[] = "Invalid status value";
        }
        
        return $errors;
    }
    
    // get all roles with pagination
    public function getRolesList($page = 1, $limit = 10, $name = '', $status = '') {
        
        // get roles from repository
        $roles = $this->roleRepository->getAllRoles($page, $limit, $name, $status);
        
        // get total count
        $totalCount = $this->roleRepository->getTotalCount($name, $status);
        
        // calculate total pages
        $totalPages = ceil($totalCount / $limit);
        
        // return data with pagination info
        return array(
            'roles' => $roles,
            'total_count' => $totalCount,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'limit' => $limit
        );
    }
    
    // get single role by id
    public function getRoleDetails($id) {
        
        // validate id
        if(empty($id) || !is_numeric($id)) {
            return null;
        }
        
        return $this->roleRepository->getRoleById($id);
    }
    
    // create new role
    public function createNewRole($name, $slug, $status) {
        
        // validate data
        $errors = $this->validateRole($name, $slug, $status);
        
        if(count($errors) > 0) {
            return array(
                'success' => false,
                'errors' => $errors
            );
        }
        
        // create role in database
        $roleId = $this->roleRepository->createRole($name, $slug, $status);
        
        if($roleId) {
            return array(
                'success' => true,
                'message' => 'Role created successfully',
                'role_id' => $roleId
            );
        } else {
            return array(
                'success' => false,
                'errors' => array('Failed to create role')
            );
        }
    }
    
    // update existing role
    public function updateExistingRole($id, $name, $slug, $status) {
        
        // validate id
        if(empty($id) || !is_numeric($id)) {
            return array(
                'success' => false,
                'errors' => array('Invalid role ID')
            );
        }
        
        // check if role exists
        $role = $this->roleRepository->getRoleById($id);
        if(!$role) {
            return array(
                'success' => false,
                'errors' => array('Role not found')
            );
        }
        
        // validate data
        $errors = $this->validateRole($name, $slug, $status, $id);
        
        if(count($errors) > 0) {
            return array(
                'success' => false,
                'errors' => $errors
            );
        }
        
        // update role in database
        $result = $this->roleRepository->updateRole($id, $name, $slug, $status);
        
        if($result) {
            return array(
                'success' => true,
                'message' => 'Role updated successfully'
            );
        } else {
            return array(
                'success' => false,
                'errors' => array('Failed to update role')
            );
        }
    }
    
    // delete role (soft delete)
    public function deleteRoleById($id) {
        
        // validate id
        if(empty($id) || !is_numeric($id)) {
            return array(
                'success' => false,
                'errors' => array('Invalid role ID')
            );
        }
        
        // check if role exists
        $role = $this->roleRepository->getRoleById($id);
        if(!$role) {
            return array(
                'success' => false,
                'errors' => array('Role not found')
            );
        }
        
        // delete role
        $result = $this->roleRepository->deleteRole($id);
        
        if($result) {
            return array(
                'success' => true,
                'message' => 'Role deleted successfully'
            );
        } else {
            return array(
                'success' => false,
                'errors' => array('Failed to delete role')
            );
        }
    }
    
    // restore deleted role
    public function restoreRoleById($id) {
        
        // validate id
        if(empty($id) || !is_numeric($id)) {
            return array(
                'success' => false,
                'errors' => array('Invalid role ID')
            );
        }
        
        // restore role
        $result = $this->roleRepository->restoreRole($id);
        
        if($result) {
            return array(
                'success' => true,
                'message' => 'Role restored successfully'
            );
        } else {
            return array(
                'success' => false,
                'errors' => array('Failed to restore role')
            );
        }
    }
    
    // generate slug from name
    public function generateSlug($name) {
        // convert to lowercase
        $slug = strtolower($name);

        // replace spaces with hyphens
        $slug = str_replace(' ', '-', $slug);

        // remove special characters
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

        // remove multiple hyphens
        $slug = preg_replace('/-+/', '-', $slug);

        return $slug;
    }

    public function generateUniqueSlug($name, $excludeId = null) {
        // generate base slug
        $baseSlug = $this->generateSlug($name);

        // check if base slug is available
        $slug = $baseSlug;
        $counter = 1;

        while ($this->isSlugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // check if slug already exists
    private function isSlugExists($slug, $excludeId = null) {
        return $this->roleRepository->isSlugExists($slug, $excludeId);
    }
}
?>