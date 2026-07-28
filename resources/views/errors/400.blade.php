<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>400 - VASILIJE</title>
    <link href="https://fonts.googleapis.com/css2?family=Uncut+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Uncut Sans', sans-serif; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .error-card { background: #fff; border: 6px solid #000; border-radius: 0; padding: 60px 40px; max-width: 520px; width: 100%; text-align: center; box-shadow: 12px 12px 0 #000; }
        .error-code { font-size: 120px; font-weight: 800; line-height: 1; color: #000; letter-spacing: -4px; margin-bottom: 0; }
        .error-divider { width: 60px; height: 6px; background: #000; margin: 16px auto; }
        .error-title { font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 12px; }
        .error-message { font-size: 14px; font-weight: 500; color: #555; margin-bottom: 30px; }
        .btn-error { display: inline-block; padding: 12px 32px; background: #fff; color: #000; border: 4px solid #000; font-weight: 800; text-decoration: none; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; transition: .15s; border-radius: 0; }
        .btn-error:hover { background: #000; color: #fff; }
        .brand { font-size: 11px; font-weight: 800; letter-spacing: 3px; color: #999; margin-top: 24px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">400</div>
        <div class="error-divider"></div>
        <div class="error-title">Solicitud Incorrecta</div>
        <div class="error-message">El servidor no puede procesar la solicitud debido a un error del cliente.</div>
        <a href="{{ url('/documentos') }}" class="btn-error">Volver al Inicio</a>
        <div class="brand">VASILIJE</div>
    </div>
</body>
</html>
