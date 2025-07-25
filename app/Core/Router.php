<?php
namespace Core;

class Router {
    protected $routes = [];

    public function __construct() {
        $this->routes = require __DIR__ . '/../../routes/web.php';
    }

    public function dispatch($uri) {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = trim($path, '/');
        foreach ($this->routes as $route => $action) {
            if ($path === trim($route, '/')) {
                list($controller, $method) = explode('@', $action);
                $controllerClass = 'App\\Controllers\\' . $controller;
                if (class_exists($controllerClass)) {
                    $ctrl = new $controllerClass();
                    if (method_exists($ctrl, $method)) {
                        return $ctrl->$method();
                    }
                }
                http_response_code(404);
                echo 'Méthode ou contrôleur introuvable';
                return;
            }
        }
        http_response_code(404);
        echo 'Page non trouvée';
    }
} 