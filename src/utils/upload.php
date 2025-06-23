<?php
require './../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$servername = "127.0.0.1";
$username = "root";
$password = "SAM003";
$database = "samass";
$port = 3307;

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Mapeo con nombres reales del Excel
$columnMapping = [
    'Código del elemento' => 'iditems',
    // 'Código del elemento' => 'codigo',
    'Nombre del elemento' => 'nombre',
    'Descripción del elemento' => 'descripcion',
    'Cantidad en existencia' => 'cantidad',
    'Fecha de adquisicion' => 'fecha',
    'Tiempo de uso' => 'uso',
    'Tiempo de vida útil' => 'vida',
    'Costo de mantenimiento mensual' => 'costo',
    'Valor residual' => 'valor_residual',
    'Estado' => 'estado_id',
    'Sección' => 'seccion_id',
    'Área' => 'area_id',
    'Tipo de elemento' => 'elemento_id',
    'Categoría' => 'categoria_id',
    'Observaciones' => 'observaciones',
    'Fabricante' => 'fabricante',
    'Serial' => 'serial',
    'Año fabricación' => 'año_fabricacion',
    'Fuente de poder' => 'id_fuentepoder',
    'Grupo' => 'grupo_id',
    'Fotografías' => 'foto_path',
    'Costo mantenimiento' => 'costo_mantenimiento',
    'Modelo' => 'modelo'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = IOFactory::load($file);
    $worksheet = $spreadsheet->getSheetByName('Lista de inventario');

    if (!$worksheet) {
        die("❌ No se encontró la hoja 'Lista de inventario'. Verifica el nombre exacto.");
    }

    // Get all the rows starting from row 11 (headers) onwards
    $rows = $worksheet->toArray(null, true, true, true); // Get all rows including headers

    // Headers are in row 11, so we use index 10 (0-based index)
    $headers = $rows[11]; // Row 11 is at index 10 in PHP array (0-based)


    $dbColumns = [];
    foreach ($headers as $colLetter => $headerName) {
        $headerName = trim($headerName);  // Remove any extra spaces
        if (isset($columnMapping[$headerName])) {
            $dbColumns[$colLetter] = $columnMapping[$headerName];
        } else {
            //error
        }
    }

    if (empty($dbColumns)) {
        die("No matching columns found. Check the headers and columnMapping.");
    }

    // Prepare SQL query for inserting data into the database
    $sql = "INSERT INTO items_backup (" . implode(", ", $dbColumns) . ") VALUES (" . rtrim(str_repeat("?,", count($dbColumns)), ",") . ")";
    $stmt = $conn->prepare($sql);

    // Loop through all rows starting from row 12 (index 11)
    for ($index = 11; $index < count($rows); $index++) {
        $row = $rows[$index];
        $values = [];

        // Process data for each row
        foreach ($dbColumns as $colLetter => $dbColumn) {
            $value = $row[$colLetter] ?? null;

            // Clean and transform data as needed
            if (in_array($dbColumn, ['costo', 'valor_residual', 'costo_mantenimiento'])) {
                $value = str_replace(['$', ',', ' '], '', $value);
                $value = is_numeric($value) ? (float) $value : null;
            }

            if (in_array($dbColumn, ['cantidad', 'vida', 'grupo_id'])) {
                $value = preg_replace('/[^0-9]/', '', $value);
                $value = $value === '' ? null : (int) $value;
            }

            $values[] = $value;
        }

        // Insert data into the database
        try {
            $stmt->execute($values);
        } catch (PDOException $e) {
            echo "Error inserting row: " . $e->getMessage();
        }
    }

    echo "✅ ¡Datos insertados exitosamente!";
}



?>
        <div class="row justify-content-center mx-auto">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">Subir Inventario Excel</h5>
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="excel_file" class="form-label">Archivo Excel</label>
                                <input type="file" class="form-control" id="excel_file" name="excel_file" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Subir</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="#" onclick="location.reload()"><strong>Refrescar</strong></a>
                </div>
            </div>
        </div>
</body>
</html>
