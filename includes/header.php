<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<link rel="stylesheet" href="../assets/css/app.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php require_once __DIR__ . '/services/swal_render.php'; ?>
