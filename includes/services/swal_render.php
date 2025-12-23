<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['swal'])) {
    $swal = $_SESSION['swal'];
    unset($_SESSION['swal']);

    $icon    = $swal['icon'] ?? $swal['type'] ?? 'info';
    $title   = $swal['title'] ?? '';
    $message = $swal['text'] ?? $swal['message'] ?? '';
?>
<script>
Swal.fire({
    icon: "<?php echo htmlspecialchars($icon); ?>",
    title: "<?php echo htmlspecialchars($title); ?>",
    text: "<?php echo htmlspecialchars($message); ?>",
    confirmButtonColor: "#3085d6"
});
</script>
<?php } ?>
