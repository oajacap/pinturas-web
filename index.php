<?php

session_start();

require_once "config/Database.php";
require_once "controllers/ClienteController.php";
require_once "controllers/EmpleadoController.php";
require_once "controllers/ProductoController.php";
require_once "controllers/FacturaController.php";
require_once "controllers/LoginController.php";
require_once "controllers/ProveedorController.php";
require_once "controllers/SucursalController.php";
require_once "controllers/TipoPagoController.php";
require_once "controllers/CotizacionController.php";
require_once "controllers/ReporteController.php";


$database = new Database();
$db = $database->getConnection();


$loginController = new LoginController($db);

$action = $_GET['action'] ?? 'home';


$rutasPublicas = [
    'home',
    'loginForm',
    'login',
    'registro',
    'procesarRegistro',
    'catalogoProductos',
    'verCarrito',
    'agregarCarrito',
    'actualizarCarrito',
    'eliminarDelCarrito',
    'vaciarCarrito',
    'solicitarCotizacion',
    'crearCotizacionPublica',
    'imprimirCotizacion'
];


if (!in_array($action, $rutasPublicas)) {
    $loginController->requiereLogin();
    $loginController->verificarInactividad();
}


$permisosPorAccion = [
   
    'listarClientes' => 'clientes',
    'formularioCliente' => 'clientes',
    'agregarCliente' => 'clientes',
    'editarCliente' => 'clientes',
    'actualizarCliente' => 'clientes',
    'eliminarCliente' => 'clientes',
    
    
    'listarEmpleados' => 'productos',
    'formularioEmpleado' => 'productos',
    'guardarEmpleado' => 'productos',
    'editarEmpleado' => 'productos',
    'eliminarEmpleado' => 'productos',
    
    
    'listarProductos' => 'productos',
    'formularioProducto' => 'productos',
    'guardarProducto' => 'productos',
    'editarProducto' => 'productos',
    'actualizarProducto' => 'productos',
    'eliminarProducto' => 'productos',
    
    
    'listarFacturas' => 'ventas',
    'formularioFactura' => 'ventas',
    'crearFactura' => 'ventas',
    'imprimirFactura' => 'ventas',
    'anularFactura' => 'ventas',
    
    
    'reportes' => 'reportes',
    
    
    'listarProveedores' => 'productos',
    'formularioProveedor' => 'productos',
    'guardarProveedor' => 'productos',
    'editarProveedor' => 'productos',
    'actualizarProveedor' => 'productos',
    'eliminarProveedor' => 'productos',
    
   
    'listarSucursales' => 'productos',
    'formularioSucursal' => 'productos',
    'guardarSucursal' => 'productos',
    'editarSucursal' => 'productos',
    'actualizarSucursal' => 'productos',
    'cambiarEstadoSucursal' => 'productos',
    'eliminarSucursal' => 'productos',
    
  
    'listarTiposPago' => 'ventas',
    'formularioTipoPago' => 'ventas',
    'guardarTipoPago' => 'ventas',
    'editarTipoPago' => 'ventas',
    'actualizarTipoPago' => 'ventas',
    'cambiarEstadoTipoPago' => 'ventas',
    'eliminarTipoPago' => 'ventas',
];


if (isset($permisosPorAccion[$action])) {
    $permisoRequerido = $permisosPorAccion[$action];
    $loginController->requierePermiso($permisoRequerido);
}


