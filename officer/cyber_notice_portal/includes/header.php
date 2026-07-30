<script>
    // Prevent browser back button cache (bfcache)
    window.addEventListener('pageshow', function (event) {
        if (event.persisted || (typeof window.performance !== 'undefined' && (window.performance.navigation.type === 2 || (window.performance.getEntriesByType && window.performance.getEntriesByType("navigation")[0] && window.performance.getEntriesByType("navigation")[0].type === "back_forward")))) {
            window.location.reload();
        }
    });
</script>
<header class="header">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 600;"><?php echo $page_title ?? 'Dashboard'; ?></h1>
        <p style="color: var(--text-dim); font-size: 0.9rem;"><?php echo date('l, d F Y'); ?></p>
    </div>
    <div class="user-profile">
        <div style="text-align: right;">
            <p style="font-size: 0.9rem; font-weight: 600;"><?php echo $_SESSION['full_name']; ?></p>
            <p style="font-size: 0.75rem; color: var(--text-dim);"><?php echo $_SESSION['rank']; ?></p>
        </div>
        <div style="width: 40px; height: 40px; background: var(--police-blue); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
            <?php echo substr($_SESSION['full_name'], 0, 1); ?>
        </div>
    </div>
</header>
