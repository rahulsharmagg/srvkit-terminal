<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PHP Terminal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
  <?php include __DIR__ . '/../assets/terminal.css'; ?>
</style>
</head>
<body>

<?php if (!$authenticated): ?>
  <?php include __DIR__ . '/login.php'; ?>
<?php else: ?>
  <?php include __DIR__ . '/terminal.php'; ?>
  <script>
    const SERVER_USER = <?php echo json_encode($whoami); ?>;
    const SERVER_HOST = <?php echo json_encode(explode('.', $hostname)[0]); ?>;
    let current_cwd  = <?php echo json_encode($cwd); ?>;
    
    <?php include __DIR__ . '/../assets/terminal.js'; ?>
  </script>
<?php endif; ?>

</body>
</html>
