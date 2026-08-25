<?php

function debuguear($variable) : string {
    echo "<pre>";
    var_dump($variable);
    echo "</pre>";
    exit;
}

// Escapa / Sanitizar el HTML
function s($html) : string {
    $s = htmlspecialchars($html);
    return $s;
}

function esUltimo(string $actual, string $proximo): bool {
    if($actual !== $proximo){
        return true;
    }
    return false;
}
// Funcion que revisa que el usario este autenticado
// CRITICO: header() NO detiene la ejecucion de PHP, por eso siempre
// debe ir acompañado de exit; si no, el resto del controlador se sigue
// ejecutando y renderiza contenido protegido aunque el usuario no
// tenga sesion.
function isAuth(): void{
    if(!isset($_SESSION['login'])){
        header('Location:/');
        exit;
    }
}

function isAdmin() : void {
    if(!isset($_SESSION['admin'])) {
        header('Location: /');
        exit;
    }
}

// Version para endpoints de API (JSON): en vez de redirigir con Location
// (que no tiene sentido para un fetch/AJAX), responde 401 y corta la
// ejecucion.
function isAuthAPI(): void {
    if(!isset($_SESSION['login'])){
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }
}

// --- Proteccion CSRF ---
// Genera (o reutiliza) un token por sesion.
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Expone el token como <input hidden> para incluir dentro de un <form>.
function csrfInput(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

// Valida el token recibido por POST (formularios clasicos) contra el
// de la sesion. Si no coincide, corta la ejecucion con 403.
function csrfVerificar(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo 'Token CSRF invalido o ausente. Recarga la pagina e intenta de nuevo.';
        exit;
    }
}

// Version para endpoints de API llamados via fetch/JS (responde JSON
// en vez de HTML plano).
function csrfVerificarAPI(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Token CSRF invalido o ausente, recarga la pagina.']);
        exit;
    }
}