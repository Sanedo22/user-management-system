<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['swal'])) {
    $swal = $_SESSION['swal'];
    unset($_SESSION['swal']);
?>
<script>
Swal.fire({
    icon: "<?php echo $swal['type']; ?>",
    title: "<?php echo $swal['title']; ?>",
    text: "<?php echo $swal['message']; ?>",
    confirmButtonColor: "#3085d6"
});
</script>
<?php } ?>
