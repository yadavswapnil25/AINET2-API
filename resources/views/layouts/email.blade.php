<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="utf-8"> <!-- utf-8 works for most cases -->
    <meta name="viewport" content="width=device-width" initial-scale=1.0, maximum-scale=1>
    <!-- Forcing initial-scale shouldn't be necessary -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- Use the latest (edge) version of IE rendering engine -->

    <!-- Including font awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" />

    <title>@yield('title')</title>

    <style type="text/css">
        * {
            /* Stops clients resizing small text. */
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
            -webkit-font-smoothing: antialiased;
        }

        body {
            -webkit-text-size-adjust: none;
            font-family: Avenir, sans-serif;
            height: 100%;
            width: 100%;
            overflow: auto;
            background-color: #F4F4F4;
        }

        div[style*="margin: 16px 0"] {
            /* Centers email on Android 4.4 */
            margin: 0 !important;
        }

        img {
            /* Uses a better rendering method when resizing images in IE. */
            -ms-interpolation-mode: bicubic;
        }

        a {
            text-decoration: none;
        }

        a[x-apple-data-detectors] {
            /* Another work-around for iOS meddling in triggered links. */
            color: inherit !important;
        }

        p, span {
            color: #6F767E;
            margin: unset;
        }

        p {
            margin-bottom: 18px;
        }

        h1 {
            color: #1A1D1F;
        }

        h2,
        h3,
        h4,
        h5 {
            color: #1A1D1F;
            margin: unset;
        }

        h3 {
            font-weight: lighter;
        }

        .content {
            max-width: 700px;
            margin: auto;
        }

        .content__header {
            padding-top: 50px;
            padding-bottom: 50px;
            text-align: center;
        }

        .content__header img {
            max-width: 30%;
            max-height: 70px;
        }

        .content__body {
            padding: 50px;
            background-color: #fcfcfc;
            border-radius: 12px;
        }

        .content__footer {
            display: flex;
            font-size: 14px;
            padding-top: 50px;
            padding-bottom: 50px;
            text-align: center;
            align-content: center;
            justify-content: center;
            padding-left: 5px;
            padding-right: 5px;
        }

        .content__footer-1 {
            text-align: center;
            justify-content: center;
            width: 100% !important;
            display: block !important;
        }

        .content__footer-1 img {
            max-height: 50px;
            max-width: 80px;
            margin-bottom: 10px;
        }

        .content__footer-2 {
            text-align: center;
            width: 100% !important;
            display: block !important;
        }

        .member__team {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            margin-top: auto;
            margin-bottom: auto;
            padding-top: 10px;
        }

        .footer__contact {
            display: inline-block;
            justify-content: center;
            font-size: 12px;
            text-transform: uppercase;
            text-align: center;
            align-self: center;
            align-content: center;
            margin: auto
        }

        .footer__contact strong {
            display: none;
        }

        .footer__info {
            display: block;
            margin: 5px;
        }

        .info {
            display: inline-block;
            margin: 12px;
        }

        .text__bold {
            color: #1A1D1F;
            font-weight: bold;
        }

        .text__center {
            text-align: center;
        }

        .text__right {
            text-align: right;
        }

        .text__white {
            color: #f4f4f4 !important
        }

        /* What it does: Overrides styles added when Yahoo's auto-senses a link. */
        .yshortcuts a {
            border-bottom: none !important;
        }

        /* What it does: Another work-around for iOS meddling in triggered links. */
        a[x-apple-data-detectors] {
            color: inherit !important;
        }

        /*    Media Queries*/
        @media (max-width: 767px) {

            .content,
            .content__body {
                padding: 25px;
            }
        }

        /* Media Queries */
        @media (min-width: 576px) {

            .footer__contact {
                display: inline-block !important;
                font-size: 14px;
            }

            .footer__contact strong {
                display: inline-block !important;
            }

            .info {
                flex-wrap: wrap;
            }

            .member {
                display: flex;
                justify-content: space-between;
                flex-wrap: nowrap;
            }

            .member__team {
                font-size: 25px;
                font-weight: 700;
                text-align: right !important;
            }
        }

        /* What it does: Forces Outlook.com to display emails full width. */
        .ExternalClass {
            width: 100%;
        }

        /* What is does: Centers email on Android 4.4 */
        div[style*="margin: 16px 0"] {
            margin: 0 !important;
        }

        /* What it does: Uses a better rendering method when resizing images in IE. */
        img {
            -ms-interpolation-mode: bicubic;
        }

        /* What it does: Overrides styles added when Yahoo's auto-senses a link. */
        .yshortcuts a {
            border-bottom: none !important;
        }

        .my-1 {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .px-10 {
            padding-left: 10px;
            padding-right: 10px;
        }

        .f-14 {
            font-size: 14px;
        }

        .table, .table > .thead, .table > tbody > tr > td, .table > thead > tr > th {
            border: 1px solid #666;
            border-collapse: collapse;
        }

        .table > tbody > tr > td, .table > thead > tr > th {
            padding: 5px;
        }

        .dark {
            color: #000;
        }
        .gray__color {
            color: #6F767E;
        }
    </style>

    @yield('email_css')

    <style type="text/css">
        u+#body a {
            color: inherit;
            text-decoration: none;
            font-size: inherit;
            font-family: inherit;
            font-weight: inherit;
            line-height: inherit;
        }
    </style>
