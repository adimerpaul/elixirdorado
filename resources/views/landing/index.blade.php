<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elixir Dorado · Licorería Premium en Bolivia</title>
    <meta name="description" content="Tu licorería de confianza. Whisky, ron, vodka, vinos y más. Pide por WhatsApp.">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🥃</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #d4a574;
            --gold-light: #e8c79a;
            --gold-deep: #a07a45;
            --dark: #0d0d12;
            --dark-soft: #1a1a23;
            --cream: #f5ecd9;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: 'Poppins', system-ui, sans-serif; background: var(--dark); color: var(--cream); overflow-x: hidden; }
        h1, h2, h3, .serif { font-family: 'Playfair Display', Georgia, serif; }

        /* ── HERO ── */
        .hero {
            position: relative; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: radial-gradient(ellipse at top, #2a1f15 0%, var(--dark) 60%);
            overflow: hidden;
        }
        .hero::before, .hero::after {
            content: ''; position: absolute; border-radius: 50%; filter: blur(80px); opacity: .22;
            animation: float 14s ease-in-out infinite;
        }
        .hero::before { width: 520px; height: 520px; background: var(--gold); top: -140px; left: -140px; }
        .hero::after  { width: 400px; height: 400px; background: #b96f3f; bottom: -110px; right: -110px; animation-delay: -5s; }
        @keyframes float { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(40px,-40px) scale(1.08); } }

        .glass { background: rgba(255,255,255,0.04); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid rgba(212,165,116,0.15); }
        .gold-text { background: linear-gradient(135deg,#f5d896 0%,var(--gold) 50%,var(--gold-deep) 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }

        .btn-gold {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 14px 28px; border-radius: 999px; font-weight: 600;
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 50%, var(--gold-deep) 100%);
            color: #1a0f00; border: none; cursor: pointer;
            box-shadow: 0 10px 30px rgba(212,165,116,0.35);
            transition: transform .25s ease, box-shadow .25s ease; text-decoration: none;
        }
        .btn-gold:hover { transform: translateY(-3px); box-shadow: 0 16px 40px rgba(212,165,116,0.5); }
        .btn-ghost {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 22px; border-radius: 999px; font-weight: 600;
            color: var(--cream); border: 1px solid rgba(212,165,116,0.4);
            background: rgba(212,165,116,0.08); text-decoration: none; cursor: pointer;
            transition: background .25s ease, border-color .25s ease;
        }
        .btn-ghost:hover { background: rgba(212,165,116,0.18); border-color: var(--gold); }

        /* ── PRODUCT CARD ── */
        .product-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.04) 0%, rgba(212,165,116,0.05) 100%);
            border: 1px solid rgba(212,165,116,0.15); border-radius: 18px;
            overflow: hidden; transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease;
            display: flex; flex-direction: column;
        }
        .product-card:hover { transform: translateY(-7px); border-color: var(--gold); box-shadow: 0 20px 50px rgba(0,0,0,0.55); }
        .product-card.in-cart { border-color: rgba(37,211,102,0.5); box-shadow: 0 0 0 1px rgba(37,211,102,0.2); }
        .product-img { width: 100%; aspect-ratio: 1/1; background: radial-gradient(circle at 30% 30%,#2a1d12,#11100a); display: flex; align-items: center; justify-content: center; font-size: 70px; overflow: hidden; }
        .product-img img { width: 100%; height: 100%; object-fit: cover; }
        .product-body { padding: 14px; display: flex; flex-direction: column; flex: 1; }
        .product-cat { font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(212,165,116,0.7); margin-bottom: 5px; }
        .product-name { font-weight: 600; font-size: 14px; line-height: 1.4; margin: 0 0 8px 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .product-price { font-family: 'Playfair Display',serif; font-size: 20px; font-weight: 700; background: linear-gradient(135deg,#f5d896,var(--gold)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .product-stock { font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 999px; background: rgba(34,197,94,0.15); color: #86efac; border: 1px solid rgba(134,239,172,0.25); }
        .product-stock.low { background: rgba(251,191,36,0.15); color: #fcd34d; border-color: rgba(252,211,77,0.25); }

        /* ── BOTÓN AGREGAR ── */
        .btn-agregar {
            margin-top: 10px; width: 100%; padding: 9px; border-radius: 12px;
            font-weight: 600; font-size: 13px; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 7px;
            transition: all .2s ease;
        }
        .btn-agregar.default { background: rgba(212,165,116,0.12); color: var(--gold-light); border: 1px solid rgba(212,165,116,0.25); }
        .btn-agregar.default:hover { background: rgba(212,165,116,0.22); border-color: var(--gold); }
        .btn-agregar.added { background: rgba(37,211,102,0.15); color: #86efac; border: 1px solid rgba(37,211,102,0.3); }
        .btn-agregar.added:hover { background: rgba(37,211,102,0.25); }

        /* ── CATEGORY PILLS ── */
        .cat-pill { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 999px; font-size: 13px; font-weight: 600; border: 1px solid rgba(212,165,116,0.25); background: rgba(255,255,255,0.03); color: rgba(245,236,217,0.75); cursor: pointer; transition: all .2s ease; white-space: nowrap; }
        .cat-pill:hover { background: rgba(212,165,116,0.1); border-color: rgba(212,165,116,0.5); color: var(--cream); }
        .cat-pill.active { background: linear-gradient(135deg,rgba(212,165,116,0.25),rgba(212,165,116,0.12)); border-color: var(--gold); color: var(--gold-light); box-shadow: 0 0 0 1px rgba(212,165,116,0.2); }
        .cat-count { background: rgba(212,165,116,0.2); color: var(--gold); font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 999px; min-width: 22px; text-align: center; }

        /* ── SEARCH ── */
        .search-wrap { position: relative; }
        .search-wrap i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: rgba(212,165,116,0.5); }
        .search-input { width: 100%; padding: 12px 16px 12px 44px; border-radius: 999px; background: rgba(255,255,255,0.05); color: var(--cream); border: 1px solid rgba(212,165,116,0.2); font-size: 14px; font-family: inherit; transition: border-color .2s ease, box-shadow .2s ease; }
        .search-input:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(212,165,116,0.15); }
        .search-input::placeholder { color: rgba(245,236,217,0.35); }

        /* ── SECTION ── */
        .section-title { font-size: clamp(28px,4vw,44px); font-weight: 900; letter-spacing: -0.02em; line-height: 1.1; }
        .divider-gold { width: 60px; height: 3px; background: linear-gradient(90deg,transparent,var(--gold),transparent); margin: 12px auto 20px; }
        .section-subtitle { color: rgba(245,236,217,0.6); max-width: 600px; margin: 0 auto; }
        .step { text-align: center; padding: 24px; background: linear-gradient(180deg,rgba(255,255,255,0.03) 0%,transparent 100%); border: 1px solid rgba(212,165,116,0.12); border-radius: 16px; transition: transform .3s ease, border-color .3s ease; }
        .step:hover { transform: translateY(-4px); border-color: var(--gold); }
        .step-num { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg,var(--gold),var(--gold-deep)); color: #1a0f00; font-weight: 900; font-size: 22px; margin: 0 auto 14px; box-shadow: 0 8px 20px rgba(212,165,116,0.35); }

        /* ── REVEAL ── */
        .reveal { opacity: 0; transform: translateY(30px); transition: opacity .8s ease, transform .8s ease; }
        .reveal.in { opacity: 1; transform: translateY(0); }

        /* ── NAVBAR ── */
        .navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 50; padding: 16px 24px; transition: background .3s ease; }
        .navbar.scrolled { background: rgba(13,13,18,0.9); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border-bottom: 1px solid rgba(212,165,116,0.18); }

        /* ─────────────────────────────────────────────
           CARRITO FLOTANTE
        ───────────────────────────────────────────── */
        #carrito-panel {
            position: fixed; right: 16px; bottom: 100px; z-index: 59;
            width: 320px; max-height: 420px;
            background: #1a1a23; border: 1px solid rgba(212,165,116,0.3);
            border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.7);
            display: flex; flex-direction: column; overflow: hidden;
            transform: translateY(20px) scale(0.95); opacity: 0; pointer-events: none;
            transition: transform .3s cubic-bezier(.25,.8,.3,1), opacity .3s ease;
        }
        #carrito-panel.open { transform: translateY(0) scale(1); opacity: 1; pointer-events: auto; }
        .carrito-header {
            padding: 14px 16px 12px; border-bottom: 1px solid rgba(212,165,116,0.15);
            display: flex; align-items: center; justify-content: space-between;
            background: linear-gradient(135deg,rgba(212,165,116,0.1),rgba(212,165,116,0.04));
        }
        .carrito-header span { font-weight: 700; font-size: 15px; }
        .carrito-header button { background: none; border: none; color: rgba(245,236,217,0.5); cursor: pointer; font-size: 16px; line-height: 1; padding: 2px; transition: color .2s; }
        .carrito-header button:hover { color: var(--cream); }
        .carrito-items { flex: 1; overflow-y: auto; padding: 8px 0; scrollbar-width: thin; scrollbar-color: rgba(212,165,116,0.2) transparent; }
        .carrito-items::-webkit-scrollbar { width: 4px; }
        .carrito-items::-webkit-scrollbar-track { background: transparent; }
        .carrito-items::-webkit-scrollbar-thumb { background: rgba(212,165,116,0.2); border-radius: 2px; }
        .carrito-item { display: flex; align-items: center; gap: 10px; padding: 8px 14px; transition: background .15s; }
        .carrito-item:hover { background: rgba(255,255,255,0.03); }
        .carrito-item-info { flex: 1; min-width: 0; }
        .carrito-item-name { font-size: 12px; font-weight: 500; line-height: 1.35; color: var(--cream); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .carrito-item-sub  { font-size: 11px; color: rgba(245,236,217,0.45); margin-top: 1px; }
        .carrito-item-sub strong { color: var(--gold-light); font-weight: 600; }
        .qty-controls { display: flex; align-items: center; gap: 0; border: 1px solid rgba(212,165,116,0.25); border-radius: 8px; overflow: hidden; flex-shrink: 0; }
        .qty-btn { background: rgba(212,165,116,0.08); border: none; color: var(--gold-light); width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; font-weight: 700; transition: background .15s; }
        .qty-btn:hover { background: rgba(212,165,116,0.2); }
        .qty-num { min-width: 28px; text-align: center; font-size: 13px; font-weight: 700; color: var(--cream); background: rgba(255,255,255,0.04); line-height: 28px; }
        .carrito-del { background: none; border: none; color: rgba(245,236,217,0.3); cursor: pointer; font-size: 13px; padding: 4px; transition: color .2s; flex-shrink: 0; }
        .carrito-del:hover { color: #fca5a5; }
        .carrito-total-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; border-top: 1px solid rgba(212,165,116,0.15); background: rgba(212,165,116,0.06); }
        .carrito-total-label { font-size: 13px; color: rgba(245,236,217,0.65); }
        .carrito-total-valor { font-family: 'Playfair Display',serif; font-size: 18px; font-weight: 700; background: linear-gradient(135deg,#f5d896,var(--gold)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .carrito-footer { padding: 10px 14px 14px; }
        .carrito-vacío { padding: 32px 16px; text-align: center; color: rgba(245,236,217,0.35); font-size: 13px; }
        .carrito-vacío i { font-size: 32px; display: block; margin-bottom: 10px; opacity: .4; }
        .btn-wa-enviar {
            width: 100%; padding: 12px; border-radius: 12px; border: none; cursor: pointer; font-weight: 700; font-size: 14px;
            background: linear-gradient(135deg,#25d366,#1ea855);
            color: white; display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 20px rgba(37,211,102,0.35);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .btn-wa-enviar:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(37,211,102,0.5); }
        .btn-wa-enviar:disabled { opacity: .45; cursor: not-allowed; transform: none; }

        /* ── WHATSAPP FAB ── */
        #wa-fab {
            position: fixed; right: 16px; bottom: 24px; z-index: 60;
            width: 64px; height: 64px; border-radius: 50%;
            background: #25d366; color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; border: none; cursor: pointer;
            box-shadow: 0 8px 28px rgba(37,211,102,0.55);
            transition: transform .25s ease;
        }
        #wa-fab:hover { transform: scale(1.1); }
        #wa-fab.pulse { animation: pulse-wa 2s ease-in-out infinite; }
        @keyframes pulse-wa {
            0%   { box-shadow: 0 8px 28px rgba(37,211,102,0.55), 0 0 0 0 rgba(37,211,102,0.5); }
            70%  { box-shadow: 0 8px 28px rgba(37,211,102,0.55), 0 0 0 22px rgba(37,211,102,0); }
            100% { box-shadow: 0 8px 28px rgba(37,211,102,0.55), 0 0 0 0 rgba(37,211,102,0); }
        }
        #wa-badge {
            position: absolute; top: -5px; right: -5px; z-index: 1;
            background: #ef4444; color: white; font-size: 11px; font-weight: 800;
            min-width: 22px; height: 22px; border-radius: 999px;
            display: flex; align-items: center; justify-content: center; padding: 0 5px;
            border: 2px solid var(--dark); transition: transform .3s cubic-bezier(.34,1.56,.64,1);
            transform: scale(0);
        }
        #wa-badge.show { transform: scale(1); }

        /* Animación "pop" al agregar */
        @keyframes pop { 0% { transform:scale(1); } 40% { transform:scale(1.15); } 100% { transform:scale(1); } }
        .pop { animation: pop .35s ease; }

        /* ── SCROLLBAR CATEGORÍAS ── */
        .cat-scroll { scrollbar-width: thin; scrollbar-color: rgba(212,165,116,0.3) transparent; }
        .cat-scroll::-webkit-scrollbar { height: 4px; }
        .cat-scroll::-webkit-scrollbar-thumb { background: rgba(212,165,116,0.3); border-radius: 2px; }

        /* ── CARD ACTIONS ── */
        .card-actions { display: flex; gap: 4px; align-items: stretch; margin-top: 10px; }
        .card-actions .btn-agregar { margin-top: 0; flex: 1; }
        .btn-detalles {
            flex-shrink: 0; display: flex; align-items: center; justify-content: center;
            padding: 0 11px; border-radius: 12px; font-size: 13px; cursor: pointer;
            border: 1px solid rgba(212,165,116,0.25); background: rgba(212,165,116,0.06);
            color: rgba(212,165,116,0.7); text-decoration: none;
            transition: all .2s ease;
        }
        .btn-detalles:hover { background: rgba(212,165,116,0.18); color: var(--gold-light); border-color: var(--gold); }

        footer { background: #06060a; padding: 40px 24px 110px; }

        @media (max-width: 400px) {
            #carrito-panel { right: 8px; left: 8px; width: auto; }
            #wa-fab { right: 16px; }
        }
    </style>
</head>
<body>

{{-- ═══ NAVBAR ═══ --}}
<nav class="navbar" id="navbar">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-3xl">🥃</span>
            <div>
                <div class="serif text-xl font-bold gold-text leading-none">Elixir Dorado</div>
                <div class="text-[11px] text-amber-200/60 tracking-widest uppercase">Licorería Premium</div>
            </div>
        </div>
        <div class="hidden md:flex items-center gap-7 text-sm font-medium">
            <a href="#inicio"    class="hover:text-amber-300 transition-colors">Inicio</a>
            <a href="#productos" class="hover:text-amber-300 transition-colors">Catálogo</a>
            <a href="#como-pedir" class="hover:text-amber-300 transition-colors">Cómo pedir</a>
            <a href="#contacto"  class="hover:text-amber-300 transition-colors">Contacto</a>
        </div>
        <a href="#productos" class="btn-gold !py-2 !px-5 text-sm">
            <i class="fas fa-wine-glass-alt"></i>
            <span class="hidden sm:inline">Ver catálogo</span>
        </a>
    </div>
</nav>

{{-- ═══ HERO ═══ --}}
<section class="hero" id="inicio">
    <div class="relative z-10 max-w-5xl mx-auto px-6 text-center">
        <div class="reveal">
            <span class="inline-block px-4 py-2 rounded-full text-xs font-semibold tracking-widest uppercase glass mb-6">
                <i class="fas fa-star text-amber-400 mr-1"></i>
                La mejor licorería de Bolivia
            </span>
            <h1 class="serif font-black leading-[1.05] mb-6" style="font-size:clamp(40px,7vw,80px)">
                Sabores que <span class="gold-text">despiertan</span><br>
                <span class="gold-text">los sentidos</span>
            </h1>
            <p class="text-base md:text-lg text-amber-100/70 max-w-2xl mx-auto mb-10">
                Whisky, ron, vodka, vinos, cervezas y más. Agrega los productos que quieres y
                pide directo por WhatsApp en segundos.
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="#productos" class="btn-gold">
                    <i class="fas fa-shopping-basket"></i> Explorar catálogo
                </a>
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="btn-ghost">
                    <i class="fab fa-whatsapp"></i> Escribir directo
                </a>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto mt-16">
            <div class="reveal" style="transition-delay:.15s">
                <div class="serif text-3xl md:text-4xl font-bold gold-text">{{ $productos->count() ?: '+500' }}</div>
                <div class="text-xs uppercase tracking-widest text-amber-100/50 mt-1">{{ $productos->count() ? 'En stock' : 'Productos' }}</div>
            </div>
            <div class="reveal" style="transition-delay:.3s">
                <div class="serif text-3xl md:text-4xl font-bold gold-text">30 min</div>
                <div class="text-xs uppercase tracking-widest text-amber-100/50 mt-1">Entrega</div>
            </div>
            <div class="reveal" style="transition-delay:.45s">
                <div class="serif text-3xl md:text-4xl font-bold gold-text">100%</div>
                <div class="text-xs uppercase tracking-widest text-amber-100/50 mt-1">Original</div>
            </div>
        </div>
    </div>
    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 text-amber-200/40 text-xs flex flex-col items-center gap-2 animate-bounce">
        <span>Desliza</span><i class="fas fa-chevron-down"></i>
    </div>
</section>

{{-- ═══ FEATURES ═══ --}}
<section class="py-20 px-6">
    <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-6">
        @php $features = [
            ['fa-truck-fast',    'Entrega rápida',       'Recibe tu pedido a domicilio en menos de 30 minutos.'],
            ['fa-shield-halved', 'Productos originales', 'Solo trabajamos con marcas certificadas y de origen confirmado.'],
            ['fa-tags',          'Mejores precios',       'Precios competitivos y promociones todos los días.'],
        ]; @endphp
        @foreach($features as $i => $f)
            <div class="step reveal" style="transition-delay:{{ $i*.15 }}s">
                <i class="fas {{ $f[0] }} text-3xl mb-3" style="color:var(--gold)"></i>
                <h3 class="text-xl font-bold mb-2">{{ $f[1] }}</h3>
                <p class="text-sm text-amber-100/60">{{ $f[2] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ═══ CATÁLOGO ═══ --}}
<section class="py-20 px-6" id="productos" style="background:linear-gradient(180deg,transparent 0%,rgba(212,165,116,0.03) 50%,transparent 100%)">
    <div class="max-w-7xl mx-auto">
        <div class="text-center reveal mb-10">
            <span class="text-xs uppercase tracking-[4px] text-amber-300/70 font-semibold">Nuestra selección</span>
            <h2 class="section-title gold-text mt-1">Catálogo Completo</h2>
            <div class="divider-gold"></div>
            <p class="section-subtitle text-sm">Elige los productos, ajusta la cantidad y envía tu pedido directo por WhatsApp.</p>
        </div>

        @if($productos->count() > 0)
            {{-- Search + count --}}
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center mb-6 reveal">
                <div class="search-wrap flex-1 max-w-sm">
                    <i class="fas fa-search text-sm"></i>
                    <input type="text" id="buscador" class="search-input" placeholder="Buscar producto..." oninput="filtrar()">
                </div>
                <div id="conteo-productos" class="text-sm text-amber-200/50"></div>
            </div>

            {{-- Category pills --}}
            @if($categorias->count() > 0)
                @php
                    $catCounts = $productos->groupBy('categoria_nombre')->map(fn($g)=>$g->count());
                @endphp
                <div class="flex gap-2 overflow-x-auto pb-3 mb-8 cat-scroll reveal">
                    <button class="cat-pill active flex-shrink-0" data-cat="todos" onclick="seleccionarCat('todos',this)">
                        <i class="fas fa-border-all text-xs"></i> Todos
                        <span class="cat-count">{{ $productos->count() }}</span>
                    </button>
                    @foreach($categorias as $cat)
                        @if(!empty($catCounts[$cat->nombre]))
                            <button class="cat-pill flex-shrink-0" data-cat="{{ $cat->nombre }}" onclick="seleccionarCat('{{ addslashes($cat->nombre) }}',this)">
                                {{ $cat->nombre }}
                                <span class="cat-count">{{ $catCounts[$cat->nombre] }}</span>
                            </button>
                        @endif
                    @endforeach
                    @php $sinCat = $productos->filter(fn($p)=>empty($p->categoria_nombre))->count(); @endphp
                    @if($sinCat > 0)
                        <button class="cat-pill flex-shrink-0" data-cat="__sin__" onclick="seleccionarCat('__sin__',this)">
                            Sin categoría <span class="cat-count">{{ $sinCat }}</span>
                        </button>
                    @endif
                </div>
            @endif

            {{-- Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4" id="grilla-productos">
                @foreach($productos as $p)
                    <div class="product-card"
                         id="card-{{ $p->id }}"
                         data-cat="{{ $p->categoria_nombre ?? '' }}"
                         data-nombre="{{ mb_strtolower($p->nombre) }}"
                         data-sin="{{ empty($p->categoria_nombre)?'1':'0' }}">
                        <div class="product-img">
                            @if(!empty($p->imagen))
                                <img src="{{ asset('storage/'.$p->imagen) }}" alt="{{ $p->nombre }}" loading="lazy">
                            @else
                                <span style="opacity:.7">🍾</span>
                            @endif
                        </div>
                        <div class="product-body">
                            @if(!empty($p->categoria_nombre))
                                <div class="product-cat">{{ $p->categoria_nombre }}</div>
                            @endif
                            <h3 class="product-name">{{ $p->nombre }}</h3>
                            <div class="flex items-end justify-between mt-auto pt-1">
                                <div class="product-price">Bs.&nbsp;{{ number_format($p->precio_venta,0) }}</div>
                                @if($p->stock_actual <= 5)
                                    <span class="product-stock low">{{ $p->stock_actual }} uds</span>
                                @else
                                    <span class="product-stock">En stock</span>
                                @endif
                            </div>
                            <div class="card-actions">
                                <button class="btn-agregar default"
                                        id="btn-{{ $p->id }}"
                                        onclick="agregarAlCarrito({{ $p->id }}, '{{ addslashes($p->nombre) }}', {{ $p->precio_venta }})">
                                    <i class="fas fa-plus text-xs"></i> Agregar
                                </button>
                                <a href="/producto/{{ $p->id }}/{{ Str::slug($p->nombre) }}" class="btn-detalles" title="Ver detalles">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden text-center py-16" id="sin-resultados">
                <i class="fas fa-search text-5xl text-amber-300/20 mb-4"></i>
                <p class="text-amber-100/60 text-lg font-semibold">Sin resultados</p>
                <button onclick="resetFiltros()" class="btn-ghost mt-6"><i class="fas fa-times"></i> Limpiar filtros</button>
            </div>
        @else
            <div class="glass rounded-3xl py-16 px-6 text-center reveal">
                <i class="fas fa-wine-bottle text-5xl text-amber-300/30 mb-4"></i>
                <p class="text-amber-100/70 text-lg">El catálogo estará disponible pronto.</p>
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="btn-gold mt-6 inline-flex">
                    <i class="fab fa-whatsapp"></i> Consultar por WhatsApp
                </a>
            </div>
        @endif

        <div class="text-center mt-14 reveal">
            <p class="text-amber-100/50 text-sm mb-3">¿Ya tienes productos en tu carrito?</p>
            <button onclick="abrirCarrito()" class="btn-gold">
                <i class="fab fa-whatsapp text-lg"></i> Ver mi pedido y enviar por WhatsApp
            </button>
        </div>
    </div>
</section>

{{-- ═══ CÓMO PEDIR ═══ --}}
<section class="py-20 px-6" id="como-pedir">
    <div class="max-w-5xl mx-auto">
        <div class="text-center reveal">
            <span class="text-xs uppercase tracking-[4px] text-amber-300/70 font-semibold">Tan fácil como</span>
            <h2 class="section-title gold-text mt-1">3 pasos para tu pedido</h2>
            <div class="divider-gold"></div>
        </div>
        <div class="grid md:grid-cols-3 gap-6 mt-12">
            @php $pasos = [
                ['1','Elige productos',    'Haz clic en "Agregar" en cada producto que quieras pedir.'],
                ['2','Revisa tu carrito',  'Ajusta la cantidad de cada producto desde el carrito flotante.'],
                ['3','Envía por WhatsApp', 'Un clic y tu pedido llega directo al WhatsApp de la tienda.'],
            ]; @endphp
            @foreach($pasos as $i => $p)
                <div class="step reveal" style="transition-delay:{{ $i*.15 }}s">
                    <div class="step-num">{{ $p[0] }}</div>
                    <h3 class="text-lg font-bold mb-2">{{ $p[1] }}</h3>
                    <p class="text-sm text-amber-100/60">{{ $p[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ CTA / CONTACTO ═══ --}}
<section class="py-20 px-6" id="contacto">
    <div class="max-w-4xl mx-auto glass rounded-3xl p-10 md:p-14 text-center reveal">
        <h2 class="section-title gold-text">¿Listo para tu pedido?</h2>
        <div class="divider-gold"></div>
        <p class="section-subtitle">Selecciona los productos y envía tu pedido directo por WhatsApp.</p>
        <div class="flex flex-wrap justify-center gap-4 mt-8">
            <a href="#productos" class="btn-gold">
                <i class="fas fa-shopping-basket"></i> Ver catálogo
            </a>
            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="btn-ghost">
                <i class="fab fa-whatsapp"></i> +{{ $whatsapp }}
            </a>
        </div>
        @if($sucursal)
            <div class="mt-10 grid sm:grid-cols-3 gap-6 text-sm">
                <div><i class="fas fa-store text-amber-400 mb-2 text-lg"></i><div class="font-semibold">{{ $sucursal->nombre }}</div></div>
                @if(!empty($sucursal->direccion))<div><i class="fas fa-map-marker-alt text-amber-400 mb-2 text-lg"></i><div class="font-semibold">{{ $sucursal->direccion }}</div></div>@endif
                @if(!empty($sucursal->telefono))<div><i class="fas fa-phone text-amber-400 mb-2 text-lg"></i><div class="font-semibold">{{ $sucursal->telefono }}</div></div>@endif
            </div>
        @endif
    </div>
</section>

{{-- ═══ FOOTER ═══ --}}
<footer>
    <div class="max-w-6xl mx-auto text-center text-amber-100/40 text-sm">
        <div class="serif text-2xl gold-text font-bold mb-2">🥃 Elixir Dorado</div>
        <p class="mb-3">Licorería premium · Bolivia</p>
        <p class="text-xs">© {{ now()->year }} Elixir Dorado. Bebe responsablemente. Prohibida la venta a menores de 18 años.</p>
    </div>
</footer>

{{-- ═══ CARRITO FLOTANTE ═══ --}}
<div id="carrito-panel">
    <div class="carrito-header">
        <span><i class="fas fa-shopping-basket text-amber-400 mr-2"></i>Mi pedido</span>
        <button onclick="cerrarCarrito()" title="Cerrar"><i class="fas fa-times"></i></button>
    </div>
    <div id="carrito-items" class="carrito-items">
        <div class="carrito-vacío" id="carrito-vacio">
            <i class="fas fa-shopping-basket"></i>
            Aún no has agregado productos.<br>
            <span style="font-size:12px;margin-top:4px;display:block">Haz clic en <strong>Agregar</strong> en cualquier producto.</span>
        </div>
    </div>
    <div id="carrito-total-row" class="carrito-total-row" style="display:none">
        <span class="carrito-total-label">Total estimado</span>
        <span class="carrito-total-valor" id="carrito-total-valor">Bs. 0</span>
    </div>
    <div class="carrito-footer" id="carrito-footer" style="display:none">
        <button class="btn-wa-enviar" id="btn-enviar-wa" onclick="enviarPorWhatsApp()">
            <i class="fab fa-whatsapp text-xl"></i>
            Enviar pedido por WhatsApp
        </button>
    </div>
</div>

{{-- ═══ WHATSAPP FAB ═══ --}}
<button id="wa-fab" class="pulse" onclick="toggleCarrito()" title="Mi pedido / WhatsApp" aria-label="Carrito de pedido">
    <i class="fab fa-whatsapp"></i>
    <span id="wa-badge"></span>
</button>

<script>
// ══════════════════════════════════════════════
// CARRITO  (persistido en localStorage)
// ══════════════════════════════════════════════
let carrito = {};
(function() {
    try { carrito = JSON.parse(localStorage.getItem('elixir_carrito') || '{}'); } catch(e) {}
})();

let carritoAbierto = false;

function saveCarrito() {
    try { localStorage.setItem('elixir_carrito', JSON.stringify(carrito)); } catch(e) {}
}

function agregarAlCarrito(id, nombre, precio) {
    if (carrito[id]) {
        carrito[id].qty++;
    } else {
        carrito[id] = { nombre, precio, qty: 1 };
    }
    saveCarrito();
    renderCarrito();
    actualizarBoton(id, true);
    abrirCarrito();
    animarFab();
}

function cambiarCantidad(id, delta) {
    if (!carrito[id]) return;
    carrito[id].qty += delta;
    if (carrito[id].qty <= 0) {
        delete carrito[id];
        actualizarBoton(id, false);
    }
    saveCarrito();
    renderCarrito();
    actualizarBadge();
}

function quitarDelCarrito(id) {
    delete carrito[id];
    actualizarBoton(id, false);
    saveCarrito();
    renderCarrito();
    actualizarBadge();
}

function fmtBs(n) {
    return 'Bs. ' + n.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function renderCarrito() {
    const ids       = Object.keys(carrito);
    const container = document.getElementById('carrito-items');
    const vacio     = document.getElementById('carrito-vacio');
    const footer    = document.getElementById('carrito-footer');
    const totalRow  = document.getElementById('carrito-total-row');
    const totalVal  = document.getElementById('carrito-total-valor');

    // Eliminar solo las filas de producto, nunca el elemento vacio
    container.querySelectorAll('.carrito-item').forEach(el => el.remove());

    if (ids.length === 0) {
        vacio.style.display    = '';
        footer.style.display   = 'none';
        totalRow.style.display = 'none';
        actualizarBadge();
        return;
    }

    vacio.style.display    = 'none';
    footer.style.display   = '';
    totalRow.style.display = '';

    let total = 0;

    ids.forEach(id => {
        const item    = carrito[id];
        const subtotal = item.precio * item.qty;
        total += subtotal;

        const div      = document.createElement('div');
        div.className  = 'carrito-item';
        div.dataset.id = id;
        div.innerHTML  = `
            <div class="carrito-item-info">
                <div class="carrito-item-name">${escHtml(item.nombre)}</div>
                <div class="carrito-item-sub">
                    ${fmtBs(item.precio)} × ${item.qty}
                    &nbsp;=&nbsp;<strong>${fmtBs(subtotal)}</strong>
                </div>
            </div>
            <div class="qty-controls">
                <button class="qty-btn" onclick="cambiarCantidad(${id},-1)">−</button>
                <div class="qty-num">${item.qty}</div>
                <button class="qty-btn" onclick="cambiarCantidad(${id},1)">+</button>
            </div>
            <button class="carrito-del" onclick="quitarDelCarrito(${id})" title="Quitar">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;
        container.appendChild(div);
    });

    totalVal.textContent = fmtBs(total);
    actualizarBadge();
}

function actualizarBadge() {
    const total  = Object.values(carrito).reduce((s,i) => s + i.qty, 0);
    const badge  = document.getElementById('wa-badge');
    const fab    = document.getElementById('wa-fab');
    badge.textContent = total > 0 ? (total > 99 ? '99+' : total) : '';
    badge.classList.toggle('show', total > 0);
    fab.classList.toggle('pulse', total === 0);
}

function actualizarBoton(id, enCarrito) {
    const btn = document.getElementById('btn-' + id);
    if (!btn) return;
    const card = document.getElementById('card-' + id);
    if (enCarrito) {
        btn.className = 'btn-agregar added';
        btn.innerHTML = '<i class="fas fa-check text-xs"></i> Agregado';
        card?.classList.add('in-cart');
    } else {
        btn.className = 'btn-agregar default';
        btn.innerHTML = '<i class="fas fa-plus text-xs"></i> Agregar';
        card?.classList.remove('in-cart');
    }
}

function abrirCarrito() {
    document.getElementById('carrito-panel').classList.add('open');
    carritoAbierto = true;
}
function cerrarCarrito() {
    document.getElementById('carrito-panel').classList.remove('open');
    carritoAbierto = false;
}
function toggleCarrito() {
    if (carritoAbierto) cerrarCarrito();
    else abrirCarrito();
}

function animarFab() {
    const fab = document.getElementById('wa-fab');
    fab.classList.add('pop');
    fab.addEventListener('animationend', () => fab.classList.remove('pop'), { once: true });
}

// ── Enviar a WhatsApp ────────────────────────────────────────────────
function enviarPorWhatsApp() {
    const ids = Object.keys(carrito);
    if (ids.length === 0) return;

    let total = 0;
    let msg   = '🥃 *Elixir Dorado — Pedido*\n';
    msg += '━━━━━━━━━━━━━━━━━━\n';

    ids.forEach(id => {
        const item     = carrito[id];
        const subtotal = item.precio * item.qty;
        total += subtotal;
        msg += `• ${item.qty}x ${item.nombre}\n`;
        msg += `   Bs. ${item.precio.toLocaleString('es-BO')} c/u = *Bs. ${subtotal.toLocaleString('es-BO')}*\n`;
    });

    msg += '━━━━━━━━━━━━━━━━━━\n';
    msg += `💰 *Total: Bs. ${total.toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}*\n\n`;
    msg += '¿Pueden confirmar disponibilidad? Gracias 🙏';

    const url = 'https://wa.me/{{ $whatsapp }}?text=' + encodeURIComponent(msg);
    window.open(url, '_blank', 'noopener');
}

// ── XSS helper ──────────────────────────────────────────────────────
function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ══════════════════════════════════════════════
// FILTROS
// ══════════════════════════════════════════════
let catActiva = 'todos';
const cards = () => document.querySelectorAll('#grilla-productos .product-card');

function contarVisibles() {
    const n = [...cards()].filter(c => c.style.display !== 'none').length;
    const el = document.getElementById('conteo-productos');
    if (el) el.textContent = n + ' producto' + (n !== 1 ? 's' : '');
    document.getElementById('sin-resultados')?.classList.toggle('hidden', n > 0);
    document.getElementById('grilla-productos')?.classList.toggle('hidden', n === 0);
}

function filtrar() {
    const q = (document.getElementById('buscador')?.value || '').toLowerCase().trim();
    cards().forEach(card => {
        const matchCat = catActiva === 'todos' ||
                         (catActiva === '__sin__' && card.dataset.sin === '1') ||
                         card.dataset.cat === catActiva;
        const matchQ   = !q || card.dataset.nombre.includes(q);
        card.style.display = (matchCat && matchQ) ? '' : 'none';
    });
    contarVisibles();
}

function seleccionarCat(cat, btn) {
    catActiva = cat;
    document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    filtrar();
}

function resetFiltros() {
    catActiva = 'todos';
    const b = document.getElementById('buscador');
    if (b) b.value = '';
    document.querySelectorAll('.cat-pill').forEach((p,i) => p.classList.toggle('active', i === 0));
    filtrar();
}

document.addEventListener('DOMContentLoaded', () => {
    Object.keys(carrito).forEach(id => actualizarBoton(id, true));
    contarVisibles();
    renderCarrito();
});

// ══════════════════════════════════════════════
// REVEAL + NAVBAR
// ══════════════════════════════════════════════
const io = new IntersectionObserver(
    entries => entries.forEach(en => { if(en.isIntersecting) en.target.classList.add('in'); }),
    { threshold: 0.1 }
);
document.querySelectorAll('.reveal').forEach(el => io.observe(el));

const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => navbar.classList.toggle('scrolled', window.scrollY > 30), { passive: true });

// Cerrar carrito al hacer clic fuera de él
document.addEventListener('click', e => {
    const panel = document.getElementById('carrito-panel');
    const fab   = document.getElementById('wa-fab');
    if (carritoAbierto && !panel.contains(e.target) && !fab.contains(e.target)) {
        cerrarCarrito();
    }
});
</script>
</body>
</html>
