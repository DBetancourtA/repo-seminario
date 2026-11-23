<?php
// Copia este archivo como config.php y ajusta los valores.
return [
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'seminario',
        'user'    => 'seminario',
        'pass'    => 'seminario',
    ],
    'app' => [
        'nombre'       => 'Seminario de Titulación',
        'institucion'  => 'Instituto Superior Tecnológico',
        'carrera'      => 'Tecnología Superior en Desarrollo de Software',
        'zona_horaria' => 'America/Guayaquil',
        // URL pública completa, sin barra final. Vacío = se detecta sola.
        'url'          => '',
        // Tamaño máximo por imagen subida (MB)
        'max_mb'       => 5,
        // Mínimo de caracteres en la descripción de un avance
        'min_actividad' => 80,
        // Pon en false después de crear la cuenta del docente
        'instalador_activo' => true,
        'debug'        => false,
    ],
];
