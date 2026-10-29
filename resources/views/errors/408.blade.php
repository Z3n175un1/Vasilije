@include('errors._shell', [
    'code' => 408,
    'title' => 'Tiempo de espera agotado',
    'message' => 'La solicitud tardó demasiado en llegar. Puede ser una conexión lenta; intenta de nuevo.',
    'scene' => '408',
    'sceneLabel' => 'Cronómetro esperando',
    'action' => ['url' => url()->full(), 'label' => 'Reintentar', 'icon' => 'fa-rotate-right'],
])
