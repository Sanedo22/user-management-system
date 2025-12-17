<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<?php require_once 'swal_render.php'; ?>
