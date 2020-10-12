<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Forgot Password</title>

        <!-- Fonts -->
        <link href='https://fonts.googleapis.com/css?family=Poppins' rel='stylesheet'>

        <!-- Styles -->
        <style>
            /* What it does: Remove spaces around the email design added by some email clients. */
            /* Beware: It can remove the padding / margin and add a background color to the compose a reply window. */
            html,
            body {
                margin: 0 auto !important;
                padding: 0 !important;
                height: 100% !important;
                width: 100% !important;
                background: #f1f1f1;
            }

            /* What it does: Stops email clients resizing small text. */
            * {
                -ms-text-size-adjust: 100%;
                -webkit-text-size-adjust: 100%;
            }

            /* What it does: Centers email on Android 4.4 */
            div[style*="margin: 16px 0"] {
                margin: 0 !important;
            }

            /* What it does: Stops Outlook from adding extra spacing to tables. */
            table,
            td {
                mso-table-lspace: 0pt !important;
                mso-table-rspace: 0pt !important;
            }

            /* What it does: Fixes webkit padding issue. */
            table {
                border-spacing: 0 !important;
                border-collapse: collapse !important;
                table-layout: fixed !important;
                margin: 0 auto !important;
            }

            /* What it does: Uses a better rendering method when resizing images in IE. */
            img {
                -ms-interpolation-mode: bicubic;
            }

            /* What it does: Prevents Windows 10 Mail from underlining links despite inline CSS. Styles for underlined links should be inline. */
            a {
                text-decoration: none;
            }

            /* What it does: A work-around for email clients meddling in triggered links. */
            *[x-apple-data-detectors],
            /* iOS */
            .unstyle-auto-detected-links *,
            .aBn {
                border-bottom: 0 !important;
                cursor: default !important;
                color: inherit !important;
                text-decoration: none !important;
                font-size: inherit !important;
                font-family: inherit !important;
                font-weight: inherit !important;
                line-height: inherit !important;
            }

            /* What it does: Prevents Gmail from displaying a download button on large, non-linked images. */
            .a6S {
                display: none !important;
                opacity: 0.01 !important;
            }

            /* What it does: Prevents Gmail from changing the text color in conversation threads. */
            .im {
                color: inherit !important;
            }

            /* If the above doesn't work, add a .g-img class to any image in question. */
            img.g-img+div {
                display: none !important;
            }

            /* What it does: Removes right gutter in Gmail iOS app: https://github.com/TedGoas/Cerberus/issues/89  */
            /* Create one of these media queries for each additional viewport size you'd like to fix */

            /* iPhone 4, 4S, 5, 5S, 5C, and 5SE */
            @media only screen and (min-device-width: 320px) and (max-device-width: 374px) {
                u~div .email-container {
                    min-width: 320px !important;
                }
            }

            /* iPhone 6, 6S, 7, 8, and X */
            @media only screen and (min-device-width: 375px) and (max-device-width: 413px) {
                u~div .email-container {
                    min-width: 375px !important;
                }
            }

            /* iPhone 6+, 7+, and 8+ */
            @media only screen and (min-device-width: 414px) {
                u~div .email-container {
                    min-width: 414px !important;
                }
            }

            .primary {
                color: #EDA54B !important;
                font-weight: 500 !important;
            }

            .light {
                color: #fff !important;
                font-weight: 500 !important;
            }

            .bg_white {
                background: #ffffff !important;
            }

            .bg_light {
                background: #F8F8F8;
            }

            .bg_primary {
                background: #EDA54B;
            }

            h1,
            h2,
            h3,
            h4,
            h5,
            h6 {
                font-family: 'Poppins';
                color: #000000 !important;
                margin-top: 0;
                font-weight: 400;
            }

            body {
                font-family: 'Poppins';
                font-weight: 400;
                font-size: 15px;
                line-height: 1.8;
                color: rgba(0, 0, 0, .4);
            }

            a {
                color: #30e3ca;
            }
        </style>
    </head>
    <body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #f1f1f1;">
        <center style="width: 100%; background-color: #f1f1f1;">
            <div style="max-width: 600px; margin: 0 auto; border: 1px solid #D9D9D9;" class="email-container">
                <!-- BEGIN BODY -->
                <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                    style="margin: auto;">
                    <tr>
                        <td valign="top" class="bg_white" style="padding: 1em 2.5em 0 2.5em;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td class="logo" style="text-align: center;">
                                        <!-- <h1><a href="#">e-Verify</a></h1> -->
                                        <img src="http://diamondtrader.co/assets/img/logo.png" width="128" style="margin-bottom: 20px">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr><!-- end tr -->
                    <tr>
                        <td valign="middle" class="hero bg_primary" style="padding: 1em 0 0 0; text-align: center">
                            <h1 class="light">Esse é o seu código</h1>
                        </td>
                    </tr>
                    <tr>
                        <td valign="middle" class="hero bg_white" style="padding: 3em 0 2em 0;">
                            <div class="text" style="text-align: center;">
                                <h1 class="primary">Olá, {{ $user->name }}!</h1>

                                <p style="font-size: 18px;">
                                    Utilize o código abaixo para redefinir sua senha:
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="middle" class="hero bg_light" style="padding: 2em 0 1em 0;">
                            <div class="text" style="text-align: center;">
                                <h1 class="primary">{{ $user->code }}</h1>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="middle" class="hero bg_white" style="padding: 2em 0 4em 0;">
                            <table>
                                <tr>
                                    <td>
                                        <div class="text" style="padding: 0 2.5em; text-align: center;">

                                            <p style="font-size: 16px;">
                                                Caso essa solicitação não tenha sido feita por você, ou se
                                                <br>você acredita que outra pessoa tenha acessado sua conta,
                                                <br>por favor, entre em contato conosco imediatamente.
                                            </p>

                                            <p style="font-size: 16px; margin-top:50px">
                                                Abraços,<br>
                                                Equipe Diamond Trading
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr><!-- end tr -->
                    <!-- 1 Column Text + Button : END -->
                </table>

                <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                    style="margin: auto;">
                    <tr>
                        <td class="bg_light" style="text-align: center;">
                            Solicitado em: <b style="margin-left: 5px">{{ date('d M Y - H:i') }}</b>

                            @if($location)

                                <br>Local: <b style="margin-left: 5px">{{ $location->cityName }} - {{ $location->regionName }} - {{ $location->countryName }}</b> <br>
                                IP: <b style="margin-left: 5px">{{ $location->ip }}</b>

                            @endif

                        </td>
                    </tr>
                </table>
            </div>
        </center>
    </body>
</html>
