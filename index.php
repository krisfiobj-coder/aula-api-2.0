<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 1);

define('API_BASE', 'https://darkred-rook-511555.hostingersite.com/api/v1');

$apiKeyActual = $_POST['api_key'] ?? 'lab_7bndMf5pQOXYbJ40op4-ETWCePpXkpnAfHjDc_LZ09Q';
$mensajeAlert = '';
$tipoAlert = '';
$productoConsultado = null;

function ejecutarPeticionAPI($endpoint, $metodo = 'GET', $payload = null, $key = '') {
    $url = API_BASE . $endpoint;
    $ch = curl_init();

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    if (!empty($key)) {
        $headers[] = 'X-API-KEY: ' . trim($key);
        $headers[] = 'Authorization: Bearer ' . trim($key);
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);

    if ($metodo === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
    }

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    return [
        'url'    => $url,
        'status' => $httpCode,
        'error'  => $curlErr,
        'json'   => json_decode($raw, true),
        'raw'    => $raw
    ];
}

// 1. Estado del servicio (GET /health.php)
$resHealth = ejecutarPeticionAPI('/health.php', 'GET');

// 2. Consultar Producto Individual por ID (GET /product.php?id=X)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'consultar_producto') {
    $idBuscado = (int)($_POST['id_buscar'] ?? 12);
    $resConsulta = ejecutarPeticionAPI('/product.php?id=' . $idBuscado, 'GET', null, $apiKeyActual);

    if ($resConsulta['status'] == 200 && is_array($resConsulta['json'])) {
        $productoConsultado = $resConsulta['json']['data'] ?? $resConsulta['json'];
        $mensajeAlert = "¡PRODUCTO ENCONTRADO EN LA API! (HTTP 200)";
        $tipoAlert = "success";
    } else {
        $mensajeAlert = "Respuesta API (HTTP " . $resConsulta['status'] . "): Producto no encontrado.";
        $tipoAlert = "danger";
    }
}

// 3. Crear Pedido (POST /orders.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_pedido') {
    $prodId   = (int)($_POST['producto_id'] ?? 12);
    $cant     = (int)($_POST['cantidad'] ?? 1);
    $cliente  = trim($_POST['customer_name'] ?? 'Kristel Benavides');

    $payloadOrder = [
        'customer_name' => $cliente,
        'items' => [
            [
                'product_id' => $prodId,
                'quantity'   => $cant
            ]
        ]
    ];

    $resOrderPOST = ejecutarPeticionAPI('/orders.php', 'POST', $payloadOrder, $apiKeyActual);

    if ($resOrderPOST['status'] >= 200 && $resOrderPOST['status'] < 300) {
        $mensajeAlert = "¡PEDIDO PROCESADO EXITOSAMENTE EN LA API! (HTTP " . $resOrderPOST['status'] . ")";
        $tipoAlert = "success";
    } else {
        $mensajeAlert = "Respuesta API (HTTP " . $resOrderPOST['status'] . "): " . (isset($resOrderPOST['json']['message']) ? $resOrderPOST['json']['message'] : $resOrderPOST['raw']);
        $tipoAlert = "danger";
    }
}

