<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - VASILIJE</title>
    <link href="https://fonts.googleapis.com/css2?family=Uncut+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Uncut Sans', sans-serif; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .error-card { background: #fff; border: 6px solid #000; border-radius: 0; padding: 50px 40px; max-width: 520px; width: 100%; text-align: center; box-shadow: 12px 12px 0 #000; position: relative; overflow: hidden; }
        .error-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: repeating-linear-gradient(45deg, transparent, transparent 20px, rgba(0,0,0,.02) 20px, rgba(0,0,0,.02) 40px); animation: bgShift 8s linear infinite; pointer-events: none; }
        @keyframes bgShift { 0% { transform: translate(0,0); } 100% { transform: translate(40px,40px); } }
        .icon-wrap { font-size: 80px; margin-bottom: 10px; display: inline-block; }
        .icon-wrap i { animation: handSlap 1.8s ease-in-out infinite; display: inline-block; }
        @keyframes handSlap { 0%,100% { transform: rotate(0deg); } 20% { transform: rotate(-20deg) scale(1.1); } 40% { transform: rotate(10deg) scale(1.1); } 60% { transform: rotate(-5deg); } }
        .error-code { font-size: 100px; font-weight: 800; line-height: 1; color: #000; letter-spacing: -4px; margin-bottom: 0; position: relative; }
        .error-divider { width: 60px; height: 6px; background: #000; margin: 12px auto; }
        .error-title { font-size: 18px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px; }
        .error-message { font-size: 14px; font-weight: 500; color: #555; margin-bottom: 28px; }
        .btn-error { display: inline-block; padding: 12px 32px; background: #fff; color: #000; border: 4px solid #000; font-weight: 800; text-decoration: none; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; border-radius: 0; transition: .2s; }
        .btn-error:hover { background: #000; color: #fff; transform: scale(1.05); }
        .brand { font-size: 11px; font-weight: 800; letter-spacing: 3px; color: #999; margin-top: 20px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-wrap"><i class="fas fa-hand"></i></div>
        <div class="error-code">403</div>
        <div class="error-divider"></div>
        <div class="error-title">Acceso Denegado</div>
        <div class="error-message">No tenés permisos para estar acá. Mejor rajá de acá.<br><span style="font-size:12px;color:#999;">(Y no insistas)</span></div>
        <a href="{{ url('/documentos') }}" class="btn-error"><i class="fas fa-arrow-left me-2"></i>Volver al Inicio</a>
        <div class="brand">VASILIJE</div>
    </div>
</body>
</html>
