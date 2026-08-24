<?php

/**
 * SAMRIDHI AGRO - Admin Footer Include
 * 
 * This file contains the common footer structure for all admin pages.
 * It includes the closing tags and JavaScript.
 * 
 * @package SamridhiAgro
 * @subpackage Includes
 * @author Samridhi Agro Team
 * @version 1.0.0
 */
?>
</main>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo JS_URL; ?>main.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (menuToggle && sidebar && overlay) {
            function toggleSidebar() {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
            }

            menuToggle.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);

            // Close sidebar on window resize (desktop)
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }

        // Auto-close flash messages after 5 seconds
        document.querySelectorAll('.alert').forEach(function(alert) {
            setTimeout(function() {
                if (alert) {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        if (alert.parentElement) {
                            alert.remove();
                        }
                    }, 500);
                }
            }, 5000);
        });
    });
</script>
</body>

</html>