// 4. Catálogo Real (GET /products.php)
$resProducts = ejecutarPeticionAPI('/products.php', 'GET', null, $apiKeyActual);
$productosObtenidos = [];
if ($resProducts['status'] == 200 && is_array($resProducts['json'])) {
    $productosObtenidos = $resProducts['json']['data'] ?? $resProducts['json'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos Disponibles — Sistema de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #0b0f19; color: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; padding-bottom: 50px; }
        .card-custom { background: #161e2e; border: 1px solid #283548; border-radius: 12px; padding: 22px; box-shadow: 0 4px 20px rgba(0,0,0,0.4); margin-bottom: 25px; }
        .product-card { background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 15px; transition: transform 0.2s, border-color 0.2s; }
        .product-card:hover { transform: translateY(-3px); border-color: #38bdf8; }
        .badge-http { font-family: monospace; font-size: 0.95rem; padding: 8px 14px; border-radius: 6px; }
        .code-box { background: #020617; color: #38bdf8; padding: 14px; border-radius: 8px; border: 1px solid #1e293b; font-family: monospace; font-size: 0.85rem; max-height: 200px; overflow-y: auto; }
        .form-control { background-color: #0f172a; border: 1px solid #334155; color: #f8fafc; }
        .form-control:focus { background-color: #0f172a; color: #fff; border-color: #38bdf8; box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.25); }
    </style>
</head>
<body>

<nav class="navbar navbar-dark border-bottom border-secondary mb-4 py-3" style="background-color: #020617;">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4 text-info" href="#">
            <i class="fa-solid fa-boxes-stacked me-2"></i> Productos Disponibles — Sistema de Inventario
        </a>
        <span class="badge bg-<?= $resHealth['status'] == 200 ? 'success' : 'danger' ?> badge-http">
            <i class="fa-solid fa-server me-1"></i> API Status: HTTP <?= $resHealth['status'] ?>
        </span>
    </div>
</nav>

<div class="container" style="max-width: 1080px;">

    <?php if ($mensajeAlert): ?>
        <div class="alert alert-<?= $tipoAlert ?> alert-dismissible fade show fw-bold p-3 mb-4 border-0 shadow" role="alert">
            <i class="fa-solid <?= $tipoAlert === 'success' ? 'fa-circle-check text-white' : 'fa-triangle-exclamation text-white' ?> me-2 fs-5"></i>
            <?= htmlspecialchars($mensajeAlert) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card-custom">
        <h6 class="fw-bold text-uppercase text-info mb-3"><i class="fa-solid fa-key me-2"></i>Credencial de Seguridad (X-API-KEY)</h6>
        <form method="POST" class="row g-2">
            <div class="col-md-9">
                <input type="text" name="api_key" class="form-control font-monospace" value="<?= htmlspecialchars($apiKeyActual) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-info w-100 fw-bold text-dark"><i class="fa-solid fa-arrows-rotate me-1"></i> Actualizar Key</button>
            </div>
        </form>
    </div>

    <!-- Consulta de Producto Específico (GET /product.php?id=X) -->
    <div class="card-custom">
        <h5 class="fw-bold text-success mb-3"><i class="fa-solid fa-magnifying-glass me-2"></i>Consultar Producto por ID (GET /product.php?id=X)</h5>
        <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="consultar_producto">
            <input type="hidden" name="api_key" value="<?= htmlspecialchars($apiKeyActual) ?>">
            <div class="col-md-9">
                <label class="form-label fw-bold">Ingrese ID del Producto a Buscar</label>
                <input type="number" name="id_buscar" class="form-control font-monospace" value="12" placeholder="Ej. 12" required>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100 fw-bold">
                    <i class="fa-solid fa-search me-2"></i> Consultar API
                </button>
            </div>
        </form>

        <?php if ($productoConsultado): ?>
            <div class="mt-3 p-3 border border-secondary rounded bg-dark">
                <h6 class="fw-bold text-info mb-1">Detalle del Producto Consultado:</h6>
                <p class="m-0"><strong>ID:</strong> #<?= htmlspecialchars($productoConsultado['id'] ?? 12) ?> | <strong>Nombre:</strong> <?= htmlspecialchars($productoConsultado['name'] ?? 'Adaptador USB-C') ?> | <strong>Precio:</strong> ₡<?= number_format((float)($productoConsultado['price'] ?? 18500), 0, ',', '.') ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Productos Disponibles -->
        <div class="col-md-7">
            <div class="card-custom h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0 text-info"><i class="fa-solid fa-store me-2"></i>Productos Disponibles</h5>
                    <span class="badge bg-secondary font-monospace"><?= count($productosObtenidos) ?> Ítems</span>
                </div>
                
                <div class="row g-3" style="max-height: 400px; overflow-y: auto; padding-right: 5px;">
                    <?php if (empty($productosObtenidos)): ?>
                        <div class="col-12 text-center text-muted py-4">
                            No se pudieron obtener los productos de la API.
                        </div>
                    <?php else: ?>
                        <?php foreach ($productosObtenidos as $p): 
                            $id     = $p['id'] ?? $p['product_id'] ?? 1;
                            $nombre = $p['name'] ?? $p['nombre'] ?? 'Producto';
                            $precio = $p['price'] ?? $p['precio'] ?? 0;
                            $stock  = $p['stock'] ?? 10;
                            $desc   = $p['description'] ?? 'Producto registrado';
                        ?>
                            <div class="col-md-6">
                                <div class="product-card h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <span class="badge bg-dark text-info border border-secondary font-monospace">#<?= htmlspecialchars($id) ?></span>
                                            <span class="badge bg-outline-success text-success border border-success">Stock: <?= htmlspecialchars($stock) ?></span>
                                        </div>
                                        <h6 class="fw-bold mb-1 text-white"><?= htmlspecialchars($nombre) ?></h6>
                                        <small class="text-muted d-block mb-2" style="font-size: 0.78rem;"><?= htmlspecialchars($desc) ?></small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-secondary">
                                        <span class="fs-6 fw-bold text-success">₡<?= number_format((float)$precio, 0, ',', '.') ?></span>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="seleccionar('<?= $id ?>')">
                                            <i class="fa-solid fa-check me-1"></i>Elegir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Generar Pedido -->
        <div class="col-md-5">
            <div class="card-custom h-100">
                <h5 class="fw-bold text-warning mb-3"><i class="fa-solid fa-cart-arrow-down me-2"></i>Crear Pedido (POST /orders.php)</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="crear_pedido">
                    <input type="hidden" name="api_key" value="<?= htmlspecialchars($apiKeyActual) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Cliente (customer_name)</label>
                        <input type="text" name="customer_name" class="form-control" value="Kristel Benavides" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">ID del Producto (product_id)</label>
                        <input type="number" id="input_producto_id" name="producto_id" class="form-control font-monospace" value="12" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Cantidad (quantity)</label>
                        <input type="number" name="cantidad" class="form-control" value="1" min="1" required>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2 text-dark">
                        <i class="fa-solid fa-paper-plane me-2"></i> Ejecutar POST /orders.php
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- PANELES DE DIAGNÓSTICO EN VIVO -->
    <div class="card-custom mt-4">
        <h6 class="fw-bold text-uppercase text-slate-400 mb-3"><i class="fa-solid fa-terminal me-2 text-success"></i>Consola de Diagnóstico cURL (Raw Response)</h6>
        <div class="row">
            <div class="col-md-6 mb-2">
                <small class="fw-bold d-block text-muted mb-1">GET /products.php (HTTP <?= $resProducts['status'] ?>)</small>
                <div class="code-box"><?= htmlspecialchars($resProducts['raw']) ?></div>
            </div>
            <div class="col-md-6 mb-2">
                <small class="fw-bold d-block text-muted mb-1">GET /health.php (HTTP <?= $resHealth['status'] ?>)</small>
                <div class="code-box"><?= htmlspecialchars($resHealth['raw']) ?></div>
            </div>
        </div>
    </div>

</div>

<script>
    function seleccionar(id) {
        document.getElementById('input_producto_id').value = id;
    }
</script>
</body>
</html>