<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recuperar contraseña</title>
    <title>Segmentation process</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
</head>

<body style="
    font-family: Arial, Helvetica, sans-serif;
    background-color: #03b7df1f;
    box-sizing: border-box;
    padding: 32px;
    margin: 0;
">

    <div style="
        box-shadow: 0px 4px 16px #0000001f;
        background-color: #ffffff;
        box-sizing: border-box;
        border-radius: 10px;
        max-width: 600px;
        margin: 0 auto;
    ">

        <!-- Header -->
        <div style="
            border-radius: 10px 10px 0 0;
            background-color: #004358;
            padding: 16px;
        ">

            <img src="{{asset('public/storage/emails/LogoPrincipalBlanco.png')}}" style="
                width: 120px;
            ">

            <h1 style="
                text-align: center;
                margin: 40px 0 10px 0;
                color: #FFFFFF;
            ">
                Recuperar contraseña
            </h1>
        </div>

        <!-- Body -->
        <div style="
            text-align: center;
            padding: 32px;
            margin: 0 auto;
        ">
            <img src="{{asset('public/storage/emails/ResetPassword.png')}}" alt="ResetPassword" style="
                margin-bottom: 50px;
                width: 250px;
            ">

            <p style="
                margin-bottom: 50px;
                color: #333333;
            ">
                Haz clic en el siguiente enlace para cambiar tu contraseña, recuerda que solo puedes usarlo una vez.
            </p>

            <a href="{{$resetUrl}}" style="
                background-color: #004358;
                text-decoration: none;
                border-radius: 32px;
                padding: 16px 32px;
                color: #FFFFFF;
            ">
                Recuperar contraseña
            </a>

            <p style="
                margin-top: 80px;
                color: #707070;
                font-size: 14px;
            ">
                Has recibido este mensaje de parte de:
            </p>

            <img src="{{asset('public/storage/emails/LogoPrincipalNegro.png')}}" style="
                width: 150px;
            ">
        </div>

    </div>

</body>

</html>
