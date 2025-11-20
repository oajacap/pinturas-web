<?php
require_once __DIR__ . "/../models/Usuario.php";

class LoginController {
    private $usuario;

    public function __construct($db) {
        $this->usuario = new Usuario($db);
    }

    public function login($identificador, $password) {
        error_log("=== LOGIN INTENT ===");
        error_log("Identificador: " . $identificador);
        
        $user = null;
        
        if (filter_var($identificador, FILTER_VALIDATE_EMAIL)) {
            error_log("Buscando por EMAIL");
            $user = $this->usuario->obtenerPorEmail($identificador);
        }
        
        if (!$user) {
            error_log("Buscando por USERNAME");
            $user = $this->usuario->obtenerPorUsuario($identificador);
        }
        
        if (!$user) {
            error_log(" Usuario NO encontrado");
            return false;
        }
        
        error_log(" Usuario encontrado: " . $user['email']);
        error_log("Rol: " . $user['nombre_rol']);
        
        if ($user['password'] !== $password) {
            error_log(" Contraseña incorrecta");
            return false;
        }
        
        error_log(" Contraseña correcta");
        
        
        $this->iniciarSesion($user);
        
        
        $this->usuario->actualizarUltimoLogin($user['usuario_id']);
        
        return true;
    }

    private function iniciarSesion($user) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $user['usuario_id'];
        $_SESSION['username'] = $user['username'] ?? '';
        $_SESSION['email'] = $user['email'];
        $_SESSION['rol_id'] = $user['rol_id'];
        $_SESSION['nombre_rol'] = $user['nombre_rol'];
        $_SESSION['permisos'] = json_decode($user['permisos'], true);
        $_SESSION['logueado'] = true;
        $_SESSION['tiempo_login'] = time();
        
        error_log(" Sesión iniciada correctamente");
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = array();

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }

        session_destroy();
    }

    public function estaLogueado() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['logueado']) && $_SESSION['logueado'] === true;
    }

    public function obtenerUsuarioLogueado() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($this->estaLogueado()) {
            return [
                'usuario_id' => $_SESSION['usuario_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'rol_id' => $_SESSION['rol_id'],
                'nombre_rol' => $_SESSION['nombre_rol'],
                'permisos' => $_SESSION['permisos']
            ];
        }

        return null;
    }

    public function tienePermiso($modulo) {
        if (!$this->estaLogueado()) {
            return false;
        }

        $permisos = $_SESSION['permisos'] ?? [];
        $modulos = $permisos['modulos'] ?? [];

        
        if (in_array('todos', $modulos)) {
            return true;
        }

        return in_array($modulo, $modulos);
    }

    public function esRol($nombre_rol) {
        if (!$this->estaLogueado()) {
            return false;
        }

        return $_SESSION['nombre_rol'] === $nombre_rol;
    }

    public function verificarInactividad() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($this->estaLogueado()) {
            $timeout = 1800; 
            $tiempo_actual = time();
            $tiempo_login = $_SESSION['tiempo_login'] ?? 0;

            if (($tiempo_actual - $tiempo_login) > $timeout) {
                $this->logout();
                return false;
            } else {
                $_SESSION['tiempo_login'] = $tiempo_actual;
            }
        }

        return true;
    }

    public function requiereLogin() {
        if (!$this->estaLogueado()) {
            header("Location: index.php?action=loginForm&mensaje=" . urlencode("Debe iniciar sesión"));
            exit();
        }

        if (!$this->verificarInactividad()) {
            header("Location: index.php?action=loginForm&mensaje=" . urlencode("Sesión expirada"));
            exit();
        }
    }

    public function requierePermiso($modulo) {
        $this->requiereLogin();

        if (!$this->tienePermiso($modulo)) {
            
            $_SESSION['error'] = "No tiene permisos para acceder al módulo: " . ucfirst($modulo);
            header("Location: index.php?action=dashboard");
            exit();
        }
    }

    public function requiereRol($nombre_rol) {
        $this->requiereLogin();

        if (!$this->esRol($nombre_rol)) {
            $_SESSION['error'] = "Acceso denegado. Requiere rol: " . $nombre_rol;
            header("Location: index.php?action=dashboard");
            exit();
        }
    }

    public function redirigirSegunRol() {
        if (!$this->estaLogueado()) {
            header("Location: index.php?action=loginForm");
            exit();
        }

        $rol = $_SESSION['nombre_rol'];

        switch ($rol) {
            case 'Administrador':
        
                header("Location: index.php?action=dashboard");
                break;

            case 'Gerente':
            
                header("Location: index.php?action=reportes");
                break;

            case 'Digitador':
            
                header("Location: index.php?action=listarProductos");
                break;

            case 'Cajero':
                
                header("Location: index.php?action=formularioFactura");
                break;

            case 'Cliente':
                
                header("Location: index.php?action=catalogoProductos");
                break;

            default:
                
                header("Location: index.php?action=dashboard");
                break;
        }
        exit();
    }

    public function obtenerModulosDisponibles() {
        if (!$this->estaLogueado()) {
            return [];
        }

        $permisos = $_SESSION['permisos'] ?? [];
        $modulos = $permisos['modulos'] ?? [];

        
        if (in_array('todos', $modulos)) {
            return [
                'clientes', 'empleados', 'productos', 'ventas', 
                'facturas', 'reportes', 'inventario', 'pagos',
                'proveedores', 'sucursales', 'configuracion'
            ];
        }

        return $modulos;
    }

    public function registrar($username, $email, $password, $rol_id = 5) {
        if ($this->usuario->usernameExiste($username)) {
            return ['success' => false, 'mensaje' => 'El nombre de usuario ya existe'];
        }

        if ($this->usuario->emailExiste($email)) {
            return ['success' => false, 'mensaje' => 'El email ya está registrado'];
        }

        if ($this->usuario->crear($username, $email, $password, $rol_id)) {
            return ['success' => true, 'mensaje' => 'Usuario registrado exitosamente'];
        }

        return ['success' => false, 'mensaje' => 'Error al registrar usuario'];
    }
}
?>