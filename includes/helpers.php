<?php
/**
 * Funciones auxiliares para el sistema
 * Archivo   :   includes/helpers.php
 */

/**
 * Sanitizar entrada de datos
 */
function limpiar($dato) {
    if (is_array($dato)) {
        return array_map('limpiar', $dato);
    }
    
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
    return $dato;
}

/**
 * Validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validar teléfono (formato básico)
 */
function validarTelefono($telefono) {
    return preg_match('/^[0-9\s\-\+\(\)]{7,20}$/', $telefono);
}

/**
 * Validar cédula (Venezuela - formato básico)
 */
function validarCedula($cedula) {
    return preg_match('/^[VEJPGvejpg][0-9]{5,9}$/', $cedula);
}

/**
 * Generar contraseña aleatoria
 */
function generarPassword($longitud = 12) {
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $longitud; $i++) {
        $password .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    return $password;
}

/**
 * Formatear fecha para mostrar
 */
function formatFecha($fecha, $formato = 'd/m/Y H:i') {
    if (empty($fecha)) return '';
    $timestamp = strtotime($fecha);
    return date($formato, $timestamp);
}

/**
 * Redirigir con mensaje
 */
function redirigir($url, $tipo = null, $mensaje = null) {
    if ($tipo && $mensaje) {
        $_SESSION['flash_message'] = [
            'tipo' => $tipo,
            'mensaje' => $mensaje
        ];
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Mostrar mensaje flash
 */
function mostrarFlash() {
    if (isset($_SESSION['flash_message'])) {
        $mensaje = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $tipo = $mensaje['tipo'] === 'success' ? 'success' : 'error';
        $icono = $tipo === 'success' ? 'check-circle' : 'exclamation-circle';
        
        return "<script>mostrarToast('{$mensaje['mensaje']}', '$tipo');</script>";
    }
    return '';
}

/**
 * Exportar a CSV con UTF-8 BOM
 */
function exportarCSV($datos, $nombre_archivo, $columnas = []) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Añadir BOM para UTF-8
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // Escribir encabezados
    if (!empty($columnas)) {
        fputcsv($output, $columnas);
    } elseif (!empty($datos)) {
        fputcsv($output, array_keys($datos[0]));
    }
    
    // Escribir datos
    foreach ($datos as $fila) {
        fputcsv($output, $fila);
    }
    
    fclose($output);
    exit();
}

/**
 * Paginar resultados
 */
function paginar($pagina_actual, $total_registros, $por_pagina = 10, $url = '') {
    $total_paginas = ceil($total_registros / $por_pagina);
    
    if ($total_paginas <= 1) return '';
    
    $html = '<nav class="flex items-center justify-between border-t border-gray-200 px-4 sm:px-0 mt-6">';
    
    // Botón anterior
    if ($pagina_actual > 1) {
        $html .= '<div class="-mt-px w-0 flex-1 flex">';
        $html .= '<a href="' . $url . '?pagina=' . ($pagina_actual - 1) . '" class="border-t-2 border-transparent pt-4 pr-1 inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">';
        $html .= '<i class="fas fa-arrow-left mr-3"></i>';
        $html .= 'Anterior</a></div>';
    } else {
        $html .= '<div class="-mt-px w-0 flex-1 flex"></div>';
    }
    
    // Números de página
    $html .= '<div class="hidden md:-mt-px md:flex">';
    for ($i = 1; $i <= $total_paginas; $i++) {
        if ($i == $pagina_actual) {
            $html .= '<a href="' . $url . '?pagina=' . $i . '" class="border-gold-500 text-gold-600 border-t-2 pt-4 px-4 inline-flex items-center text-sm font-medium">' . $i . '</a>';
        } else {
            $html .= '<a href="' . $url . '?pagina=' . $i . '" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 border-t-2 pt-4 px-4 inline-flex items-center text-sm font-medium">' . $i . '</a>';
        }
    }
    $html .= '</div>';
    
    // Botón siguiente
    if ($pagina_actual < $total_paginas) {
        $html .= '<div class="-mt-px w-0 flex-1 flex justify-end">';
        $html .= '<a href="' . $url . '?pagina=' . ($pagina_actual + 1) . '" class="border-t-2 border-transparent pt-4 pl-1 inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">';
        $html .= 'Siguiente';
        $html .= '<i class="fas fa-arrow-right ml-3"></i></a></div>';
    } else {
        $html .= '<div class="-mt-px w-0 flex-1 flex justify-end"></div>';
    }
    
    $html .= '</nav>';
    
    return $html;
}