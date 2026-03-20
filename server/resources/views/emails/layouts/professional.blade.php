<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
            -ms-text-size-adjust: none;
            width: 100% !important;
        }
        .wrapper {
            width: 100%;
            background-color: #f3f4f6;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }
        .header {
            background-color: #bc01f5;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 40px 30px;
            line-height: 1.6;
        }
        .content h2 {
            color: #111827;
            font-size: 20px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .content p {
            margin-top: 0;
            margin-bottom: 16px;
            color: #4b5563;
        }
        .btn-container {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            background-color: #bc01f5;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(188, 1, 245, 0.2), 0 2px 4px -1px rgba(188, 1, 245, 0.1);
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #9e01ce;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            background-color: #f9fafb;
            border-radius: 8px;
            overflow: hidden;
        }
        .details-table th, .details-table td {
            padding: 12px 16px;
            text-align: left;
            font-size: 14px;
        }
        .details-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 600;
            width: 35%;
        }
        .details-table td {
            color: #4b5563;
            border-bottom: 1px solid #f3f4f6;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .footer {
            text-align: center;
            padding: 30px;
            font-size: 13px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            background-color: #fafbfc;
        }
        .footer p {
            margin: 0 0 8px 0;
        }
        .footer a {
            color: #bc01f5;
            text-decoration: none;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            background-color: #f3e8ff;
            color: #8b5cf6;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>EDU-SEN</h1>
            </div>
            <div class="content">
                @yield('content')
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} EDU-SEN. Tous droits réservés.</p>
                <p>Ce mail a été envoyé automatiquement, merci de ne pas y répondre.</p>
            </div>
        </div>
    </div>
</body>
</html>
