<?php
/**
 * AdHub - Footer Include
 * Renders the footer bar and loads JS. Call this INSIDE .adhub-content-wrapper,
 * after closing </main>.
 */
?>

    <!-- ── Footer bar ─────────────────────────────────────────────────────── -->
    <footer class="adhub-footer">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span>© <?= date('Y') ?> <strong>AdHub</strong> — Agency Campaign Manager</span>
            <span class="text-muted small">v1.0.0</span>
        </div>
    </footer>

    <!-- Bootstrap 5 JS CDN (bundle includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="/adhub/assets/js/script.js"></script>

</body>
</html>
