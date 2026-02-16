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

    // fetch all ACTIVE records
    public function getAll()
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE deleted_at IS NULL 
                ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // fetch ONE ACTIVE record by id
    public function getOne($id)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE id = ? AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // fetch record by id (ACTIVE + DELETED)
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // fetch ONLY SOFT-DELETED record
    public function getDeletedById($id)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE id = ? AND deleted_at IS NOT NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $setParts = [];

        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
        }

        $sql = "UPDATE {$this->table} 
                SET " . implode(', ', $setParts) . " 
                WHERE id = ?";

        $values = array_values($data);
        $values[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    // soft delete record
    public function delete($id)
    {
        $sql = "UPDATE {$this->table} 
                SET deleted_at = NOW() 
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    // restore soft deleted record
    public function restore($id)
    {
        $sql = "UPDATE {$this->table} 
                SET deleted_at = NULL, status = 1
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    // check if active record exists
    public function exists($column, $value, $skipId = null)
    {
        $sql = "SELECT id FROM {$this->table} 
                WHERE {$column} = ? 
                AND deleted_at IS NULL";

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
