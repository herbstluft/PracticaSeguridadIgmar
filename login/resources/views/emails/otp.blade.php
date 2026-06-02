<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Código de acceso</title>
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
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }
        h2 {
            color: #0f172a;
            font-size: 20px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 24px;
        }
        p {
            font-size: 14px;
            line-height: 1.6;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .code-container {
            background-color: #f1f5f9;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            margin: 28px 0;
        }
        .otp-code {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 6px;
            color: #0f172a;
            font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
            margin: 0;
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
        <h2>Código de verificación de inicio de sesión</h2>
        <p>Hola,</p>
        <p>Para completar el inicio de sesión en tu cuenta, ingresa el siguiente código de verificación de un solo uso:</p>
        
        <div class="code-container">
            <p class="otp-code">{{ $otpCode }}</p>
        </div>

        <div class="footer">
            Este correo fue enviado de forma automática para proteger el acceso a tu cuenta. Si no solicitaste este código, puedes ignorar este mensaje.
        </div>
    </div>
</body>
</html>
