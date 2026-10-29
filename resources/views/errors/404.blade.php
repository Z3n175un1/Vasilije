@include('errors._shell', [
    'code' => 404,
    'title' => 'Página no encontrada',
    'message' => 'Este auto ya no llega a ningún destino: la página que buscas no existe o fue movida de lugar.',
    'scene' => '404',
    'sceneLabel' => 'Auto antiguo destartalado con humo y una señal caída',
    'action' => ['url' => url('/documentos'), 'label' => 'Volver al inicio', 'icon' => 'fa-arrow-left'],
])
