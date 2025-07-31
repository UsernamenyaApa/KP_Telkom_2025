<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Expired</title>
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: sans-serif;
            font-weight: 100;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 36px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="flex-center position-ref full-height">
        <div class="content">
            <div class="title">
                Page Expired
            </div>
            <div>
                Maaf, sesi Anda telah berakhir. Silakan kembali dan coba lagi.
            </div>
            <div style="margin-top: 20px;">
                <a href="{{ url()->previous() }}">Kembali ke Halaman Sebelumnya</a>
            </div>
        </div>
    </div>
</body>
</html>