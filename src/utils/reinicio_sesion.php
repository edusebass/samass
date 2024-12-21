<?php

function protegerPagina($nombre_pagina) {
    $_SESSION['pagina_protegida'] = $nombre_pagina;
}
function reiniciarSesionSiNecesario() {
    $pagina_actual = basename($_SERVER['PHP_SELF']);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['pagina_protegida'] = $pagina_actual;
        return;
    }
    // Siempre actualizamos la página protegida a la actual
    $_SESSION['pagina_protegida'] = $pagina_actual;
}

// Llamar a esta función al inicio de cada página
reiniciarSesionSiNecesario();

?>