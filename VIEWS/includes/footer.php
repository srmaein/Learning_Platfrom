    <!-- Common JavaScript -->
    <script src="<?php echo JS_PATH; ?>main.js"></script>
    
    <!-- Page specific JavaScript -->
    <?php if (isset($page_js)): ?>
        <script src="<?php echo JS_PATH . $page_js; ?>"></script>
    <?php endif; ?>
</body>
</html> 