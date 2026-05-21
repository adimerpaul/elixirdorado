<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $sucursal->nombre }} — Catálogo</title>
    <meta name="description" content="Catálogo de productos de {{ $sucursal->nombre }}. Realiza tu pedido por WhatsApp.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --gold:       #C9A84C;
            --gold-light: #E8C96B;
            --gold-dark:  #9B7A2E;
            --black:      #0D0D0D;
            --dark:       #1A1A1A;
            --dark2:      #252525;
            --dark3:      #2E2E2E;
            --gray:       #888;
            --light:      #F5F0E8;
            --white:      #FFFFFF;
            --radius:     12px;
            --shadow:     0 4px 24px rgba(0,0,0,0.35);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--black);
            color: var(--white);
            min-height: 100vh;
        }

        /* ── HEADER ── */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(13,13,13,0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(201,168,76,0.2);
            padding: 0 24px;
        }
        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
            gap: 16px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: var(--gold);
            line-height: 1.1;
        }
        .logo-sub {
            font-size: 11px;
            color: var(--gray);
            font-weight: 400;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .cart-btn {
            position: relative;
            background: var(--dark2);
            border: 1px solid rgba(201,168,76,0.3);
            color: var(--gold);
            padding: 10px 18px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .cart-btn:hover { background: var(--dark3); border-color: var(--gold); }
        .cart-count {
            background: var(--gold);
            color: var(--black);
            font-size: 11px;
            font-weight: 700;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(160deg, #1a1100 0%, #0d0d0d 60%);
            border-bottom: 1px solid rgba(201,168,76,0.15);
            padding: 60px 24px;
            text-align: center;
        }
        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(32px, 5vw, 54px);
            color: var(--gold);
            line-height: 1.1;
            margin-bottom: 14px;
        }
        .hero p {
            color: var(--gray);
            font-size: 16px;
            max-width: 480px;
            margin: 0 auto;
            line-height: 1.7;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(201,168,76,0.1);
            border: 1px solid rgba(201,168,76,0.3);
            color: var(--gold);
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 50px;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
        }

        /* ── CATEGORÍAS ── */
        .cats-bar {
            background: var(--dark);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 0 24px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .cats-bar::-webkit-scrollbar { height: 0; }
        .cats-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            gap: 4px;
            padding: 12px 0;
        }
        .cat-tab {
            flex-shrink: 0;
            padding: 8px 18px;
            border-radius: 50px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--gray);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .cat-tab:hover { color: var(--white); border-color: rgba(201,168,76,0.3); }
        .cat-tab.active {
            background: var(--gold);
            color: var(--black);
            font-weight: 700;
        }

        /* ── BÚSQUEDA ── */
        .search-wrap {
            max-width: 1200px;
            margin: 32px auto 0;
            padding: 0 24px;
        }
        .search-box {
            position: relative;
        }
        .search-box input {
            width: 100%;
            background: var(--dark2);
            border: 1px solid rgba(201,168,76,0.2);
            border-radius: var(--radius);
            padding: 14px 20px 14px 48px;
            color: var(--white);
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .search-box input:focus { border-color: var(--gold); }
        .search-box input::placeholder { color: var(--gray); }
        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
        }

        /* ── GRID PRODUCTOS ── */
        .products-section {
            max-width: 1200px;
            margin: 32px auto 80px;
            padding: 0 24px;
        }
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: var(--gold-light);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(201,168,76,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: var(--dark2);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: var(--radius);
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .product-card:hover {
            transform: translateY(-4px);
            border-color: rgba(201,168,76,0.4);
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .product-img {
            width: 100%;
            aspect-ratio: 4/3;
            object-fit: cover;
            background: var(--dark3);
            display: block;
        }
        .product-img-placeholder {
            width: 100%;
            aspect-ratio: 4/3;
            background: linear-gradient(135deg, var(--dark3) 0%, #1e1a10 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--gold-dark);
        }
        .product-img-placeholder span { font-size: 40px; }
        .product-img-placeholder small { font-size: 11px; color: var(--gray); }
        .product-body {
            padding: 16px;
        }
        .product-cat {
            font-size: 11px;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .product-name {
            font-size: 15px;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 6px;
        }
        .product-desc {
            font-size: 12px;
            color: var(--gray);
            line-height: 1.5;
            margin-bottom: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--gold);
        }
        .product-price small {
            font-size: 11px;
            color: var(--gray);
            font-weight: 400;
        }
        .add-btn {
            background: var(--gold);
            color: var(--black);
            border: none;
            border-radius: 50px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            white-space: nowrap;
        }
        .add-btn:hover { background: var(--gold-light); }
        .add-btn:active { transform: scale(0.96); }

        /* ── CARRITO PANEL ── */
        .cart-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 200;
            backdrop-filter: blur(4px);
        }
        .cart-overlay.open { display: block; }
        .cart-panel {
            position: fixed;
            top: 0;
            right: -100%;
            width: min(420px, 100vw);
            height: 100vh;
            background: var(--dark);
            border-left: 1px solid rgba(201,168,76,0.2);
            z-index: 201;
            display: flex;
            flex-direction: column;
            transition: right 0.3s ease;
            overflow: hidden;
        }
        .cart-panel.open { right: 0; }
        .cart-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cart-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            color: var(--gold);
        }
        .close-cart {
            background: var(--dark2);
            border: none;
            color: var(--white);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 16px 24px;
        }
        .cart-empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        .cart-empty span { font-size: 48px; display: block; margin-bottom: 12px; }
        .cart-item {
            display: flex;
            gap: 12px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            align-items: flex-start;
        }
        .cart-item-info { flex: 1; }
        .cart-item-name { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
        .cart-item-price { font-size: 13px; color: var(--gold); }
        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }
        .qty-btn {
            background: var(--dark3);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--white);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .qty-btn:hover { border-color: var(--gold); color: var(--gold); }
        .qty-num { font-size: 14px; font-weight: 600; min-width: 20px; text-align: center; }
        .remove-item {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
        }
        .remove-item:hover { color: #e55; }
        .cart-footer {
            padding: 20px 24px;
            border-top: 1px solid rgba(255,255,255,0.07);
        }
        .cart-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 16px;
        }
        .order-btn {
            width: 100%;
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: var(--white);
            border: none;
            border-radius: var(--radius);
            padding: 16px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: opacity 0.2s;
        }
        .order-btn:hover { opacity: 0.9; }
        .order-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        /* ── MODAL PEDIDO ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            z-index: 300;
            backdrop-filter: blur(6px);
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--dark);
            border: 1px solid rgba(201,168,76,0.3);
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .modal-head {
            background: linear-gradient(135deg, #1a1100, var(--dark));
            padding: 24px 28px;
            border-bottom: 1px solid rgba(201,168,76,0.15);
        }
        .modal-head h3 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: var(--gold);
            margin-bottom: 4px;
        }
        .modal-head p { font-size: 13px; color: var(--gray); }
        .modal-body { padding: 28px; }
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }
        .form-group input, .form-group select {
            width: 100%;
            background: var(--dark2);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 12px 16px;
            color: var(--white);
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus { border-color: var(--gold); }
        .form-group input::placeholder { color: var(--gray); }
        .form-group select option { background: var(--dark2); }
        .form-note {
            background: rgba(201,168,76,0.08);
            border: 1px solid rgba(201,168,76,0.2);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 12px;
            color: var(--gold-light);
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .modal-actions {
            display: flex;
            gap: 12px;
        }
        .btn-cancel {
            flex: 1;
            background: var(--dark2);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--white);
            border-radius: 8px;
            padding: 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-whatsapp {
            flex: 2;
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: var(--white);
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-whatsapp:hover { filter: brightness(1.1); }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--gray);
        }
        .empty-state span { font-size: 56px; display: block; margin-bottom: 16px; }

        /* ── FOOTER ── */
        footer {
            background: var(--dark);
            border-top: 1px solid rgba(201,168,76,0.1);
            text-align: center;
            padding: 24px;
            font-size: 13px;
            color: var(--gray);
        }
        footer strong { color: var(--gold); }

        /* ── TOAST ── */
        .toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: var(--gold);
            color: var(--black);
            font-weight: 700;
            font-size: 13px;
            padding: 12px 24px;
            border-radius: 50px;
            z-index: 400;
            transition: transform 0.3s;
            white-space: nowrap;
        }
        .toast.show { transform: translateX(-50%) translateY(0); }

        /* Responsive */
        @media (max-width: 600px) {
            .hero { padding: 40px 16px; }
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
            .product-price { font-size: 15px; }
            .add-btn { padding: 7px 12px; font-size: 12px; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header>
    <div class="header-inner">
        <a href="#" class="logo">
            <div class="logo-icon">🥃</div>
            <div>
                <div class="logo-text">{{ $sucursal->nombre }}</div>
                <div class="logo-sub">Catálogo en línea</div>
            </div>
        </a>
        <div class="header-right">
            <button class="cart-btn" onclick="toggleCart()">
                🛒 Mi Pedido
                <span class="cart-count" id="cartCount">0</span>
            </button>
        </div>
    </div>
</header>

<!-- HERO -->
<section class="hero">
    <div class="hero-badge">🇧🇴 Solo para Bolivia</div>
    <h1>Bienvenido a<br>{{ $sucursal->nombre }}</h1>
    <p>Explora nuestro catálogo, agrega lo que deseas y completa tu pedido por WhatsApp.</p>
</section>

<!-- CATEGORÍAS -->
<div class="cats-bar">
    <div class="cats-inner">
        <button class="cat-tab active" onclick="filterCat('all', this)">Todos</button>
        @foreach($categorias as $cat)
            <button class="cat-tab" onclick="filterCat('{{ $cat->id }}', this)">{{ $cat->nombre }}</button>
        @endforeach
    </div>
</div>

<!-- BÚSQUEDA -->
<div class="search-wrap">
    <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInput" placeholder="Buscar producto..." oninput="filterSearch(this.value)">
    </div>
</div>

<!-- PRODUCTOS -->
<section class="products-section">
    @if($productos->isEmpty())
        <div class="empty-state">
            <span>📦</span>
            <p>No hay productos disponibles por ahora.</p>
        </div>
    @else
        @php
            $grouped = $productos->groupBy('categoria_nombre');
        @endphp

        @foreach($grouped as $catNombre => $items)
            <div class="cat-group" data-cat-group="{{ $items->first()->categoria_id ?? 'sin' }}">
                <h2 class="section-title">
                    🏷️ {{ $catNombre ?? 'Sin categoría' }}
                    <small style="font-size:13px;color:var(--gray);font-family:'Inter',sans-serif;font-weight:400;">({{ $items->count() }})</small>
                </h2>
                <div class="products-grid" style="margin-bottom: 40px;">
                    @foreach($items as $prod)
                        <div class="product-card"
                             data-id="{{ $prod->id }}"
                             data-cat="{{ $prod->categoria_id ?? 'sin' }}"
                             data-nombre="{{ strtolower($prod->nombre) }}"
                             data-desc="{{ strtolower($prod->descripcion ?? '') }}">

                            @if($prod->imagen)
                                <img class="product-img"
                                     src="{{ asset('storage/' . $prod->imagen) }}"
                                     alt="{{ $prod->nombre }}"
                                     loading="lazy">
                            @else
                                <div class="product-img-placeholder">
                                    <span>🍾</span>
                                    <small>Sin imagen</small>
                                </div>
                            @endif

                            <div class="product-body">
                                <div class="product-cat">{{ $prod->categoria_nombre ?? 'General' }}</div>
                                <div class="product-name">{{ $prod->nombre }}</div>
                                @if($prod->descripcion)
                                    <div class="product-desc">{{ $prod->descripcion }}</div>
                                @endif
                                <div class="product-footer">
                                    <div class="product-price">
                                        Bs. {{ number_format($prod->precio_venta, 2) }}
                                        <br><small>por unidad</small>
                                    </div>
                                    <button class="add-btn" onclick="addToCart({{ $prod->id }}, '{{ addslashes($prod->nombre) }}', {{ $prod->precio_venta }}, '{{ $prod->categoria_nombre ?? 'General' }}')">
                                        + Agregar
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</section>

<!-- FOOTER -->
<footer>
    <strong>{{ $sucursal->nombre }}</strong> — Pedidos por WhatsApp 🇧🇴<br>
    <small>Panel exclusivo para clientes. No compartir enlace con personal interno.</small>
</footer>

<!-- CARRITO OVERLAY -->
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>

<!-- CARRITO PANEL -->
<div class="cart-panel" id="cartPanel">
    <div class="cart-header">
        <h2>🛒 Mi Pedido</h2>
        <button class="close-cart" onclick="toggleCart()">✕</button>
    </div>
    <div class="cart-items" id="cartItems">
        <div class="cart-empty">
            <span>🛒</span>
            <p>Tu pedido está vacío.<br>Agrega productos del catálogo.</p>
        </div>
    </div>
    <div class="cart-footer">
        <div class="cart-total">
            <span>Total estimado</span>
            <span id="cartTotal">Bs. 0.00</span>
        </div>
        <button class="order-btn" id="orderBtn" onclick="openOrderModal()" disabled>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 22C6.486 22 2 17.514 2 12S6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>
            Enviar pedido por WhatsApp
        </button>
    </div>
</div>

<!-- MODAL DATOS DEL CLIENTE -->
<div class="modal-overlay" id="orderModal">
    <div class="modal">
        <div class="modal-head">
            <h3>📋 Completa tu pedido</h3>
            <p>Necesitamos tus datos para procesar el pedido correctamente</p>
        </div>
        <div class="modal-body">
            <div class="form-note">
                ⚡ Al hacer clic en <strong>"Enviar por WhatsApp"</strong>, serás redirigido a una conversación donde podrás confirmar tu pedido.
            </div>
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" id="clientNombre" placeholder="Tu nombre y apellido" required>
            </div>
            <div class="form-group">
                <label>Carnet de Identidad (CI) *</label>
                <input type="text" id="clientCI" placeholder="Ej: 12345678" required>
            </div>
            <div class="form-group">
                <label>Teléfono / WhatsApp *</label>
                <input type="tel" id="clientTel" placeholder="Ej: 70000000" required>
            </div>
            <div class="form-group">
                <label>Departamento</label>
                <select id="clientDepto">
                    <option value="">Seleccionar...</option>
                    <option>La Paz</option>
                    <option>Cochabamba</option>
                    <option>Santa Cruz</option>
                    <option>Oruro</option>
                    <option>Potosí</option>
                    <option>Sucre / Chuquisaca</option>
                    <option>Tarija</option>
                    <option>Beni</option>
                    <option>Pando</option>
                </select>
            </div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeOrderModal()">Cancelar</button>
                <button class="btn-whatsapp" onclick="sendToWhatsApp()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
                    Enviar por WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
    // ── ESTADO DEL CARRITO ──
    let cart = {};

    function addToCart(id, nombre, precio, categoria) {
        if (cart[id]) {
            cart[id].qty++;
        } else {
            cart[id] = { id, nombre, precio, categoria, qty: 1 };
        }
        renderCart();
        showToast('✓ ' + nombre + ' añadido');
    }

    function changeQty(id, delta) {
        if (!cart[id]) return;
        cart[id].qty += delta;
        if (cart[id].qty <= 0) delete cart[id];
        renderCart();
    }

    function removeItem(id) {
        delete cart[id];
        renderCart();
    }

    function renderCart() {
        const items = Object.values(cart);
        const count = items.reduce((s, i) => s + i.qty, 0);
        const total = items.reduce((s, i) => s + i.qty * i.precio, 0);

        document.getElementById('cartCount').textContent = count;
        document.getElementById('cartTotal').textContent = 'Bs. ' + total.toFixed(2);
        document.getElementById('orderBtn').disabled = items.length === 0;

        const container = document.getElementById('cartItems');
        if (items.length === 0) {
            container.innerHTML = `
                <div class="cart-empty">
                    <span>🛒</span>
                    <p>Tu pedido está vacío.<br>Agrega productos del catálogo.</p>
                </div>`;
            return;
        }

        container.innerHTML = items.map(item => `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.nombre}</div>
                    <div class="cart-item-price">Bs. ${(item.precio * item.qty).toFixed(2)}</div>
                    <div class="cart-item-controls">
                        <button class="qty-btn" onclick="changeQty(${item.id}, -1)">−</button>
                        <span class="qty-num">${item.qty}</span>
                        <button class="qty-btn" onclick="changeQty(${item.id}, 1)">+</button>
                    </div>
                </div>
                <button class="remove-item" onclick="removeItem(${item.id})" title="Quitar">🗑</button>
            </div>
        `).join('');
    }

    // ── PANEL CARRITO ──
    function toggleCart() {
        document.getElementById('cartPanel').classList.toggle('open');
        document.getElementById('cartOverlay').classList.toggle('open');
    }

    // ── MODAL PEDIDO ──
    function openOrderModal() {
        if (Object.keys(cart).length === 0) return;
        toggleCart();
        document.getElementById('orderModal').classList.add('open');
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.remove('open');
    }

    function sendToWhatsApp() {
        const nombre = document.getElementById('clientNombre').value.trim();
        const ci     = document.getElementById('clientCI').value.trim();
        const tel    = document.getElementById('clientTel').value.trim();
        const depto  = document.getElementById('clientDepto').value;

        if (!nombre || !ci || !tel) {
            showToast('⚠️ Completa los campos requeridos');
            return;
        }

        const items  = Object.values(cart);
        const total  = items.reduce((s, i) => s + i.qty * i.precio, 0);

        const detalle = items.map(i =>
            `  • ${i.qty}x ${i.nombre} — Bs. ${(i.qty * i.precio).toFixed(2)}`
        ).join('\n');

        const msg = `🥃 *PEDIDO — {{ $sucursal->nombre }}*\n\n` +
            `👤 *Cliente:* ${nombre}\n` +
            `🪪 *CI:* ${ci}\n` +
            `📱 *Teléfono:* ${tel}\n` +
            (depto ? `📍 *Departamento:* ${depto}\n` : '') +
            `\n🛒 *Detalle del pedido:*\n${detalle}\n\n` +
            `💰 *Total estimado: Bs. ${total.toFixed(2)}*\n\n` +
            `_Pedido realizado desde el catálogo en línea_`;

        const phone = '{{ $whatsapp }}';
        const url   = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;

        window.open(url, '_blank');
        closeOrderModal();
        cart = {};
        renderCart();
        showToast('✅ Redirigiendo a WhatsApp...');
    }

    // ── FILTROS ──
    function filterCat(catId, btn) {
        document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');

        document.querySelectorAll('.cat-group').forEach(g => {
            g.style.display = (catId === 'all' || g.dataset.catGroup === catId) ? '' : 'none';
        });

        document.querySelectorAll('.product-card').forEach(c => {
            c.style.display = (catId === 'all' || c.dataset.cat === catId) ? '' : 'none';
        });
    }

    function filterSearch(query) {
        query = query.toLowerCase().trim();
        document.querySelectorAll('.product-card').forEach(c => {
            const match = c.dataset.nombre.includes(query) || c.dataset.desc.includes(query);
            c.style.display = match ? '' : 'none';
        });
        // Ocultar grupos vacíos
        document.querySelectorAll('.cat-group').forEach(g => {
            const visible = Array.from(g.querySelectorAll('.product-card')).some(c => c.style.display !== 'none');
            g.style.display = visible ? '' : 'none';
        });
    }

    // ── TOAST ──
    function showToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 2500);
    }
</script>
</body>
</html>
