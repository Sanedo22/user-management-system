<?php
require_once __DIR__ . '/../repo/repository.php';

class TaskService
{
    private $repo;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->repo = new Repository($db, 'tasks');
    }

    //fetch all tasks
    public function getAllTasks()
    {
        $sql = "
        SELECT 
            t.*,
            assigner.email AS assigned_by_email,
            assignee.email AS assigned_to_email
        FROM tasks t
        JOIN users assigner ON assigner.id = t.assigned_by
        JOIN users assignee ON assignee.id = t.assigned_to 
        WHERE t.deleted_at IS NULL
        ORDER BY t.created_at DESC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTaskById($taskId)
    {
        $sql = "SELECT * FROM tasks WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$taskId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTodayTasksByStartDate()
    {
        $sql = "
        SELECT 
            t.*,
            u1.email AS assigned_to_email,
            u2.email AS assigned_by_email
        FROM tasks t
        JOIN users u1 ON u1.id = t.assigned_to
        JOIN users u2 ON u2.id = t.assigned_by
        WHERE DATE(t.start_date) = CURDATE()
          AND t.deleted_at IS NULL
        ORDER BY t.start_date ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    //create task
    public function createTask($title, $description, $assignedTo, $assignedBy, $startDate, $endDate)
    {
        if (empty($title)) {
            return ['success' => false, 'errors' => ['Task title is required']];
        }

        // fetch actor (who assigns)
        $stmt = $this->db->prepare(
            "SELECT u.id, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = ?"
        );
        $stmt->execute([$assignedBy]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);

        // fetch target (who receives)
        $stmt = $this->db->prepare(
            "SELECT u.id, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = ?"
        );
        $stmt->execute([$assignedTo]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$actor || !$target) {
            return [
                'success' => false,
                'errors' => ['Invalid user selection']
            ];
        }

        if (empty($startDate) || empty($endDate)) {
            return [
                'success' => false,
                'errors' => ['Start date and End date are required']
            ];
        }

        if ($startDate > $endDate) {
            return [
                'success' => false,
                'errors' => ['End date cannot be before start date']
            ];
        }


        //RBAC check
        if (!$this->canAssignTask($actor, $target)) {
            return [
                'success' => false,
                'errors' => ['You are not allowed to assign tasks to this role']
            ];
        }

        $data = [
            'title'       => $title,
            'description' => $description,
            'assigned_to' => $assignedTo,
            'assigned_by' => $assignedBy,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'status'      => 'Pending'
        ];

        $id = $this->repo->insert($data);

        return $id
            ? ['success' => true, 'message' => 'Task assigned successfully']
            : ['success' => false, 'errors' => ['Failed to assign task']];
    }

    public function updateTask($taskId, $title, $description, $startDate, $endDate)
    {
        if (empty($title)) {
            return ['success' => false, 'errors' => ['Task title is required']];
        }

        if ($startDate > $endDate) {
            return ['success' => false, 'errors' => ['End date cannot be before start date']];
        }

        $updated = $this->repo->update($taskId, [
            'title'       => $title,
            'description' => $description,
            'start_date'  => $startDate,
            'end_date'    => $endDate
        ]);

        return $updated
            ? ['success' => true, 'message' => 'Task updated successfully']
            : ['success' => false, 'errors' => ['Update failed']];
    }

    public function softDeleteTask($taskId)
    {
        return $this->repo->update($taskId, [
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getDeletedTasks()
    {
        $sql = "
        SELECT t.*,
               u1.email AS assigned_by_email,
               u2.email AS assigned_to_email
        FROM tasks t
        JOIN users u1 ON u1.id = t.assigned_by
        JOIN users u2 ON u2.id = t.assigned_to
        WHERE t.deleted_at IS NOT NULL
        ORDER BY t.deleted_at DESC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function restoreTask($taskId)
    {
        return $this->repo->update($taskId, [
            'deleted_at' => null
        ]);
    }



    //fetch user tasks
    public function getTasksForUser($userId)
    {
        $sql = "SELECT t.*, 
                       u1.email AS assigned_by_email,
                       u2.email AS assigned_to_email
                FROM tasks t
                JOIN users u1 ON u1.id = t.assigned_by
                JOIN users u2 ON u2.id = t.assigned_to
                WHERE t.assigned_to = ?
                AND t.deleted_at IS NULL
                ORDER BY t.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //get task assigned by admins
    public function getTasksAssignedBy($adminId)
    {
        $sql = "SELECT t.*, 
                       u1.email AS assigned_by_email,
                       u2.email AS assigned_to_email
                FROM tasks t
                JOIN users u1 ON u1.id = t.assigned_by
                JOIN users u2 ON u2.id = t.assigned_to
                WHERE t.assigned_by = ?
                AND t.deleted_at IS NULL
                ORDER BY t.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$adminId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //update task status 
    public function updateTaskStatus($taskId, $status, $userId)
    {
        $allowed = ['Pending', 'In Progress', 'Completed'];

        if (!in_array($status, $allowed)) {
            return ['success' => false, 'errors' => ['Invalid Status']];
        }

        // Fetch task
        $task = $this->getTaskById($taskId);

        if (!$task) {
            return ['success' => false, 'errors' => ['Task not found']];
        }

        // Ownership check (VERY IMPORTANT)
        if ((int)$task['assigned_to'] !== (int)$userId) {
            return ['success' => false, 'errors' => ['Unauthorized']];
        }

        $updated = $this->repo->update($taskId, [
            'status' => $status
        ]);

        return $updated
            ? ['success' => true, 'message' => 'Task updated']
            : ['success' => false, 'errors' => ['Update failed']];
    }


    public function canAssignTask($assignedBy, $assignedTo)
    {
        // cannot assign to self
        if ($assignedBy['id'] == $assignedTo['id']) {
            return false;
        }

        // Manager → User only
        if (
            $assignedBy['role_name'] === 'Manager' &&
            in_array($assignedTo['role_name'], ['Super Admin', 'Admin', 'Manager'])
        ) {
            return false;
        }

        // Super Admin → anyone except Super Admin
        if (
            $assignedBy['role_name'] === 'Super Admin' &&
            $assignedTo['role_name'] === 'Super Admin'
        ) {
            return false;
        }

        return true;
    }

    public function addComment($taskId, $userId, $comment)
    {
        if (trim($comment) === '') {
            return ['success' => false, 'error' => 'Comment cannot be empty'];
        }

        // Security: ensure task belongs to user (either assigned to OR assigned by them)
        $task = $this->getTaskById($taskId);
        if (!$task || ((int)$task['assigned_to'] !== (int)$userId && (int)$task['assigned_by'] !== (int)$userId)) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        $sql = "INSERT INTO task_comments (task_id, user_id, comment)
            VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$taskId, $userId, $comment]);

        return ['success' => true];
    }

    public function getCommentsForTask($taskId)
    {
        $sql = "
        SELECT c.comment, c.created_at, u.email
        FROM task_comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.task_id = ?
        ORDER BY c.created_at DESC
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
