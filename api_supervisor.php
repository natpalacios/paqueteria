<?php

require_once "conexion.php";

header(
    'Content-Type: application/json; charset=utf-8'
);


$accion =
    $_GET['accion'] ?? '';



/*
|--------------------------------------------------------------------------
| NOTIFICACIONES
|--------------------------------------------------------------------------
*/

if ($accion === 'notificaciones') {


    $sql = "
        SELECT
            id,
            codigo,
            estado,
            fecha_envio,
            municipio,
            departamento,
            direccion,
            responsable_nombre
        FROM envios
        ORDER BY id DESC
        LIMIT 20
    ";


    $resultado =
        $conexion->query($sql);


    if (!$resultado) {

        echo json_encode(
            [

                'ok' => false,

                'mensaje' =>
                    $conexion->error

            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;

    }


    $notificaciones = [];


    while (
        $fila =
        $resultado->fetch_assoc()
    ) {


        $notificaciones[] = [

            'id' =>
                (int)$fila['id'],

            'codigo' =>
                $fila['codigo'],

            'estado' =>
                $fila['estado'],

            'fecha_envio' =>
                $fila['fecha_envio'],

            'municipio' =>
                $fila['municipio'],

            'departamento' =>
                $fila['departamento'],

            'direccion' =>
                $fila['direccion'],

            'responsable' =>
                $fila['responsable_nombre']

        ];

    }


    $ultimoId = 0;


    if (!empty($notificaciones)) {

        $ultimoId =
            $notificaciones[0]['id'];

    }


    echo json_encode(
        [

            'ok' => true,

            'ultimo_id' =>
                $ultimoId,

            'notificaciones' =>
                $notificaciones

        ],
        JSON_UNESCAPED_UNICODE
    );


    exit;

}



/*
|--------------------------------------------------------------------------
| ÚLTIMO ENVÍO
|--------------------------------------------------------------------------
*/

if ($accion === 'ultimo') {


    $sql = "
        SELECT
            id,
            codigo,
            fecha_envio
        FROM envios
        ORDER BY id DESC
        LIMIT 1
    ";


    $resultado =
        $conexion->query($sql);


    if (!$resultado) {

        echo json_encode(
            [

                'ok' => false

            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;

    }


    $fila =
        $resultado->fetch_assoc();


    echo json_encode(
        [

            'ok' => true,

            'envio' =>
                $fila ?: null

        ],
        JSON_UNESCAPED_UNICODE
    );


    exit;

}



/*
|--------------------------------------------------------------------------
| ACCIÓN DESCONOCIDA
|--------------------------------------------------------------------------
*/

echo json_encode(
    [

        'ok' => false,

        'mensaje' =>
            'Acción no válida.'

    ],
    JSON_UNESCAPED_UNICODE
);

?>