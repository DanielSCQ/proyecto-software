<?php
// acciones_carrito.php - Procesador del servidor para el carrito de compras AGRANDA (Fase 4)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/config/conexion.php');
require_once(__DIR__ . '/includes/helpers.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$metodo = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Si viene un cuerpo JSON (fetch)
$inputJSON = json_decode(file_get_contents('php://input'), true);
if (is_array($inputJSON)) {
    $action = $inputJSON['action'] ?? $action;
}

$codigoCupon = $_SESSION['cupon'] ?? null;

switch ($action) {
    case 'sincronizar':
        // Sincroniza los productos enviados desde el cliente (LocalStorage) validándolos rigurosamente en el servidor
        $itemsCliente = $inputJSON['items'] ?? $_POST['items'] ?? [];
        if (is_string($itemsCliente)) {
            $itemsCliente = json_decode($itemsCliente, true) ?: [];
        }

        $nuevoCarrito = [];
        foreach ($itemsCliente as $item) {
            $id = (int)($item['id'] ?? $item['id_producto'] ?? 0);
            $cant = (int)($item['cantidad'] ?? 1);
            if ($id > 0 && $cant > 0) {
                $nuevoCarrito[$id] = [
                    'id' => $id,
                    'cantidad' => $cant
                ];
            }
        }
        $_SESSION['carrito'] = array_values($nuevoCarrito);
        
        $calculo = calcularCarritoServidor($conexion, $_SESSION['carrito'], $codigoCupon);
        echo json_encode([
            'ok' => true,
            'carrito' => $calculo
        ]);
        exit;

    case 'obtener':
        $calculo = calcularCarritoServidor($conexion, $_SESSION['carrito'], $codigoCupon);
        echo json_encode([
            'ok' => true,
            'carrito' => $calculo
        ]);
        exit;

    case 'agregar':
        $idProducto = (int)($inputJSON['id_producto'] ?? $_POST['id_producto'] ?? 0);
        $cantidad = (int)($inputJSON['cantidad'] ?? $_POST['cantidad'] ?? 1);

        if ($idProducto <= 0 || $cantidad <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Datos de producto inválidos']);
            exit;
        }

        // Consultar stock real en el servidor
        $prod = obtenerProductoPorId($conexion, $idProducto);
        if (!$prod || (int)$prod['stock_actual'] <= 0) {
            echo json_encode(['ok' => false, 'error' => 'El producto no está disponible o se encuentra agotado']);
            exit;
        }

        $stockMax = (int)$prod['stock_actual'];
        $encontrado = false;

        foreach ($_SESSION['carrito'] as &$item) {
            if ((int)$item['id'] === $idProducto) {
                $item['cantidad'] = min($item['cantidad'] + $cantidad, $stockMax);
                $encontrado = true;
                break;
            }
        }
        unset($item);

        if (!$encontrado) {
            $_SESSION['carrito'][] = [
                'id' => $idProducto,
                'cantidad' => min($cantidad, $stockMax)
            ];
        }

        $calculo = calcularCarritoServidor($conexion, $_SESSION['carrito'], $codigoCupon);
        echo json_encode([
            'ok' => true,
            'mensaje' => 'Producto agregado al carrito',
            'carrito' => $calculo
        ]);
        exit;

    case 'actualizar':
        $idProducto = (int)($inputJSON['id_producto'] ?? $_POST['id_producto'] ?? 0);
        $cantidad = (int)($inputJSON['cantidad'] ?? $_POST['cantidad'] ?? 1);

        if ($idProducto <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID de producto inválido']);
            exit;
        }

        if ($cantidad <= 0) {
            // Eliminar producto si la cantidad es 0 o menor
            $_SESSION['carrito'] = array_values(array_filter($_SESSION['carrito'], function($item) use ($idProducto) {
                return (int)$item['id'] !== $idProducto;
            }));
        } else {
            $prod = obtenerProductoPorId($conexion, $idProducto);
            $stockMax = $prod ? (int)$prod['stock_actual'] : 0;
            
            foreach ($_SESSION['carrito'] as &$item) {
                if ((int)$item['id'] === $idProducto) {
                    $item['cantidad'] = min($cantidad, $stockMax);
                    break;
                }
            }
            unset($item);
        }

        $calculo = calcularCarritoServidor($conexion, $_SESSION['carrito'], $codigoCupon);
        echo json_encode([
            'ok' => true,
            'carrito' => $calculo
        ]);
        exit;

    case 'eliminar':
        $idProducto = (int)($inputJSON['id_producto'] ?? $_POST['id_producto'] ?? 0);

        $_SESSION['carrito'] = array_values(array_filter($_SESSION['carrito'], function($item) use ($idProducto) {
            return (int)$item['id'] !== $idProducto;
        }));

        $calculo = calcularCarritoServidor($conexion, $_SESSION['carrito'], $codigoCupon);
        echo json_encode([
            'ok' => true,
            'mensaje' => 'Producto eliminado',
            'carrito' => $calculo
        ]);
        exit;

    case 'vaciar':
        $_SESSION['carrito'] = [];
        unset($_SESSION['cupon']);
        echo json_encode([
            'ok' => true,
            'mensaje' => 'Carrito vaciado exitosamente',
            'carrito' => calcularCarritoServidor($conexion, [], null)
        ]);
        exit;

    case 'aplicar_cupon':
        $codigo = trim($inputJSON['codigo'] ?? $_POST['codigo'] ?? '');
        if (empty($codigo)) {
            echo json_encode(['ok' => false, 'error' => 'Por favor ingresa un código de cupón']);
            exit;
        }

        $calculoPrevio = calcularCarritoServidor($conexion, $_SESSION['carrito'], $codigo);
        if ($calculoPrevio['cupon_aplicado'] !== null) {
            $_SESSION['cupon'] = $codigo;
            echo json_encode([
                'ok' => true,
                'mensaje' => '¡Cupón aplicado correctamente!',
                'carrito' => $calculoPrevio
            ]);
        } else {
            $errorMsg = !empty($calculoPrevio['mensajes']) ? implode(' ', $calculoPrevio['mensajes']) : 'El cupón no es válido o no cumple los requisitos.';
            echo json_encode([
                'ok' => false,
                'error' => $errorMsg
            ]);
        }
        exit;

    case 'remover_cupon':
        unset($_SESSION['cupon']);
        $calculo = calcularCarritoServidor($conexion, $_SESSION['carrito'], null);
        echo json_encode([
            'ok' => true,
            'mensaje' => 'Cupón removido',
            'carrito' => $calculo
        ]);
        exit;

    default:
        echo json_encode(['ok' => false, 'error' => 'Acción no reconocida']);
        exit;
}
?>
