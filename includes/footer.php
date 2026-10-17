<?php
/**
 * Global Footer
 * Kaldis Coffee PLC
 */
?>
        </main>
        
        <!-- Footer Banner -->
        <footer class="kaldis-footer">
            <div class="container-fluid px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <div>
                    &copy; <?= date('Y') ?> Kaldis Coffee. All rights reserved.
                </div>
                <div>
                    Developed by <strong>IT Department</strong>
                </div>
            </div>
        </footer>
    </div>
</div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js for Dashboards -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- TomSelect 2.4 JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js"></script>
    <!-- Kaldis Custom JS with Cache Busting -->
    <script src="<?= url('/assets/js/app.js') ?>?v=<?= time() ?>"></script>
</body>
</html>
