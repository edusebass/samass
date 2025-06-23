<!-- <section class="row">
                    <div class="col-12">
                        <div class="w-100 bg-plomo mb-2 p-1"><b>INFORMACIÓN</b></div>
                        <div class="card rounded-4 px-1 mb-3">
                        <div class="row m-1">
                            <div class="col-12 col-sm-6 d-flex flex-column position-relative">
                                <div class="row py-2">
                                    <div class="col-6 d-flex align-items-center">
                                            <label for="codigo" class="form-label">Código:</label>
                                    </div>
                                    <div class="col-6 d-flex align-items-end">
                                            <input type="text" class="form-control" id="codigo" name="codigo" required>
                                    </div>
                                </div>
                                <div class="row py-2">
                                    <div class="col-6 d-flex align-items-center">
                                            <label for="nombre" class="form-label">Nombre:</label>
                                    </div>
                                    <div class="col-6 d-flex align-items-end">
                                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                                    </div>
                                </div>
                                <div class="row py-2">
                                    <div class="col-6 d-flex align-items-center">
                                            <label for="descripcion" class="form-label">Descripción:</label>
                                    </div>
                                    <div class="col-6 d-flex align-items-end">
                                            <textarea id="descripcion" class="form-control" name="descripcion"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 d-flex flex-column position-relative"> 
                                <div class="row py-2">
                                    <div class="col-6 d-flex align-items-center">
                                            <label for="elemento_id" class="form-label">Tipo de elemento:</label>
                                    </div>
                                    <div class="col-6 d-flex align-items-end">
                                        <select class="form-select" aria-label="Default select example" id="elemento_id" name="elemento_id">
                                            <option value="default" selected>Elegir una opción</option>
                                            <?php foreach ($resultado_elementos as $fila): ?>
                                                <option value="<?php echo $fila['idelementos']; ?>">
                                                    <?php echo $fila['tipo']; ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row py-2">
                                    <div class="col-6 d-flex align-items-center"> 
                                        <label for="herramienta_foto" class="form-label">Foto de la Herramienta:</label>
                                    </div>
                                    <div class="col-6 d-flex align-items-end">
                                        <input type="file" class="form-control border-2 rounded-3" id="herramienta_foto" name="herramienta_foto" accept="image/*">
                                    </div>
                                </div>
                                <div class="row py-2">
                      <div class="col-6 d-flex align-items-center">
                                        <label for="estado_id" class="form-label">Estado:</label>
                                    </div>
                                    <div class="col-6 d-flex align-items-end">
                                        <select class="form-select" aria-label="Elegir estado" id="estado_id" name="estado_id">
                                            <option value="default" selected>Elegir una opcion</option>
                                            <?php foreach ($resultado_estados as $fila): ?>
                                                <option value="<?php echo $fila['idestado']; ?>">
                                                    <?php echo $fila['descripcion']; ?>
                                                </option>
                                            <?php endforeach ?>
                                         </select>
                                    </div>
                                </div>
                                <div class="row row-cols-2 py-2">
                                    <div class="col-6">
                                        <div class="row">
                    <div class="col-6 d-flex align-items-center">
                                            <label for="cantidad" class="form-label">Cantidad:</label>
                                        </div>
                                        <div class="col-6 d-flex align-items-end">
                                            <input type="number" class="form-control border-2 rounded-3" id="cantidad" name="cantidad">
                                        </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="row">
                                        <div class="col-12  d-flex align-items-center">
                                            <label for="minimal" class="form-label">Minimal:</label>
                                        </div>
                                        <div class="col-12 d-flex align-items-end">
                                            <input type="number" class="form-control border-2 rounded-3" id="cantidad" name="minimal">
                                        </div>
                                        </div>
                                    </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </section> -->