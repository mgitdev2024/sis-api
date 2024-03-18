<!DOCTYPE html>
<html>

<head>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

        a {
            color: white !important;
        }

        #temp-url {
            font-style: italic !important;
            color: #0000FF !important;
        }

        body,
        h1,
        p,
        td,
        th {
            font-family: Poppins, sans-serif
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #444;
            margin: 0 auto
        }

        .border-top {
            border-top: 5px solid #8c0000
        }

        .bg-light {
            background: #fcf9f6
        }

        .bg-primary {
            background: #8c0000
        }

        .container-md {
            margin-left: 2rem;
            margin-right: 2rem
        }

        .container-lg {
            margin-left: 6rem;
            margin-right: 6rem
        }

        .p-1 {
            padding: .25rem
        }

        .p-2 {
            padding: .5rem
        }

        .p-3 {
            padding: .75rem
        }

        .p-4 {
            padding: 1.5rem
        }

        .p-5 {
            padding: 2.2rem
        }

        .pt-1 {
            padding-top: .25rem
        }

        .pt-2 {
            padding-top: .5rem
        }

        .pt-3 {
            padding-top: .75rem
        }

        .pt-4 {
            padding-top: 1.5rem
        }

        .pt-5 {
            padding-top: 2.2rem
        }

        .m-0 {
            margin: 0
        }

        .m-1 {
            margin: .25rem
        }

        .m-2 {
            margin: .5rem
        }

        .m-3 {
            margin: .75rem
        }

        .m-4 {
            margin: 1rem
        }

        .m-5 {
            margin: 1.25rem
        }

        .font-xl {
            font-size: 2.5rem
        }

        .font-lg {
            font-size: 2rem
        }

        .font-md,
        th {
            font-size: 1.5rem
        }

        .font-sm {
            font-size: 1rem
        }

        .bold-text,
        .font-bold {
            font-weight: 700
        }

        .font-normal {
            font-weight: 400
        }

        .text-center {
            text-align: center
        }

        .text-left {
            text-align: left
        }

        .text-right {
            text-align: right
        }

        .text-white {
            color: #fcf9f6
        }

        .text-decoration-none {
            text-decoration: none
        }

        .text-primary {
            color: #8c0000
        }

        .border-round {
            border-radius: 10px
        }

        #create-password {
            background: #7e1717;
            transition: .3s
        }

        #create-password:hover {
            background: #b44c4c;
            transition: .3s
        }

        h1 {
            font-size: 2.5em;
            padding: 0;
            margin-bottom: 1%
        }

        .links {
            color: white !important;
        }

        @media screen and (max-width:680px) {
            h1 {
                font-size: 2em
            }

            #footer-text {
                font-size: .9em
            }

            #footer-term {
                display: none
            }

            .container-lg {
                margin-left: 1rem;
                margin-right: 1rem
            }

            #create-password {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
                font-size: .95rem;
                font-size: bold
            }

            td {
                padding: 0 5px 0 16px
            }

            th {
                font-size: 1rem;
                margin-bottom: 20%
            }

            #contact-it {
                padding-bottom: 1.5rem;
                padding-top: 1.5rem
            }

            .container-md {
                margin: .5rem
            }
        }

        @media screen and (max-width:330px) {
            #create-password {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
                font-size: .8rem;
                font-size: bold
            }
        }

        @media screen and (max-width:315px) {
            #create-password {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
                font-size: .7rem;
                font-size: bold
            }
        }

        @media screen and (max-width:266px) {
            #create-password {
                font-size: 50%
            }
        }
    </style>
</head>

<body class="bg-light">
    <table cellpadding="20" cellspacing="0">
        <tr>
            <th class="p-4">
                <img src="{{ $message->embed(public_path('img/logo.png')) }}" alt="Mary Grace Logo" height="130">
            </th>
        </tr>

        <tr>
            <td class="border-top">
                <h1 class="text-center">Create Password</h1>
            </td>
        </tr>

        <tr class='m-0'>
            <td class="font-sm m-0">
                <p class="container-md">
                    Hello <span class="font-bold">{{ $full_name }}</span>! We hope this message
                    finds
                    you well. To
                    enhance the security of your account and ensure a seamless experience, we kindly recommend that you
                    create a new password to access the system. To proceed, please follow the instructions below:
                </p>
            </td>
        </tr>
        <tr>
            <th>
                <p class="font-sm text-left container-lg">
                    1. Click the Create Password Button
                </p>
            </th>
        </tr>
        <tr>
            <td>
                <p class="font-sm font-bold text-left container-lg">
                    2. Set your New Password
                    <br>
                    <span class="font-normal">
                        You'll be taken to a secure page where you can set a new password for your account. Make sure
                        it's unique and strong.
                    </span>
                </p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="font-sm font-bold text-left container-lg">
                    3. Log In securely
                    <br>
                    <span class="font-normal">
                        Once your password is created, you can securely log in to your account with your new
                        credentials.
                    </span>
                </p>
            </td>
        </tr>
        <tr>
            <td class="text-center p-4">
                <a id="create-password" href="{{ $temporary_url }}"
                    class="border-round font-md text-decoration-none text-white p-3 bold-text links"
                    style="padding-left: 2rem; padding-right: 2rem; color:white;">
                    CREATE YOUR PASSWORD
                </a>
            </td>
        </tr>
        <tr class="">
            <p class="font-sm font-bold text-left container-lg">
                Click on the link, or alternatively, copy and paste it into your browser's address bar
            </p>
            <a id="temp-url" class="font-sm text-left container-lg" href="{{ $temporary_url }}">
                {{ $temporary_url }}
            </a>
        </tr>

        <tr class="">
            <th class="" style="padding-bottom:0">
                This link is valid for one use only. Expires when the password has been changed.
            </th>
        </tr>
        <tr class="">
            <td class="font-sm text-center" id="contact-it">
                <p>
                    If you didn't request your password, please disregard this message or contact our IT Development
                    Support on Viber at <span class="text-primary font-bold">0945 128 8042.</span>
                </p>
            </td>
        </tr>
        <tr>
            <table class="bg-primary text-center" cellpadding="0" cellspacing="0" style="height: 100px; padding: 20px">
                <tr>
                    <td class="spacer" height="20">&nbsp;</td>
                </tr>
                <tr>
                    <td class="text-right" width="50%" style="padding-right:20px;">
                        <a href="#" class="text-decoration-none">
                            <img src="{{ $message->embed(public_path('img/facebook-logo.png')) }}" alt="Facebook Logo"
                                style ="height: 2rem">
                        </a>
                    </td>
                    <td class="text-left" width="50%" style="padding-left:20px;">
                        <a href="#" class="text-decoration-none">
                            <img src="{{ $message->embed(public_path('img/instagram-logo.png')) }}" alt="Instagram Logo"
                                style ="height: 3rem">
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan=2>
                        <p class="text-white" id="footer-text">
                            &copy; 2023 All Rights Reserved. Mary Grace Foods Inc.
                            <br>
                            <span>
                                Block 2, Marian Industrial Park, Lot 25 and 27 Marian Road -2, Parañaque, 1713 Metro
                                Manila
                            </span>
                        </p>
                    </td>
                </tr>
                <tr id="visit-us-term">
                    <td style="color: white;">
                        <a href="#" class="text-white links">Visit Us</a>
                    </td>
                    <td style="color: white; border-left: solid;">
                        <a href="#" class="text-white links">Terms and Conditions</a>
                    </td>
                </tr>
                <tr>
                    <td class="spacer" height="20">&nbsp;</td>
                </tr>
            </table>
        </tr>
    </table>
</body>

</html>
