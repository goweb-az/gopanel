<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoxlama</title>
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f8f9fa; font-family: sans-serif; }
        .loader { text-align: center; color: #6c757d; }
        .spinner { width: 40px; height: 40px; border: 4px solid #e9ecef; border-top-color: #495057; border-radius: 50%; animation: spin .8s linear infinite; margin: 0 auto 12px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="loader">
        <div class="spinner"></div>
        <p>Yüklənir…</p>
    </div>
    <script>
        document.cookie = '__hs=1; path=/; max-age=' + (60*60*24*365) + '; SameSite=Lax';
        location.reload();
    </script>
</body>
</html>
