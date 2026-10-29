@include('errors._shell', [
    'code' => 400,
    'title' => 'Solicitud incorrecta',
    'message' => 'No pudimos entender lo que enviaste. Revisa los datos del formulario e intenta de nuevo.',
    'scene' => '400',
    'sceneLabel' => 'Paquete con signo de interrogación y flechas en direcciones opuestas',
    'action' => ['url' => url('/documentos'), 'label' => 'Volver al inicio', 'icon' => 'fa-arrow-left'],
])
