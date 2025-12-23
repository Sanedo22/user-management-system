<?php

class Repository
{
    public $db;
    private $table;

    public function __construct($db, $table)
    {
        $this->db = $db;
        $this->table = $table;
    }
    // Fetch all records from the table
    public function getAll()
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch a single record by ID
    public function getOne($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch a record by ID (including soft-deleted)
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch only soft-deleted record
    public function getDeletedById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND deleted_at IS NOT NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }




    // Insert a new record into the table
    public function insert($data)
    {
        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->table} ($cols) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return $this->db->lastInsertId();
    }

    // Update an existing record by ID
    public function update($id, $data)
    {
        $setParts = [];
        foreach (array_keys($data) as $col) {
            $setParts[] = "$col = ?";
        }
        $setString = implode(', ', $setParts);
        $sql = "UPDATE {$this->table} SET $setString WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    //delete a record by ID (soft delete)
    public function delete($id)
    {
        $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    //restore a soft-deleted record by ID
    public function restore($id)
    {
        $sql = "UPDATE {$this->table} SET deleted_at = NULL WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    //check if a record exists or not
    public function exists($column, $value, $skipId = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ? AND deleted_at IS NULL";
        $params = [$value];

        if ($skipId !== null) {
            $sql .= " AND id != ?";
            $params[] = $skipId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }
}
