<!DOCTYPE html>
<html>

<head>
    <title>Link de Download</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div
        style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="color: #333;">Olá, {{ $nome ?? 'Visitante' }}!</h2>
        <p style="color: #555; font-size: 16px;">
            Aqui está o link para baixar <strong>{{ $download->titulo }}</strong>, conforme solicitado.
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $link }}"
                style="background-color: #2563EB; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px; display: inline-block;">
                &darr; Baixar Arquivo
            </a>
        </div>

        <p style="color: #777; font-size: 14px;">
            Este link expira em <strong>24 horas</strong> por segurança.
        </p>

        <p style="color: #999; font-size: 14px; margin-top: 20px;">
            Se tiver dificuldades, copie o link abaixo:<br>
            <a href="{{ $link }}" style="color: #2563EB; word-break: break-all;">{{ $link }}</a>
        </p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

        <p style="color: #aaa; font-size: 12px; text-align: center;">
            Enviado automaticamente por AdasSoft.
        </p>
    </div>
</body>

</html>