switch ($action) {

    
    case 'home':
        require "views/home.php";
        break;

    case 'loginForm':
        $mensaje = $_GET['mensaje'] ?? '';
        require "views/login.php";
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['usuario'] ?? '';
            $password = $_POST['contrasena'] ?? '';

            if ($loginController->login($username, $password)) {
            
                $loginController->redirigirSegunRol();
            } else {
                $mensaje = urlencode("Usuario o contraseña incorrectos");
                header("Location: index.php?action=loginForm&mensaje=$mensaje");
                exit();
            }
        }
        break;

    case 'logout':
        $loginController->logout();
        header("Location: index.php?action=home");
        exit();
        break;

    case 'registro':
        require "views/registro.php";
        break;

    case 'procesarRegistro':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resultado = $loginController->registrar(
                $_POST['username'],
                $_POST['email'],
                $_POST['password']
            );
            
            if ($resultado['success']) {
                header("Location: index.php?action=loginForm&mensaje=" . urlencode($resultado['mensaje']));
            } else {
                header("Location: index.php?action=registro&error=" . urlencode($resultado['mensaje']));
            }
            exit();
        }
        break;


    case 'dashboard':
        $loginController->requiereLogin();
        $usuarioActual = $loginController->obtenerUsuarioLogueado();
        $modulosDisponibles = $loginController->obtenerModulosDisponibles();
        require "views/dashboard.php";
        break;


    case 'listarClientes':
        $clienteController = new ClienteController($db);
        $clientes = $clienteController->listarClientes();
        require "views/clientes.php";
        break;

    case 'formularioCliente':
        require "views/AgregarCliente.php";
        break;

    case 'agregarCliente':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $clienteController = new ClienteController($db);
            $clienteController->agregarCliente(
                $_POST['usuario_id'],
                $_POST['nombre_cliente'],
                $_POST['nit'],
                $_POST['email'],
                $_POST['telefono'],
                $_POST['direccion'],
                isset($_POST['acepta_promociones']) ? 1 : 0
            );
        }
        header("Location: index.php?action=listarClientes");
        break;

    case 'editarCliente':
        if (isset($_GET['cliente_id'])) {
            $clienteController = new ClienteController($db);
            $cliente_id = $_GET['cliente_id'];
            $cliente = $clienteController->obtenerCliente($cliente_id);
            require "views/EditarCliente.php";
        } else {
            header("Location: index.php?action=listarClientes");
        }
        break;

    case 'actualizarCliente':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $clienteController = new ClienteController($db);
            $clienteController->actualizarCliente(
                $_POST['cliente_id'],
                $_POST['usuario_id'],
                $_POST['nombre_cliente'],
                $_POST['nit'],
                $_POST['email'],
                $_POST['telefono'],
                $_POST['direccion'],
                isset($_POST['acepta_promociones']) ? 1 : 0
            );
        }
        header("Location: index.php?action=listarClientes");
        break;

    case 'eliminarCliente':
        if (isset($_GET['cliente_id'])) {
            $clienteController = new ClienteController($db);
            $clienteController->eliminarCliente($_GET['cliente_id']);
        }
        header("Location: index.php?action=listarClientes");
        break;

    
    case 'listarEmpleados':
        $empleadoController = new EmpleadoController($db);
        $empleados = $empleadoController->listarEmpleados();
        require "views/empleados.php";
        break;

    case 'formularioEmpleado':
        require "views/AgregarEmpleado.php";
        break;

    case 'guardarEmpleado':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $empleadoController = new EmpleadoController($db);
            $empleadoController->agregarEmpleado(
                $_POST['usuario_id'],
                $_POST['nombre_empleado'],
                $_POST['apellido_empleado'],
                $_POST['sucursal_id'],
                isset($_POST['activo']) ? 1 : 0,
                $_POST['fecha_contratacion']
            );
        }
        header("Location: index.php?action=listarEmpleados");
        break;

    case 'editarEmpleado':
        if (isset($_GET['empleado_id'])) {
            $empleadoController = new EmpleadoController($db);
            $empleado_id = $_GET['empleado_id'];
            $empleado = $empleadoController->obtenerEmpleado($empleado_id);
            require "views/EditarEmpleado.php";
        } else {
            header("Location: index.php?action=listarEmpleados");
        }
        break;

    case 'eliminarEmpleado':
        if (isset($_GET['empleado_id'])) {
            $empleadoController = new EmpleadoController($db);
            $empleadoController->eliminarEmpleado($_GET['empleado_id']);
        }
        header("Location: index.php?action=listarEmpleados");
        break;

   
    case 'listarProductos':
        $productoController = new ProductoController($db);
        $productos = $productoController->listarProductos();
        require "views/productos.php";
        break;

    case 'formularioProducto':
        require "views/AgregarProductos.php";
        break;

    case 'guardarProducto':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productoController = new ProductoController($db);
            $productoController->agregarProducto($_POST);
        }
        header("Location: index.php?action=listarProductos");
        break;

    case 'editarProducto':
        if (isset($_GET['producto_id'])) {
            $productoController = new ProductoController($db);
            $producto_id = $_GET['producto_id'];
            $producto = $productoController->obtenerProducto($producto_id);
            require "views/EditarProductos.php";
        } else {
            header("Location: index.php?action=listarProductos");
        }
        break;

    case 'actualizarProducto':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productoController = new ProductoController($db);
            $productoController->actualizarProducto($_POST['producto_id'], $_POST);
        }
        header("Location: index.php?action=listarProductos");
        break;
        
    case 'eliminarProducto':
        if (isset($_GET['producto_id'])) {
            $productoController = new ProductoController($db);
            $productoController->eliminarProducto($_GET['producto_id']);
        }
        header("Location: index.php?action=listarProductos");
        break;
        
    case 'listarFacturas':
        $facturaController = new FacturaController($db);
        $facturas = $facturaController->listarFacturas();
        require "views/factura.php";
        break;

    case 'formularioFactura':
        $clienteController = new ClienteController($db);
        $empleadoController = new EmpleadoController($db);
        $productoController = new ProductoController($db);
        
        $clientes = $clienteController->listarClientes();
        $empleados = $empleadoController->listarEmpleados();
        $productos = $productoController->listarProductos();
        require "views/AgregarFactura.php";
        break;

    case 'crearFactura':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $facturaController = new FacturaController($db);
            
            $datosFactura = [
                'numero_factura' => $_POST['numero_factura'],
                'serie_factura' => $_POST['serie_factura'],
                'cliente_id' => $_POST['cliente_id'],
                'empleado_id' => $_POST['empleado_id'],
                'sucursal_id' => $_POST['sucursal_id'],
                'subtotal' => 0,
                'impuestos' => 0,
                'total' => 0
            ];

            $detalles = $_POST['detalles'];
            $subtotal = 0;
            foreach($detalles as $d){
                $subtotal += $d['cantidad'] * $d['precio_unitario'] * (1 - $d['porcentaje_descuento']/100);
            }
            $impuestos = $subtotal * 0.12;
            $total = $subtotal + $impuestos;

            $datosFactura['subtotal'] = $subtotal;
            $datosFactura['impuestos'] = $impuestos;
            $datosFactura['total'] = $total;

            $facturaController->crearFacturaConDetalles($datosFactura, $detalles);
        }
        header("Location: index.php?action=listarFacturas");
        break;

    case 'imprimirFactura':
        if(isset($_GET['factura_id'])){
            $facturaController = new FacturaController($db);
            $factura = $facturaController->obtenerFactura($_GET['factura_id']);
            require "views/ImprimirFactura.php";
        } else {
            header("Location: index.php?action=listarFacturas");
        }
        break;

    case 'anularFactura':
        if(isset($_GET['factura_id'])){
            $facturaController = new FacturaController($db);
            if($facturaController->anularFactura($_GET['factura_id'])){
                $_SESSION['mensaje'] = "Factura anulada correctamente";
            } else {
                $_SESSION['error'] = "Error al anular la factura";
            }
        }
        header("Location: index.php?action=listarFacturas");
        break;
    
 
    case 'solicitarCotizacion':
        $productoController = new ProductoController($db);
        $productos = $productoController->listarProductos();
        require "views/SolicitarCotizacion.php";
        break;

    case 'crearCotizacionPublica':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cotizacionController = new CotizacionController($db);
            
            try {
                $email = trim($_POST['email']);
                $stmt = $db->prepare("SELECT cliente_id FROM cliente WHERE email = ?");
                $stmt->execute([$email]);
                $clienteExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($clienteExistente) {
                    $cliente_id = $clienteExistente['cliente_id'];
                } else {
                    $stmt = $db->prepare("INSERT INTO cliente 
                                        (nombre_cliente, nit, email, telefono, direccion, acepta_promociones, usuario_id, fecha_registro) 
                                        VALUES (?, ?, ?, ?, ?, ?, NULL, NOW())");
                    $stmt->execute([
                        $_POST['nombre_cliente'],
                        $_POST['nit'] ?? 'CF',
                        $email,
                        $_POST['telefono'] ?? '',
                        $_POST['direccion'] ?? '',
                        isset($_POST['acepta_promociones']) ? 1 : 0
                    ]);
                    $cliente_id = $db->lastInsertId();
                }
                
                $detalles = $_POST['detalles'];
                $subtotal = 0;
                
                foreach($detalles as &$d){
                    if (!isset($d['porcentaje_descuento'])) {
                        $stmt = $db->prepare("SELECT porcentaje_descuento FROM producto WHERE producto_id = ?");
                        $stmt->execute([$d['producto_id']]);
                        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
                        $d['porcentaje_descuento'] = $producto['porcentaje_descuento'] ?? 0;
                    }
                    $subtotal += $d['cantidad'] * $d['precio_unitario'] * (1 - ($d['porcentaje_descuento'] ?? 0)/100);
                }
                
                $impuestos = $subtotal * 0.12;
                $total = $subtotal + $impuestos;
                
                $datosCotizacion = [
                    'cliente_id' => $cliente_id,
                    'empleado_id' => null,
                    'sucursal_id' => null,
                    'subtotal' => $subtotal,
                    'impuestos' => $impuestos,
                    'total' => $total
                ];
                
                $cotizacion_id = $cotizacionController->crearCotizacionConDetalles($datosCotizacion, $detalles);
                
                if ($cotizacion_id) {
                    $_SESSION['mensaje'] = "Cotizacion creada exitosamente";
                    header("Location: index.php?action=imprimirCotizacion&cotizacion_id=" . $cotizacion_id);
                    exit();
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
                header("Location: index.php?action=solicitarCotizacion");
                exit();
            }
        }
        break;

    case 'imprimirCotizacion':
        if(isset($_GET['cotizacion_id'])){
            $cotizacionController = new CotizacionController($db);
            $cotizacion = $cotizacionController->obtenerCotizacion($_GET['cotizacion_id']);
            
            if($cotizacion) {
                require "views/ImprimirCotizacion.php";
            } else {
                $_SESSION['error'] = "Cotizacion no encontrada";
                header("Location: index.php?action=home");
                exit();
            }
        }
        break;
       
    case 'reportes':
        $reporteController = new ReporteController($db);
        
        if(isset($_GET['reporte'])){
            $reporte = $_GET['reporte'];
            
            switch($reporte){
                case '1':
                    if(isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])){
                        $resultado = $reporteController->reporteVentasPorTipoPago($_GET['fecha_inicio'], $_GET['fecha_fin']);
                    }
                    break;
                case '2':
                    if(isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])){
                        $limite = $_GET['limite'] ?? 10;
                        $resultado = $reporteController->reporteProductosMayorIngreso($_GET['fecha_inicio'], $_GET['fecha_fin'], $limite);
                    }
                    break;
                case '3':
                    if(isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])){
                        $limite = $_GET['limite'] ?? 10;
                        $resultado = $reporteController->reporteProductosMasVendidos($_GET['fecha_inicio'], $_GET['fecha_fin'], $limite);
                    }
                    break;
                case '4':
                    $resultado = $reporteController->reporteInventarioActual();
                    break;
                case '5':
                    if(isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])){
                        $limite = $_GET['limite'] ?? 10;
                        $resultado = $reporteController->reporteProductosMenosVendidos($_GET['fecha_inicio'], $_GET['fecha_fin'], $limite);
                    }
                    break;
                case '6':
                    $resultado = $reporteController->reporteProductosSinStock();
                    break;
                case '7':
                    if(isset($_GET['numero_factura'])){
                        $resultado = $reporteController->reporteDetalleFactura($_GET['numero_factura']);
                    }
                    break;
            }
        }
        require "views/reportes.php";
        break;

    
    case 'listarProveedores':
        $proveedorController = new ProveedorController($db);
        $proveedores = $proveedorController->listarProveedores();
        require "views/proveedores.php";
        break;

    case 'formularioProveedor':
        require "views/AgregarProveedor.php";
        break;

    case 'guardarProveedor':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $proveedorController = new ProveedorController($db);
            $proveedorController->agregarProveedor(
                $_POST['nombre_proveedor'],
                $_POST['contacto_proveedor'] ?? '',
                $_POST['telefono_proveedor'] ?? '',
                $_POST['email_proveedor'] ?? '',
                $_POST['direccion_proveedor'] ?? ''
            );
        }
        header("Location: index.php?action=listarProveedores");
        exit();
        break;

    case 'editarProveedor':
        $proveedor_id = $_GET['proveedor_id'] ?? null;
        if ($proveedor_id) {
            $proveedorController = new ProveedorController($db);
            $proveedor = $proveedorController->obtenerProveedor($proveedor_id);
            require "views/EditarProveedor.php";
        } else {
            header("Location: index.php?action=listarProveedores");
            exit();
        }
        break;

    case 'actualizarProveedor':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $proveedorController = new ProveedorController($db);
            $proveedorController->actualizarProveedor(
                $_POST['proveedor_id'],
                $_POST['nombre_proveedor'],
                $_POST['contacto_proveedor'] ?? '',
                $_POST['telefono_proveedor'] ?? '',
                $_POST['email_proveedor'] ?? '',
                $_POST['direccion_proveedor'] ?? ''
            );
        }
        header("Location: index.php?action=listarProveedores");
        exit();
        break;

    case 'eliminarProveedor':
        $proveedor_id = $_GET['proveedor_id'] ?? null;
        if ($proveedor_id) {
            $proveedorController = new ProveedorController($db);
            $proveedorController->eliminarProveedor($proveedor_id);
        }
        header("Location: index.php?action=listarProveedores");
        exit();
        break;

    
    case 'listarSucursales':
        $sucursalController = new SucursalController($db);
        $sucursales = $sucursalController->listarSucursales();
        require "views/sucursales.php";
        break;

    case 'formularioSucursal':
        require "views/AgregarSucursal.php";
        break;

    case 'guardarSucursal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sucursalController = new SucursalController($db);
            $sucursalController->agregarSucursal(
                $_POST['nombre_sucursal'],
                $_POST['direccion_sucursal'],
                $_POST['telefono_sucursal'] ?? '',
                !empty($_POST['latitud']) ? $_POST['latitud'] : null,
                !empty($_POST['longitud']) ? $_POST['longitud'] : null,
                isset($_POST['activa']) ? 1 : 0
            );
        }
        header("Location: index.php?action=listarSucursales");
        exit();
        break;

    case 'editarSucursal':
        $sucursal_id = $_GET['sucursal_id'] ?? null;
        if ($sucursal_id) {
            $sucursalController = new SucursalController($db);
            $sucursal = $sucursalController->obtenerSucursal($sucursal_id);
            require "views/EditarSucursal.php";
        } else {
            header("Location: index.php?action=listarSucursales");
            exit();
        }
        break;

    case 'actualizarSucursal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sucursalController = new SucursalController($db);
            $sucursalController->actualizarSucursal(
                $_POST['sucursal_id'],
                $_POST['nombre_sucursal'],
                $_POST['direccion_sucursal'],
                $_POST['telefono_sucursal'] ?? '',
                !empty($_POST['latitud']) ? $_POST['latitud'] : null,
                !empty($_POST['longitud']) ? $_POST['longitud'] : null,
                isset($_POST['activa']) ? 1 : 0
            );
        }
        header("Location: index.php?action=listarSucursales");
        exit();
        break;

    case 'cambiarEstadoSucursal':
        $sucursal_id = $_GET['sucursal_id'] ?? null;
        $estado = $_GET['estado'] ?? 0;
        if ($sucursal_id) {
            $sucursalController = new SucursalController($db);
            $sucursalController->cambiarEstadoSucursal($sucursal_id, $estado);
        }
        header("Location: index.php?action=listarSucursales");
        exit();
        break;

    case 'eliminarSucursal':
        $sucursal_id = $_GET['sucursal_id'] ?? null;
        if ($sucursal_id) {
            $sucursalController = new SucursalController($db);
            $sucursalController->eliminarSucursal($sucursal_id);
        }
        header("Location: index.php?action=listarSucursales");
        exit();
        break;

    
    case 'listarTiposPago':
        $tipoPagoController = new TipoPagoController($db);
        $tiposPago = $tipoPagoController->listarTiposPago();
        require "views/tipos_pago.php";
        break;

    case 'formularioTipoPago':
        require "views/AgregarTipoPago.php";
        break;

    case 'guardarTipoPago':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipoPagoController = new TipoPagoController($db);
            $tipoPagoController->agregarTipoPago(
                $_POST['nombre_pago'],
                $_POST['descripcion'] ?? '',
                isset($_POST['activo']) ? 1 : 0
            );
        }
        header("Location: index.php?action=listarTiposPago");
        exit();
        break;

    case 'editarTipoPago':
        $tipo_pago_id = $_GET['tipo_pago_id'] ?? null;
        if ($tipo_pago_id) {
            $tipoPagoController = new TipoPagoController($db);
            $tipoPago = $tipoPagoController->obtenerTipoPago($tipo_pago_id);
            require "views/EditarTipoPago.php";
        } else {
            header("Location: index.php?action=listarTiposPago");
            exit();
        }
        break;

    case 'actualizarTipoPago':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipoPagoController = new TipoPagoController($db);
            $tipoPagoController->actualizarTipoPago(
                $_POST['tipo_pago_id'],
                $_POST['nombre_pago'],
                $_POST['descripcion'] ?? '',
                isset($_POST['activo']) ? 1 : 0
            );
        }
        header("Location: index.php?action=listarTiposPago");
        exit();
        break;

    case 'cambiarEstadoTipoPago':
        $tipo_pago_id = $_GET['tipo_pago_id'] ?? null;
        $estado = $_GET['estado'] ?? 0;
        if ($tipo_pago_id) {
            $tipoPagoController = new TipoPagoController($db);
            $tipoPagoController->cambiarEstadoTipoPago($tipo_pago_id, $estado);
        }
        header("Location: index.php?action=listarTiposPago");
        exit();
        break;

    case 'eliminarTipoPago':
        $tipo_pago_id = $_GET['tipo_pago_id'] ?? null;
        if ($tipo_pago_id) {
            $tipoPagoController = new TipoPagoController($db);
            $tipoPagoController->eliminarTipoPago($tipo_pago_id);
        }
        header("Location: index.php?action=listarTiposPago");
        exit();
        break;

    
    case 'catalogoProductos':
        $productoController = new ProductoController($db);
        require "views/catalogo_productos.php";
        break;

    case 'verCarrito':
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }
        require "views/ver_carrito.php";
        break;

    case 'agregarCarrito':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['carrito'])) {
                $_SESSION['carrito'] = [];
            }
            
            $producto_id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
            $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT) ?? 1;
            
            if ($producto_id) {
                $productoController = new ProductoController($db);
                $producto = $productoController->obtenerProducto($producto_id);
                
                if ($producto) {
                    $precio_final = $producto['precio_base'] * (1 - ($producto['porcentaje_descuento'] / 100));
                    
                    $_SESSION['carrito'][] = [
                        'id' => $producto['producto_id'],
                        'nombre' => $producto['nombre'],
                        'sku' => $producto['codigo_sku'],
                        'precio' => $precio_final,
                        'cantidad' => $cantidad
                    ];
                    $_SESSION['mensaje'] = "Producto agregado";
                }
            }
        }
        header("Location: index.php?action=verCarrito");
        exit();
        break;

    case 'actualizarCarrito':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $index = filter_input(INPUT_POST, 'index', FILTER_VALIDATE_INT);
            $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);
            
            if (isset($_SESSION['carrito'][$index]) && $cantidad > 0) {
                $_SESSION['carrito'][$index]['cantidad'] = $cantidad;
            }
        }
        header("Location: index.php?action=verCarrito");
        exit();
        break;

    case 'eliminarDelCarrito':
        if (isset($_GET['index'])) {
            $index = filter_input(INPUT_GET, 'index', FILTER_VALIDATE_INT);
            if (isset($_SESSION['carrito'][$index])) {
                unset($_SESSION['carrito'][$index]);
                $_SESSION['carrito'] = array_values($_SESSION['carrito']);
            }
        }
        header("Location: index.php?action=verCarrito");
        exit();
        break;

    case 'vaciarCarrito':
        $_SESSION['carrito'] = [];
        header("Location: index.php?action=verCarrito");
        exit();
        break;

    
    default:
        header("Location: index.php?action=home");
        break;
}
?>