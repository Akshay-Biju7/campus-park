<?php
/**
 * CampusPark - Modern Dark Footer Template
 */
?>
<footer class="mt-auto py-4 no-print" style="background: #0d0e12; border-top: 1px solid rgba(255, 255, 255, 0.06);">
    <div class="container-fluid px-lg-5">
        <div class="row align-items-center gy-3">
            <div class="col-md-6 text-center text-md-start">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-1">
                    <span class="fw-bold text-white"><i class="bi bi-cpu-fill text-warning me-1"></i> CampusPark</span>
                    <span class="text-secondary small">|</span>
                    <span class="text-muted small">Intelligent Campus Mobility & Parking Management</span>
                </div>
                <p class="text-muted small mb-0" style="font-size: 0.78rem;">
                    &copy; <?= date('Y') ?> Campus Security Authority & Academic Administration. Real-time Digital Gate Register.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="d-inline-flex align-items-center gap-3 small text-muted">
                    <span class="badge" style="background: rgba(52, 211, 153, 0.12); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.25);">
                        <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> Gate Nodes Active
                    </span>
                    <a href="setup.php" class="text-decoration-none text-secondary">
                        <i class="bi bi-database-gear me-1"></i> DB Wizard
                    </a>
                    <a href="admin_login.php" class="text-decoration-none text-secondary">
                        <i class="bi bi-shield-lock me-1"></i> Staff Portal
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3.2 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- CampusPark App JS -->
<script src="assets/js/app.js"></script>

</body>
</html>