</head>

<body id="body" style="font-family: Avenir, sans-serif; background-color: #F4F4F4; line-height: 24px">

    <!-- Email Content : BEGIN -->
    <div class='content' style=" max-width: 700px; margin: auto;">
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" role="presentation">
            <tr>
                <td class="content__header">
                        @isset($mailHelper->logo)
                            <img src="{{ $mailHelper->logo }}" border="0" />
                        @else
                            <h3>{{ config('app.name') }}</h3>
                        @endisset
                    
                </td>
            </tr>
        </table>
        <!-- Email Header : END -->

        <!-- Email Body : BEGIN -->
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" role="presentation">
            <tr>
                <td class="content__body" width="100%"
                    style="font-family: Avenir, sans-serif; background-color: #fcfcfc">
                    @yield('body')
                </td>
            </tr>
        </table>
        <!-- Email Body : END -->

        <!-- Email Footer : BEGIN -->
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" class="content__footer"
            role="presentation">
            <tr width="100%" style="font-family: Avenir, sans-serif;">
                <td class="content__footer-1" align="center" style="font-family: Avenir, sans-serif;">
                  
                    {!! $shortDesc ?? null !!}
                </td>
                <td class="content__footer-2" align="center">
                    <div class="footer__contact">
                        @isset($mailHelper->footerTitles)
                            @foreach ($mailHelper->footerTitles as $footerTitle)
                                <a href="{{ $footerTitle['link'] }}" class="text__primary" target="_blank"
                                    style="font-weight: bold; margin: 12px; font-family: Avenir, sans-serif;">{{ $footerTitle['text'] }}</a>
                                @if (!$loop->last)
                                    <strong style="padding: 12px; color:#6F767E">&bull;</strong>
                                @endif
                            @endforeach
                        @endisset
                    </div>
                    <br />
                    <div class="info">
                        @isset($socialMedia)
                            @foreach ($socialMedia as $media)
                                <a href="{{ $media['link'] }}" class="media" title="{{ $media['label'] }}" target="_blank"
                                    style="padding: 12px; text-align:center; align-content:center">
                                    <img src="{{ asset('images/icons/' . $media['icon']) }}" width="20px" height="20px">
                                </a>
                            @endforeach
                        @endisset
                    </div>

                    <div class="footer__info"
                        style="text-align: center; display: block; margin: 5px; font-family: Avenir, sans-serif;">
                        <span>{!! $mailHelper->copyrightText ?? null !!}</span>
                        <p>For any query, please contact us on <a href="mailto:theainet@gmail.com">theainet@gmail.com</a></p>

                    </div>

                </td>
            </tr>
        </table>
        <!-- Email Footer : END -->
    </div>

    <!-- Email Content : END -->
</body>

</html>
