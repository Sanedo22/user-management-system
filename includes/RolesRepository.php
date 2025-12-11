<?php
// include Role class
require_once 'Role.php';

// RoleRepository class - handles all database operations for roles
class RoleRepository {
    
    // database connection
    private $conn;
    
    // constructor - gets database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // get all roles (with pagination and filters)
    public function getAllRoles($page = 1, $limit = 10, $name = '', $status = '') {
        
        // build query
        $query = "SELECT * FROM roles WHERE deleted_at IS NULL";
        
        // add name filter if provided
        if(!empty($name)) {
            $query .= " AND name LIKE :name";
        }
        
        // add status filter if provided
        if($status !== '') {
            $query .= " AND status = :status";
        }
        
        // add ordering
        $query .= " ORDER BY created_at DESC";
        
        // calculate offset for pagination
        $offset = ($page - 1) * $limit;
        $query .= " LIMIT :limit OFFSET :offset";
        
        // prepare query
        $stmt = $this->conn->prepare($query);
        
        // bind name parameter if needed
        if(!empty($name)) {
            $searchName = "%{$name}%";
            $stmt->bindParam(':name', $searchName);
        }
        
        // bind status parameter if needed
        if($status !== '') {
            $stmt->bindParam(':status', $status);
        }
        
        // bind pagination parameters
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        // execute query
        $stmt->execute();
        
        // create array to store roles
        $roles = array();
        
        // fetch all roles and convert to Role objects
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $role = new Role();
            $role->setId($row['id']);
            $role->setName($row['name']);
            $role->setSlug($row['slug']);
            $role->setStatus($row['status']);
            $role->setCreatedAt($row['created_at']);
            $role->setUpdatedAt($row['updated_at']);
            $role->setDeletedAt($row['deleted_at']);
            
            $roles[] = $role;
        }
        
        return $roles;
    }
    
    // get total count of roles (for pagination)
    public function getTotalCount($name = '', $status = '') {
        
        $query = "SELECT COUNT(*) as total FROM roles WHERE deleted_at IS NULL";
        
        // add filters
        if(!empty($name)) {
            $query .= " AND name LIKE :name";
        }
        
        if($status !== '') {
            $query .= " AND status = :status";
        }
        
        $stmt = $this->conn->prepare($query);
        
        // bind parameters
        if(!empty($name)) {
            $searchName = "%{$name}%";
            $stmt->bindParam(':name', $searchName);
        }
        
        if($status !== '') {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'];
    }
    
    // get role by id
    public function getRoleById($id) {
        
        $query = "SELECT * FROM roles WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $role = new Role();
            $role->setId($row['id']);
            $role->setName($row['name']);
            $role->setSlug($row['slug']);
            $role->setStatus($row['status']);
            $role->setCreatedAt($row['created_at']);
            $role->setUpdatedAt($row['updated_at']);
            $role->setDeletedAt($row['deleted_at']);
            
            return $role;
        }
        
        return null;
    }
    
    // create new role
    public function createRole($name, $slug, $status) {
        
        $query = "INSERT INTO roles (name, slug, status) VALUES (:name, :slug, :status)";
        $stmt = $this->conn->prepare($query);
        
        // bind parameters
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':status', $status);
        
        // execute query
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // update role
    public function updateRole($id, $name, $slug, $status) {
        
        $query = "UPDATE roles SET name = :name, slug = :slug, status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        // bind parameters
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':status', $status);
        
        // execute query
        return $stmt->execute();
    }
    
    // soft delete role
    public function deleteRole($id) {
        
        $query = "UPDATE roles SET deleted_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    // restore deleted role
    public function restoreRole($id) {
        
        $query = "UPDATE roles SET deleted_at = NULL WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    // check if role name already exists
    public function isNameExists($name, $excludeId = null) {
        
        $query = "SELECT COUNT(*) as total FROM roles WHERE name = :name AND deleted_at IS NULL";
        
        // exclude current role id when updating
        if($excludeId) {
            $query .= " AND id != :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        
        if($excludeId) {
            $stmt->bindParam(':id', $excludeId);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'] > 0;
    }
}
?>