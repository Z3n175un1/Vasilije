@include('errors._shell', [
    'code' => 502,
    'title' => 'Respuesta inválida',
    'message' => 'Dos de nuestros servidores no se entendieron. Suele ser temporal; intenta de nuevo más tarde.',
    'scene' => '502',
    'sceneLabel' => 'Dos servidores conectados por un enlace roto',
    'action' => ['url' => url()->full(), 'label' => 'Reintentar', 'icon' => 'fa-rotate-right'],
])
