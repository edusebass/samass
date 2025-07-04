/**
 * Escáner QR - Funcionalidad de escaneo de códigos QR
 * 
 * Utilidad para manejar el escaneo de códigos QR
 * en el sistema de bodega.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

<?php
// escaner-qr.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!isset($input['qrData'])) {
            throw new Exception('Datos del QR no proporcionados');
        }
        
        // Devolvemos el resultado como JSON
        echo json_encode([
            'success' => true,
            'message' => 'QR leído correctamente',
            'data' => $input['qrData']
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Si no es POST, devolvemos el HTML del escáner
?>
<div id="qr-reader-container">
    <div id="reader"></div>
    <div id="result"></div>
    <div id="error"></div>
</div>

<style>
    /* #qr-reader-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    } */
    /* #reader {
        width: 400px;
        margin: 0 auto;
    } */
    #result, #error {
        margin: 20px;
        padding: 10px;
        border-radius: 4px;
        text-align: center;
        display: none;
    }
    #result {
        background-color: #dff0d8;
        border: 1px solid #d6e9c6;
    }
    #error {
        background-color: #f2dede;
        border: 1px solid #ebccd1;
        color: #a94442;
    }
</style>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    const html5QrCode = new Html5Qrcode("reader");
    const resultDiv = document.getElementById('result');
    const errorDiv = document.getElementById('error');
    let isScanning = false;
    let qrCallback = null;

    function showError(message) {
        errorDiv.style.display = 'block';
        errorDiv.textContent = message;
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 3000);
    }

    function showResult(message) {
        resultDiv.style.display = 'block';
        resultDiv.textContent = message;
        setTimeout(() => {
            resultDiv.style.display = 'none';
        }, 3000);
    }

    function onScanSuccess(decodedText) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ qrData: decodedText })
        })
        .then(response => response.json())
        .then(data => {
            showResult(`QR escaneado correctamente`);
            if (typeof qrCallback === 'function') {
                qrCallback(decodedText);
            }
            stopScanning();
        })
        .catch(error => {
            showError('Error al procesar el código QR');
            console.error('Error:', error);
        });
    }

    function startScanning() {
        const config = {
            fps: 10,
            qrbox: { width: 400, height: 400 },
            aspectRatio: 1.0
        };

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            (errorMessage) => {}
        )
        .then(() => {
            isScanning = true;
        })
        .catch((err) => {
            showError('Error al iniciar la cámara');
            console.error('Error al iniciar la cámara:', err);
        });
    }

    function stopScanning() {
        if (isScanning) {
            html5QrCode.stop()
            .then(() => {
                isScanning = false;
            })
            .catch((err) => {
                console.error('Error al detener el escáner:', err);
            });
        }
    }

    // Iniciar el escaneo automáticamente al cargar la página
    document.addEventListener('DOMContentLoaded', startScanning);

    // Función para establecer el callback
    window.setQRCallback = function(callback) {
        qrCallback = callback;
    };
</script>
