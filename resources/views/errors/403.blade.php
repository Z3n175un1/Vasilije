@include('errors._shell', [
    'code' => 403,
    'title' => 'Acceso denegado',
    'message' => 'Tu cuenta no tiene permiso para ver este recurso. Si crees que es un error, comunícate con el administrador.',
    'scene' => '403',
    'sceneLabel' => 'Barrera de acceso restringido frente a un camión',
    'action' => ['url' => url('/documentos'), 'label' => 'Volver al inicio', 'icon' => 'fa-arrow-left'],
])
