<?php
require_once dirname(__DIR__, 2) . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Common CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>header-footer.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo IMAGES_PATH; ?>top.png">
    
    <!-- Page specific CSS -->
    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="<?php echo CSS_PATH . $page_css; ?>">
    <?php endif; ?>
    
    <title><?php echo isset($page_title) ? $page_title : 'MVC Application'; ?></title>
</head>
<body>
    <!-- Your header content here -->
</body>
</html> 