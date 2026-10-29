@include('errors._shell', [
    'code' => 500,
    'title' => 'Error interno del servidor',
    'message' => 'Algo se rompió de nuestro lado. El equipo técnico ya fue notificado; vuelve en unos minutos.',
    'scene' => '500',
    'sceneLabel' => 'Camión con humo saliendo del motor y una llave inglesa',
    'action' => ['url' => url('/documentos'), 'label' => 'Volver al inicio', 'icon' => 'fa-arrow-left'],
])
