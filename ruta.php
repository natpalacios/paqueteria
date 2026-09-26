<?php

header('Content-Type: application/json; charset=utf-8');

function responder($datos)
{
    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}




function consultarUrl($url)
{

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,

        CURLOPT_USERAGENT =>
            'GrupoSALogistic/1.0',

        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Accept-Language: es'
        ]
    ]);


    $respuesta =
        curl_exec($ch);


    if ($respuesta === false) {

        $error =
            curl_error($ch);

        curl_close($ch);

        throw new Exception(
            $error
        );

    }


    $codigo =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    curl_close($ch);


    if ($codigo < 200 || $codigo >= 300) {

        throw new Exception(
            "HTTP " . $codigo
        );

    }


    return $respuesta;

}




function geocodificar(
    $consulta
) {

    $url =
        'https://nominatim.openstreetmap.org/search?' .
        http_build_query([
            'format' => 'jsonv2',
            'limit' => 1,
            'countrycodes' => 'ni',
            'q' => $consulta
        ]);


    $respuesta =
        consultarUrl(
            $url
        );


    $datos =
        json_decode(
            $respuesta,
            true
        );


    if (
        !is_array($datos) ||
        empty($datos)
    ) {

        return null;

    }


    return [
        'lat' =>
            (float)$datos[0]['lat'],

        'lon' =>
            (float)$datos[0]['lon']
    ];

}




$municipio =
    trim(
        $_GET['municipio'] ?? ''
    );


$departamento =
    trim(
        $_GET['departamento'] ?? ''
    );


$direccion =
    trim(
        $_GET['direccion'] ?? ''
    );


if (
    $municipio === '' &&
    $departamento === '' &&
    $direccion === ''
) {

    responder([
        'ok' => false,
        'mensaje' =>
            'No se recibió una dirección.'
    ]);

}




$origen = [
    'lat' => 11.8496,
    'lon' => -86.1990
];




try {

    $origenBuscado =
        geocodificar(
            'Universidad Central de Nicaragua Jinotepe, Carazo, Nicaragua'
        );


    if ($origenBuscado) {

        $origen =
            $origenBuscado;

    }

} catch (Exception $e) {


}




$consultaDestino =
    implode(
        ', ',
        array_filter([
            $direccion,
            $municipio,
            $departamento,
            'Nicaragua'
        ])
    );


$destino = null;


try {

    $destino =
        geocodificar(
            $consultaDestino
        );




    if (!$destino) {

        $consultaAlternativa =
            implode(
                ', ',
                array_filter([
                    $municipio,
                    $departamento,
                    'Nicaragua'
                ])
            );


        $destino =
            geocodificar(
                $consultaAlternativa
            );

    }

} catch (Exception $e) {

    responder([
        'ok' => false,
        'mensaje' =>
            'No se pudo geocodificar el destino: ' .
            $e->getMessage()
    ]);

}


if (!$destino) {

    responder([
        'ok' => false,
        'mensaje' =>
            'No se encontró la ubicación del destino.'
    ]);

}




$urlOSRM =
    'https://router.project-osrm.org/route/v1/driving/' .

    $origen['lon'] .
    ',' .
    $origen['lat'] .

    ';' .

    $destino['lon'] .
    ',' .
    $destino['lat'] .

    '?' .

    http_build_query([
        'overview' => 'full',
        'geometries' => 'geojson',
        'steps' => 'true'
    ]);


try {

    $respuestaOSRM =
        consultarUrl(
            $urlOSRM
        );

} catch (Exception $e) {

    responder([
        'ok' => false,
        'mensaje' =>
            'No se pudo calcular la ruta: ' .
            $e->getMessage()
    ]);

}


$datosOSRM =
    json_decode(
        $respuestaOSRM,
        true
    );


if (
    !is_array($datosOSRM) ||
    ($datosOSRM['code'] ?? '') !== 'Ok' ||
    empty($datosOSRM['routes'][0])
) {

    responder([
        'ok' => false,
        'mensaje' =>
            'El servicio de rutas no pudo encontrar una ruta.'
    ]);

}


$ruta =
    $datosOSRM['routes'][0];




responder([

    'ok' => true,

    'origen' => [
        'lat' => $origen['lat'],
        'lon' => $origen['lon']
    ],

    'destino' => [
        'lat' => $destino['lat'],
        'lon' => $destino['lon']
    ],

    'distancia_metros' =>
        $ruta['distance'] ?? 0,

    'duracion_segundos' =>
        $ruta['duration'] ?? 0,

    'geometry' =>
        $ruta['geometry'] ?? null

]);