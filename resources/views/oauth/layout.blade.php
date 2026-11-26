<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Autorizar conector IA') · IntalnetAcces</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --soft: #f8fafc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f1f5f9;
            color: var(--ink);
            font-family: Inter, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .shell { width: 100%; max-width: 520px; }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            padding-left: 4px;
        }
        .brand img { width: 40px; height: 40px; border-radius: 12px; border: 1px solid var(--line); object-fit: cover; }
        .brand strong { display: block; font-size: 15px; letter-spacing: -0.01em; }
        .brand span { display: block; font-size: 11px; color: var(--muted); font-weight: 600; }
        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 18px 40px -28px rgba(15, 23, 42, .35);
        }
        .eyebrow {
            margin: 0 0 6px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
        }
        h1 { margin: 0 0 10px; font-size: 21px; line-height: 1.25; letter-spacing: -0.02em; }
        .lead { margin: 0 0 20px; font-size: 13px; line-height: 1.6; color: #475569; font-weight: 500; }
        .rows { margin: 0 0 18px; padding: 14px 16px; background: var(--soft); border: 1px solid var(--line); border-radius: 14px; }
        .rows > div { display: flex; justify-content: space-between; gap: 16px; padding: 6px 0; }
        .rows > div + div { border-top: 1px dashed var(--line); }
        .rows dt { margin: 0; font-size: 11px; font-weight: 700; color: var(--muted); }
        .rows dd { margin: 0; font-size: 12px; font-weight: 700; text-align: right; word-break: break-word; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-weight: 600; }
        .perms { margin: 0 0 18px; padding: 0; list-style: none; }
        .perms li { position: relative; padding-left: 22px; font-size: 12.5px; line-height: 1.55; color: #334155; font-weight: 500; }
        .perms li + li { margin-top: 8px; }
        .perms li::before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 0;
            width: 15px;
            height: 15px;
            border-radius: 999px;
            background: #0f172a;
            color: #fff;
            font-size: 9px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 3px;
        }
        .warn {
            margin: 0 0 18px;
            padding: 12px 14px;
            border: 1px solid #fcd34d;
            background: #fffbeb;
            border-radius: 12px;
            font-size: 12px;
            line-height: 1.55;
            color: #92400e;
            font-weight: 600;
        }
        .actions { display: flex; gap: 10px; flex-wrap: wrap-reverse; }
        .btn {
            flex: 1 1 150px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 12.5px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: background .15s ease, color .15s ease, border-color .15s ease;
        }
        .btn.solid { background: #0f172a; color: #fff; border-color: #0f172a; }
        .btn.solid:hover { background: #1e293b; }
        .btn.ghost { background: #fff; color: #334155; border-color: #cbd5e1; }
        .btn.ghost:hover { background: var(--soft); }
        .hint { margin: 16px 0 0; font-size: 11px; line-height: 1.6; color: var(--muted); font-weight: 600; }
        footer { margin-top: 14px; text-align: center; font-size: 10.5px; color: #94a3b8; font-weight: 600; }
    </style>
</head>
<body>
    <main class="shell">
        <div class="brand">
            <img src="{{ asset('favicon.png') }}" alt="IntalnetAcces">
            <div>
                <strong>IntalnetAcces</strong>
                <span>Conector IA · solo lectura</span>
            </div>
        </div>

        @yield('content')

        <footer>El acceso se limita a la empresa y sede de tu cuenta.</footer>
    </main>
</body>
</html>
