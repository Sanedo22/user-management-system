<?php
// Role entity class
class Role {
    
    // role properties (columns from database table)
    public $id;
    public $name;
    public $slug;
    public $status;
    public $created_at;
    public $updated_at;
    public $deleted_at;
    
    // constructor - runs when we create a new Role object
    public function __construct() {
        // empty for now
    }
    
    // set id
    public function setId($id) {
        $this->id = $id;
    }
    
    // get id
    public function getId() {
        return $this->id;
    }
    
    // set name
    public function setName($name) {
        $this->name = $name;
    }
    
    // get name
    public function getName() {
        return $this->name;
    }
    
    // set slug
    public function setSlug($slug) {
        $this->slug = $slug;
    }
    
    // get slug
    public function getSlug() {
        return $this->slug;
    }
    
    // set status
    public function setStatus($status) {
        $this->status = $status;
    }
    
    // get status
    public function getStatus() {
        return $this->status;
    }
    
    // set created_at
    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
    }
    
    // get created_at
    public function getCreatedAt() {
        return $this->created_at;
    }
    
    // set updated_at
    public function setUpdatedAt($updated_at) {
        $this->updated_at = $updated_at;
    }
    
    // get updated_at
    public function getUpdatedAt() {
        return $this->updated_at;
    }
    
    // set deleted_at
    public function setDeletedAt($deleted_at) {
        $this->deleted_at = $deleted_at;
    }
    
    // get deleted_at
    public function getDeletedAt() {
        return $this->deleted_at;
    }
    
    // check if role is active
    public function isActive() {
        return $this->status == 1;
    }
    
    // check if role is deleted (soft delete)
    public function isDeleted() {
        return $this->deleted_at != null;
    }
}
?>