<?php
require './../layout/header.php';
require './../utils/session_check.php';

?>
    <title>SAM assistant</title>

    </head>
    <body>
    
    <?php require './../layout/header.php'; ?>
<br>
    
        <div style="background-color: #e8ecf2; height:10;"><b>
            SISTEMA MENSAJISTICA INTERNA</b></div>
            <br>

            <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: "Lato", sans-serif;
        }
        .sidebar {
            width: 20%;
            height: 30%;
            background-color: #e8ecf2;
            color: black;
            float: left;
            padding: 10px;
        }
        .main-content {
            width: 80%;
            height: 100%;
            float: left;
            padding: 10px;
        }
        .email-list, .email-view {
            border: 1px solid #FF7514;
            padding: 10px;
            margin-bottom: 10px;
        }
        .email-item {
            border-bottom: 1px solid #e8ecf2;
            padding: 5px;
            cursor: pointer;
        }
        .email-item-new {
            border-bottom: 1px solid #e8ecf2;
            padding: 5px;
            cursor: pointer;
            font-style: italic;
        }
        .email-item:hover {
            background-color: #e8ecf2;
        }
        .email-item-new:hover {
            background-color: #e8ecf2;
        }
        .email-header {
            background-color: #e8ecf2;
            padding: 10px;
        }
        .email-body {
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Carpetas</h2>
        <ul>
            <li>Bandeja</li>
            <li>Enviadas</li>
            <li>Guardadas</li>
            <li>Basura</li>
        </ul>
    <br>    

    </div>
    <div class="main-content">
        <div class="email-list">
            <h2>Bandeja</h2>
            <div class="email-item">
                <strong>SU Guido Grillo</strong> - Puntos de recordar
            </div>
            <div class="email-item-new">
                <strong>ADM Vivian Castro</strong> - Lista de compras para la semana
            </div>
        </div>
        <div class="email-view">
            <div class="email-header">
                <h2>Puntos de recordar</h2>
                <p >DE: <strong>SU Guido Grillo</strong></p>
                <p >A: <strong>ADM Jason Castro</strong></p>
            </div>
            <div class="email-body">
                <p>Hola Jason, te envio este mensaje para recordarte que tenemos la reunion con los chicos el miercoles a las 9.30AM.</p>
                <p>Saludos,<br>Guido</p>
            </div>
        </div>
    </div>


<?php require './../layout/footer.htm'; ?>   

</div></div> 
</body>
</html>