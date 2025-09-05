<?php 
require_once 'auth/session_check.php';
?>
<!DOCTYPE html>
<html>

<head>
    <title>Debug Test</title>
</head>

<body>
    <h1>Test Simple</h1>
    <div id="test"></div>
    <script>
        document.getElementById('test').innerHTML = 'JavaScript funciona!';
        console.log('Debug: JavaScript ejecutado correctamente');
    </script>
</body>

</html>