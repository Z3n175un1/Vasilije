@include('errors._shell', [
    'code' => 503,
    'title' => 'En mantenimiento',
    'message' => 'Estamos realizando tareas de mantenimiento para mejorar el servicio. Volveremos en breve.',
    'scene' => '503',
    'sceneLabel' => 'Camión en un elevador de taller con conos de seguridad',
    'action' => ['url' => url()->full(), 'label' => 'Reintentar', 'icon' => 'fa-rotate-right'],
])
