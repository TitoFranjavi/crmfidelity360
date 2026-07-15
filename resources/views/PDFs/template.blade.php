<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLANTILLA DE CORREO</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;500;700&display=swap"
          rel="stylesheet">

    <style type="text/css">
        * {
            padding: 0;
            margin: 0;
            font-family: 'IBM Plex Sans Arabic', Sans-Serif;
        }

        table {
            border-spacing: 0;
        }

        .parent-body {
            width: 100%;
            background: #f6f6f6;
            padding: 30px 0;
            box-sizing: border-box;
        }

        a {
            color: black;
        }

        /* LOGOTIPO */
        .logo {
            width: 100%;
            text-align: center;
            padding: 20px 0 20px 0;
        }

        .logo img {
            height: 40px;
        }

        /* CONTENIDO */

        .btn {
            background: #BEAEE2;
            color: white;
            border-radius: 30px;
            padding: 10px 40px;
        }

        a.btn {
            text-decoration: none;
        }

        .button-link {
            margin: 30px auto;
        }

        .bg-gradient-purple {
            background: linear-gradient(to right, #FFABE1, #A685E2);
        }

        .bg-white {
            background: white;
        }

        .bg-gradient-green {
            background: linear-gradient(to bottom, #ddf5db, #b9eed6);
            color: #1b3228;
        }

        .bg-gradient-yellow {
            background: linear-gradient(to bottom, #FDE49C, #FFB740);
            color: #66470f;
        }

        .bg-gradient-gold {
            background: linear-gradient(to bottom, #FFF8CD, #FFEFA0);
            color: #3d351b;
        }

        .bg-gradient-purple {
            background: linear-gradient(to bottom, #F0D9FF, #BFA2DB);
            color: #413150;
        }

        .bg-gradient-blue {
            background: linear-gradient(to bottom, #BBF1FA, #51C2D5);
            color: #1c4f5a;
        }

        .empty-column {
            width: 20%;
        }

        /* ENCABEZADO */

        .header {
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;

            box-sizing: border-box;
            text-align: center;

        }

        .header img {
            max-height: 266px;

            margin-bottom: 30px;
        }

        .header-title {
            width: 70%;
            margin: auto;
        }

        .header-title h2 {
            margin-bottom: 20px;
            line-height: 1.8em;
        }

        .header-title h3 {
            font-weight: normal;
            font-size: 1em;
        }

        /* CONTENIDO */
        .content {
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .content .content-header {
            text-align: center;
            font-size: 1.5em;
            margin-bottom: 30px;

        }

        .content .content-description {
            text-align: justify;
            font-weight: 100;
            text-indent: 50px;

            margin-bottom: 20px;
        }

        .code {
            background: #F8EDE3;

            text-align: center;
            margin: 20px auto;
            padding: 10px;
            border: solid 2px rgba(0, 0, 0, 0.2);

            border-radius: 5px;

            color: rgba(0, 0, 0, 0.5);


        }

        .header, .content {
            padding: 50px;
        }

        .paragraph-sm, .paragraph-sm a {
            font-size: 0.8em;
            color: rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        /* FOOTER */

        .footer {
            margin-top: 20px;
        }

        .footer .content {
            border-radius: 20px;
        }

        .footer-text {
            font-size: 0.7em;
            text-align: center;
            color: rgba(0, 0, 0, 0.5);
        }

        .footer-contact {
            text-align: center;
            font-size: 0.6em;
            margin: 20px auto 0 auto;
            color: rgba(0, 0, 0, 0.5);

            width: 70%;
        }

        .footer-contact img {
            width: 10px;
            margin: auto 5px auto 30px;
            opacity: 0.5;
        }

        /* NOTIFICACIONES */

        .notification {
            padding: 20px 0;

            border-top: solid 1px rgba(0, 0, 0, 0.1);
            border-bottom: solid 1px rgba(0, 0, 0, 0.1);
        }

        .notification-title {
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 1em;
        }

        .notification-description {
            letter-spacing: 1px;
            margin-top: 5px;
            font-weight: lighter;
        }

        .notifications-resume {
            font-weight: lighter;
            font-size: 0.9em;
        }

        .notifications-resume tr td:last-child {
            text-align: center;
        }

        .table-title {
            border-bottom: solid 1px rgba(0, 0, 0, 0.1);
        }

        .user-name {
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 1.2em;
        }

        .notification-user {
            padding: 10px;
            border-radius: 20px;
            margin-bottom: 10px;
        }

        .notification-user:nth-child(2n) {
            background: #ffffe6;
        }

        .notifications-resume tr td {
            width: 25%;
        }

        @media screen and (max-width: 600px) {
            .empty-column {
                width: 0;
            }

            .footer-contact {
                width: 100%;
            }

            .date-column {
                display: none;
            }

            .notifications-resume tr td {
                width: 50%;
            }

            .header, .content {
                padding: 10px;
            }
        }

        @media screen and (min-width: 600px) and (max-width: 1500px) {
            .empty-column {
                width: 10%;
            }
        }
    </style>
</head>
<body>
<div class="parent-body">

    <!-- HEADER -->
    <div class="logo">
        Header
    </div>

    <!-- CONTENIDO -->
    contenido

    <!-- FOOTER -->
    footer
</div>
</body>
</html>
