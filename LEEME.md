FICHA ITEM VERSIÓN PRUEBA

<?php
function renderRow($label, $value) {
    return "
        <div class='row mb-2'>
            <label class='col-sm-4 fw-bold'>$label</label>
            <div class='col-sm-8'>
                <span>". htmlspecialchars($value) ."</span>
            </div>
        </div>
    ";
}
?>

<div class="container-fluid mt-3">
    <section class="row">
        <div class="col-12">
            <div class="w-100 bg-plomo mb-2 p-1"><strong>INFORMACIONES</strong></div>
            <div class="card rounded-4 px-3 mb-3">
                <div class="row row-cols-sm-2 p-1">
                    <div class="col">
                        <?php
                        echo renderRow('CODIGO:', $item['codigo']);
                        echo renderRow('NOMBRE:', $item['nombre']);
                        echo renderRow('DESCRIPCIÓN:', $item['descripcion']);
                        ?>
                    </div>
                    <div class="col">
                        <?php
                        echo renderRow('TIPO ELEMENTO:', $elemento['tipo']);
                        echo renderRow('ESTADO:', $estado['descripcion']);
                        echo renderRow('CANTIDAD:', $item['cantidad']);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="row">
        <div class="col-12">
            <div class="w-100 bg-plomo mb-2 p-1"><strong>DETALLES</strong></div>
            <div class="card rounded-4 px-3 mb-3">
                <div class="row row-cols-sm-2 row-cols-md-4 p-1">
                    <div class="col">
                        <?php
                        echo renderRow('COSTO UNITARIO:', $item['costo']);
                        echo renderRow('VALOR RESIDUAL:', $item['valor_residual']);
                        echo renderRow('COSTO MANTENIMIENTO:', $item['costo_mantenimiento']);
                        ?>
                    </div>
                    <div class="col">
                        <?php
                        echo renderRow('FECHA ADQUISICIÓN:', $item['fecha']);
                        echo renderRow('TIEMPO UTILIZACIÓN:', $item['uso']);
                        echo renderRow('TIEMPO VIDA ÚTIL:', $item['vida']);
                        ?>
                    </div>
                    <div class="col">
                        <?php
                        echo renderRow('FABRICANTE:', $item['fabricante']);
                        echo renderRow('S/N:', $item['serial']);
                        echo renderRow('MODELO:', $item['modelo']);
                        ?>
                    </div>
                    <div class="col">
                        <?php
                        echo renderRow('AÑO FABRICACIÓN:', $item['año_fabricacion']);
                        echo renderRow('FUENTE PODER:', $fuentePoder['descripcion']);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="w-100 bg-plomo mb-2 p-1"><strong>MANTENIMIENTO</strong></div>
    <section class="row">
   
        <div class="col-4 col-md-5">
            <div class="card rounded-4 px-3 mb-3"> 
                <div class="row">
                    <div class="col-12">
                         <label for="" class="form-label">VIGENTE SI</label>
                        <?php
                        foreach ($mantenimientos as $mantenimiento) {
                            $descripcion_codigo_man = obtener_descripcion_codigo_man($conn, $mantenimiento      ['id_codigo_man']);
                            $progreso = calcular_progreso_mantenimiento($mantenimiento['fecha_creacion'],       $descripcion_codigo_man);

                            // Mostrar información del mantenimiento
                            echo "<p>Notas: " . htmlspecialchars($mantenimiento['notas']) . "</p>";
                            echo "<p>Descripción del Código de Mantenimiento: " . htmlspecialchars      ($descripcion_codigo_man) . "</p>";

                            // Barra de progreso
                            echo "<div class='progress'>
                                    <div class='progress-bar' role='progressbar' style='width: " . htmlspecialchars     ($progreso) . "%;    ' aria-valuenow='" . htmlspecialchars($progreso) . "'      aria-valuemin='0' aria-valuemax='100'></div>
                                </div>";

                            // Alertas según progreso
                            if ($progreso >= 100) {
                                echo "<script>alert('El mantenimiento debe realizarse ya.');</script>";
                            } elseif ($progreso >= 80) {
                                echo "<script>alert('El mantenimiento está próximo a vencer.');</script>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-5">
            <div class="card rounded-4 px-3 mb-3">
       
                <div class="row">
                    <div class="col-4">
                        <label for="">MANUALES</label>
                        <img src="/public/ico/manual.png" alt="Manual" style="width: 70px;">
                    </div>
                   
                    <div class="col-8">
                        <?php
                        if (!empty($manuales)) {
                            foreach ($manuales as $manual) {
                                echo "<p>Título: " . htmlspecialchars($manual['titulo']) . "</p>";
                                echo "<p>Enlace: <a href='./../../" . htmlspecialchars($manual['enlace']) . "'  download>" .     htmlspecialchars($manual['enlace']) . "</a></p>";
                            }
                        }
                        ?>
                      </div>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-2">
            <div class="card rounded-4 px-3 mb-3">
       
                <div class="row">
                    <div class="col-12">
                    <span id="codigoQR"">CÓDIGO QR</span>
                    <?php if (!empty($item['qr_image_path'])) { ?>
                        <img width="250" src="./../../<?php echo htmlspecialchars($item['qr_image_path']); ?>"  alt="Código QR">
                    <?php } else { ?>
                        <p>No hay código QR disponible.</p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
</div>
