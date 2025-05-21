<?php
// Incluindo o autoloader e inicializando o roteamento
define('BASE_PATH', __DIR__);  // Defina o caminho base corretamente

// Autoloader
function autoload($className)
{
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/src/' . $className . '.php';  // Ajustado para a nova estrutura
    if (file_exists($file)) {
        require_once $file;
    }
}

spl_autoload_register('autoload');

// Roteamento
$requestUri = $_SERVER['REQUEST_URI'];

$requestUri = str_replace('/api/clients/bm', '', $requestUri); // local
//$requestUri = str_replace('/api/clients/bm', '', $requestUri); // production

// Se houver query string, remove
$requestUri = explode('?', $requestUri)[0];

// Remove a barra final, se houver
$requestUri = rtrim($requestUri, '/');

// Defina a lógica de roteamento (ajuste para a raiz)
switch ($requestUri) {

    case '/apps/rdstation/webhooks': // Rota do index
        $controller = new \App\Controllers\IndexController();
        $controller->webhooks();
        break;
    case '/apps/rdstation/leads/campanha_base': // Rota do index
        $controller = new \App\Controllers\IndexController();
        $controller->leads_campanha_base();
        break;
    case '/apps/rdstation/oportunidade': // Rota do index
        $controller = new \App\Controllers\IndexController();
        $controller->oportunidade();
        break;
    case '/apps/3cplus/qualifications': // Rota do ligacoes qualificadas
        $controller = new \App\Controllers\IndexController();
        $controller->qualifications();
        break;
    case '/apps/rdconversas/callback': // Rota do ligacoes qualificadas
        $controller = new \App\Controllers\IndexController();
        $controller->callbackRdConversas();
        break;
    case '/apps/nl/test': // Rota do ligacoes qualificadas
        $controller = new \App\Controllers\IndexController();
        $controller->test();
        break;
    default:
        echo "Página não encontrada!";
        break;
}
