</main>
        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Copyright &copy; Main Wahyu <?php echo date("Y"); ?></div>
                </div>
            </div>
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="<?php echo BASE_URL; ?>js/scripts.js"></script>
<?php 
// Tempat untuk script JS tambahan per halaman
if (isset($additional_scripts)) {
    echo $additional_scripts;
} 
?>
</body>
</html>