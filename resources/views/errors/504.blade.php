@include('errors._shell', [
    'code' => 504,
    'title' => 'Tiempo de respuesta agotado',
    'message' => 'Otro servidor tardó demasiado en responder. Es algo temporal; intenta nuevamente en un momento.',
    'scene' => '504',
    'sceneLabel' => 'Reloj de arena y un camión esperando',
    'action' => ['url' => url()->full(), 'label' => 'Reintentar', 'icon' => 'fa-rotate-right'],
])
