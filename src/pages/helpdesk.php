<?php
require './../layout/head.html';
require './../utils/session_check.php';
?>
    <title>SAM Assistant</title>
    <style>     
    </style>
    </head>
    <body>
    <div class="container">
    <div class="content">

    <?php require './../layout/header.php';?>

    <div class="bg">
        <div class="tx">
            <h1>HELPDESK</h1>
            <br>
            <ul>
                <li><h4><a href="./../pages/usuarios.php">Contacta por Mail</a></h4></li>
                <li><h4><a href="./../pages/items.php">Contacta por mensaje</a></h4></li>
            </ul>
    </div>
    </div>
    <?php
require './../layout/footer.htm';
    ?>   
</body>
</html>