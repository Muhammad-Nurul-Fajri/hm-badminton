<?php
/**
 * FILE: footer.php
 * FUNGSI: Template footer HTML
 */
?>
    <script>
        setTimeout(() => {
            const flash = document.querySelector('.flash-message');
            if (flash) {
                flash.style.opacity = '0';
                setTimeout(() => flash.remove(), 300);
            }
        }, 3000);
    </script>
</body>
</html>