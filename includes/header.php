<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<?php require_once '../../includes/swal_render.php'; ?>

<link rel="stylesheet" href="../assets/css/app.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php require_once __DIR__ . '/services/swal_render.php'; ?>

