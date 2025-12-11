</div> <!-- end container -->
    
    <!-- Footer -->
    <footer class="mt-5 py-3 bg-light text-center">
        <p class="mb-0">&copy; User Management System.</p>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Display flash messages if any -->
    <?php
    if(function_exists('displayFlashMessage')) {
        displayFlashMessage();
    }
    ?>
    
</body>
</html>