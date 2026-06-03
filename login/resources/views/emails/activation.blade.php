<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activa tu cuenta</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 520px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
        }
        .header {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo-text {
            font-size: 22px;
            font-weight: 800;
            color: #4f46e5;
            letter-spacing: -0.5px;
        }
        h2 {
            color: #0f172a;
            font-size: 22px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 16px;
            text-align: center;
        }
        p {
            font-size: 15px;
            line-height: 1.6;
            margin-top: 0;
            margin-bottom: 20px;
            color: #475569;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn-primary {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 30px;
            font-size: 15px;
            font-weight: 700;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2), 0 2px 4px -1px rgba(79, 70, 229, 0.1);
            transition: all 0.2s ease;
        }
        .warning-box {
            background-color: #fffbeb;
            border: 1px solid #fef3c7;
            border-radius: 12px;
            padding: 16px;
            margin: 24px 0;
        }
        .warning-text {
            font-size: 13px;
            color: #b45309;
            margin: 0;
            font-weight: 500;
            line-height: 1.5;
        }
        .footer {
            margin-top: 32px;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="logo-text">Sistema de Seguridad</span>
        </div>
        <h2>¡Hola, {{ $user->name }}!</h2>
        <p>Gracias por registrarte. Para completar la creación de tu cuenta y verificar tu dirección de correo electrónico, por favor haz clic en el siguiente botón:</p>
        
        <div class="btn-container">
            <a href="{{ $activationUrl }}" class="btn-primary">Activar cuenta</a>
        </div>

        <div class="warning-box">
            <p class="warning-text">⚠️ <strong>Importante:</strong> Este enlace de activación expirará en <strong>5 minutos</strong>. Si expira, deberás registrarte de nuevo para solicitar otro enlace.</p>
        </div>

        <p style="font-size: 13px; color: #64748b; margin-top: 24px;">Si tienes problemas para hacer clic en el botón "Activar cuenta", copia y pega la siguiente URL en tu navegador web:</p>
        <p style="font-size: 12px; word-break: break-all; color: #4f46e5; background-color: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #f1f5f9;">{{ $activationUrl }}</p>

        <div class="footer">
            Este correo fue enviado de forma automática para completar tu registro. Si no creaste una cuenta, puedes ignorar este mensaje de forma segura.
        </div>
    </div>
</body>
</html>
