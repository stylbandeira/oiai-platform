<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Redefinição de Senha</title>
</head>

<body style="background: #f8f9fa;">
    <div style="text-align: center; margin-bottom: 2em;">
        <a href="{{ config('app.url') }}" rel="noopener noreferrer">
            <img src="{{ asset('storage/img/primary-logo.png') }}" alt="Logo"
                style="object-fit: contain; height: 8rem; display: inline-block;" />
        </a>
    </div>

    <div style="padding: 2em; margin: 1em auto; border-radius: 1em; max-width: 600px;">
        <h1 style="margin-bottom: 1em;">Olá, {{ $user->name ?? 'Usuário' }}</h1>
        <p style="margin-bottom: 1.5em;">Clique no botão abaixo para redefinir sua senha:</p>

        <a href="{{ $url }}"
            style="padding: 10px 20px; background-color: #3490dc; color: white; text-decoration: none; border-radius: 6px; display: inline-block;">
            Redefinir Senha
        </a>

        <hr style="margin: 2em 0;" />

        <p style="text-align: center; color: #6c757d;">
            Se você não solicitou essa ação, apenas ignore este e-mail.
        </p>
    </div>

</body>

</html>
