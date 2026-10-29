@include('errors._shell', [
    'code' => 401,
    'title' => 'No autorizado',
    'message' => 'Esta zona es solo para personal con sesión iniciada. Ingresa tus credenciales para continuar.',
    'scene' => '401',
    'sceneLabel' => 'Portón cerrado con candado',
    'action' => ['url' => route('login'), 'label' => 'Iniciar sesión', 'icon' => 'fa-right-to-bracket'],
])
