<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $producto->nombre }} · Elixir Dorado</title>
    <meta name="description" content="Compra {{ $producto->nombre }} por Bs. {{ number_format($producto->precio_venta, 0) }} en Elixir Dorado. {{ $producto->categoria_nombre ?? 'Bebidas premium' }} con envío rápido en Bolivia.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $producto->nombre }} · Elixir Dorado">
    <meta property="og:description" content="Bs. {{ number_format($producto->precio_venta, 0) }} — {{ $producto->categoria_nombre ?? 'Bebidas Premium' }} — Elixir Dorado">
    @if(!empty($producto->imagen))
    <meta property="og:image" content="{{ asset('storage/' . $producto->imagen) }}">
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="Elixir Dorado">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $producto->nombre }} · Elixir Dorado">
    <meta name="twitter:description" content="Bs. {{ number_format($producto->precio_venta, 0) }} en Elixir Dorado">
    @if(!empty($producto->imagen))
    <meta name="twitter:image" content="{{ asset('storage/' . $producto->imagen) }}">
    @endif

    {{-- JSON-LD --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Product",
        "name": "{{ addslashes($producto->nombre) }}",
        "description": "{{ addslashes($producto->categoria_nombre ?? 'Bebida premium') }} disponible en Elixir Dorado",
        @if(!empty($producto->imagen))
        "image": "{{ asset('storage/' . $producto->imagen) }}",
        @endif
        "brand": { "@@type": "Brand", "name": "Elixir Dorado" },
        "offers": {
            "@@type": "Offer",
            "priceCurrency": "BOB",
            "price": "{{ $producto->precio_venta }}",
            "availability": "https://schema.org/InStock",
            "url": "{{ $canonicalUrl }}"
        }
    }
    </script>

    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🥃</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #d4a574; --gold-light: #e8c79a; --gold-deep: #a07a45;
            --dark: #0d0d12; --dark-soft: #1a1a23; --cream: #f5ecd9;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: 'Poppins', system-ui, sans-serif; background: var(--dark); color: var(--cream); overflow-x: hidden; min-height: 100vh; }
        h1, h2, .serif { font-family: 'Playfair Display', Georgia, serif; }

        .glass { background: rgba(255,255,255,0.04); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid rgba(212,165,116,0.15); }
        .gold-text { background: linear-gradient(135deg,#f5d896 0%,var(--gold) 50%,var(--gold-deep) 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }

        /* ── HEADER ── */
        .topbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 50;
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 20px;
            background: rgba(13,13,18,0.85); backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(212,165,116,0.15);
        }
        .back-link {
            display: inline-flex; align-items: center; gap: 8px;
            color: rgba(245,236,217,0.7); text-decoration: none; font-size: 14px; font-weight: 500;
            transition: color .2s;
        }
        .back-link:hover { color: var(--gold-light); }
        .logo-text { font-family: 'Playfair Display', serif; font-size: 18px; font-weight: 700; }

        /* ── PRODUCT LAYOUT ── */
        .detail-wrapper {
            max-width: 1000px; margin: 0 auto; padding: 100px 20px 140px;
            display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: start;
        }
        @media (max-width: 700px) {
            .detail-wrapper { grid-template-columns: 1fr; gap: 28px; padding: 88px 16px 140px; }
        }

        /* ── IMAGE ── */
        .product-img-box {
            aspect-ratio: 1/1; border-radius: 24px; overflow: hidden;
            background: radial-gradient(circle at 30% 30%, #2a1d12, #11100a);
            display: flex; align-items: center; justify-content: center; font-size: 120px;
            border: 1px solid rgba(212,165,116,0.15);
            box-shadow: 0 24px 70px rgba(0,0,0,0.6);
        }
        .product-img-box img { width: 100%; height: 100%; object-fit: cover; }

        /* ── INFO ── */
        .product-cat-badge {
            display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
            text-transform: uppercase; color: rgba(212,165,116,0.8);
            background: rgba(212,165,116,0.1); border: 1px solid rgba(212,165,116,0.2);
            padding: 4px 12px; border-radius: 999px; margin-bottom: 12px;
        }
        .product-title { font-size: clamp(22px,4vw,38px); font-weight: 900; line-height: 1.15; margin: 0 0 16px; }
        .product-price-big {
            font-family: 'Playfair Display', serif; font-size: clamp(28px,5vw,44px); font-weight: 900;
            background: linear-gradient(135deg,#f5d896,var(--gold)); -webkit-background-clip: text; background-clip: text; color: transparent;
            margin-bottom: 12px;
        }
        .stock-pill {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 999px;
            background: rgba(34,197,94,0.15); color: #86efac; border: 1px solid rgba(134,239,172,0.25);
            margin-bottom: 24px;
        }
        .stock-pill.low { background: rgba(251,191,36,0.15); color: #fcd34d; border-color: rgba(252,211,77,0.25); }

        /* ── QTY SELECTOR ── */
        .qty-row { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
        .qty-label { font-size: 13px; color: rgba(245,236,217,0.6); font-weight: 500; }
        .qty-ctrl {
            display: flex; align-items: center; border: 1px solid rgba(212,165,116,0.3);
            border-radius: 14px; overflow: hidden;
        }
        .qty-btn-ctrl {
            background: rgba(212,165,116,0.1); border: none; color: var(--gold-light);
            width: 42px; height: 42px; font-size: 18px; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background .15s;
        }
        .qty-btn-ctrl:hover { background: rgba(212,165,116,0.25); }
        .qty-btn-ctrl:disabled { opacity: .35; cursor: not-allowed; }
        .qty-num-ctrl {
            min-width: 48px; text-align: center; font-size: 16px; font-weight: 700;
            color: var(--cream); background: rgba(255,255,255,0.04); line-height: 42px;
        }

        /* ── BUTTONS ── */
        .btn-add-cart {
            width: 100%; padding: 14px; border-radius: 14px; border: none; cursor: pointer;
            font-weight: 700; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px;
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 50%, var(--gold-deep) 100%);
            color: #1a0f00;
            box-shadow: 0 8px 30px rgba(212,165,116,0.35);
            transition: transform .2s ease, box-shadow .2s ease;
            margin-bottom: 12px;
        }
        .btn-add-cart:hover { transform: translateY(-2px); box-shadow: 0 14px 40px rgba(212,165,116,0.5); }
        .btn-add-cart.added {
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: #052e16; box-shadow: 0 8px 30px rgba(34,197,94,0.35);
        }
        .btn-back-catalog {
            width: 100%; padding: 12px; border-radius: 14px;
            border: 1px solid rgba(212,165,116,0.3); background: rgba(212,165,116,0.06);
            color: var(--gold-light); font-size: 14px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            text-decoration: none; transition: background .2s, border-color .2s;
        }
        .btn-back-catalog:hover { background: rgba(212,165,116,0.14); border-color: var(--gold); }
        .cart-added-notice {
            text-align: center; font-size: 13px; color: rgba(245,236,217,0.5); margin-top: 8px;
        }

        /* ── SHARE ── */
        .share-divider {
            display: flex; align-items: center; gap: 10px; margin: 20px 0 12px;
            color: rgba(245,236,217,0.35); font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
        }
        .share-divider::before, .share-divider::after {
            content: ''; flex: 1; height: 1px; background: rgba(212,165,116,0.15);
        }
        .share-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .share-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            flex: 1; min-width: 0; padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.04); color: rgba(245,236,217,0.75);
            font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; white-space: nowrap;
            transition: all .2s ease; font-family: inherit;
        }
        .share-btn:hover { transform: translateY(-2px); }
        .share-btn-wa    { border-color: rgba(37,211,102,0.35); color: #86efac; }
        .share-btn-wa:hover { background: rgba(37,211,102,0.15); border-color: rgba(37,211,102,0.6); box-shadow: 0 4px 16px rgba(37,211,102,0.2); }
        .share-btn-gmail { border-color: rgba(234,67,53,0.35); color: #fca5a5; }
        .share-btn-gmail:hover { background: rgba(234,67,53,0.15); border-color: rgba(234,67,53,0.6); box-shadow: 0 4px 16px rgba(234,67,53,0.2); }
        .share-btn-copy  { border-color: rgba(212,165,116,0.35); color: var(--gold-light); }
        .share-btn-copy:hover { background: rgba(212,165,116,0.15); border-color: var(--gold); box-shadow: 0 4px 16px rgba(212,165,116,0.2); }
        .share-btn-more  { border-color: rgba(139,92,246,0.35); color: #c4b5fd; }
        .share-btn-more:hover { background: rgba(139,92,246,0.15); border-color: rgba(139,92,246,0.6); box-shadow: 0 4px 16px rgba(139,92,246,0.2); }

        /* ── CARRITO FLOTANTE ── */
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
        .carrito-items::-webkit-scrollbar-thumb { background: rgba(212,165,116,0.2); border-radius: 2px; }
        .carrito-item { display: flex; align-items: center; gap: 10px; padding: 8px 14px; transition: background .15s; }
        .carrito-item:hover { background: rgba(255,255,255,0.03); }
        .carrito-item-info { flex: 1; min-width: 0; }
        .carrito-item-name { font-size: 12px; font-weight: 500; line-height: 1.35; color: var(--cream); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .carrito-item-sub  { font-size: 11px; color: rgba(245,236,217,0.45); margin-top: 1px; }
        .carrito-item-sub strong { color: var(--gold-light); font-weight: 600; }
        .qty-controls { display: flex; align-items: center; border: 1px solid rgba(212,165,116,0.25); border-radius: 8px; overflow: hidden; flex-shrink: 0; }
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
            background: linear-gradient(135deg,#25d366,#1ea855); color: white;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 20px rgba(37,211,102,0.35); transition: transform .2s ease, box-shadow .2s ease;
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
            box-shadow: 0 8px 28px rgba(37,211,102,0.55); transition: transform .25s ease;
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

        @keyframes pop { 0% { transform:scale(1); } 40% { transform:scale(1.15); } 100% { transform:scale(1); } }
        .pop { animation: pop .35s ease; }

        @media (max-width: 400px) {
            #carrito-panel { right: 8px; left: 8px; width: auto; }
        }
    </style>
</head>
<body>

{{-- ── TOPBAR ── --}}
<header class="topbar">
    <a href="/" class="back-link">
        <i class="fas fa-arrow-left text-xs"></i>
        <span class="logo-text gold-text">Elixir Dorado</span>
    </a>
    <button onclick="toggleCarrito()" style="background:none;border:none;cursor:pointer;position:relative;padding:6px;"
            title="Mi pedido" aria-label="Ver carrito">
        <i class="fas fa-shopping-basket text-xl" style="color:var(--gold-light)"></i>
        <span id="topbar-badge"
              style="position:absolute;top:0;right:0;background:#ef4444;color:#fff;font-size:10px;font-weight:800;
                     min-width:18px;height:18px;border-radius:999px;display:none;align-items:center;justify-content:center;
                     padding:0 3px;border:2px solid var(--dark);">0</span>
    </button>
</header>

{{-- ── PRODUCT DETAIL ── --}}
<main>
    <div class="detail-wrapper">

        {{-- IMAGE --}}
        <div>
            <div class="product-img-box">
                @if(!empty($producto->imagen))
                    <img src="{{ asset('storage/'.$producto->imagen) }}" alt="{{ $producto->nombre }}">
                @else
                    <span style="opacity:.7">🍾</span>
                @endif
            </div>
        </div>

        {{-- INFO --}}
        <div>
            @if(!empty($producto->categoria_nombre))
                <div class="product-cat-badge">{{ $producto->categoria_nombre }}</div>
            @endif

            <h1 class="product-title gold-text">{{ $producto->nombre }}</h1>

            <div class="product-price-big">Bs. {{ number_format($producto->precio_venta, 0) }}</div>

            @if($producto->stock_actual <= 5)
                <div class="stock-pill low">
                    <i class="fas fa-exclamation-circle text-xs"></i>
                    {{ $producto->stock_actual }} {{ $producto->stock_actual === 1 ? 'unidad disponible' : 'unidades disponibles' }}
                </div>
            @else
                <div class="stock-pill">
                    <i class="fas fa-check-circle text-xs"></i>
                    En stock
                </div>
            @endif

            {{-- QTY SELECTOR --}}
            <div class="qty-row">
                <span class="qty-label">Cantidad:</span>
                <div class="qty-ctrl">
                    <button class="qty-btn-ctrl" id="btn-minus" onclick="decrementQty()">−</button>
                    <div class="qty-num-ctrl" id="qty-display">1</div>
                    <button class="qty-btn-ctrl" id="btn-plus" onclick="incrementQty()">+</button>
                </div>
            </div>

            {{-- ADD TO CART --}}
            <button class="btn-add-cart" id="btn-add-cart" onclick="agregarDetalle()">
                <i class="fas fa-shopping-basket"></i>
                Agregar al carrito
            </button>

            <a href="/" class="btn-back-catalog">
                <i class="fas fa-store text-xs"></i>
                Ver catálogo completo
            </a>

            {{-- ── SHARE ── --}}
            <div class="share-divider">Compartir</div>
            <div class="share-row">
                <a href="#" onclick="shareWhatsapp(); return false;" class="share-btn share-btn-wa" title="Compartir por WhatsApp">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="#" onclick="shareGmail(); return false;" class="share-btn share-btn-gmail" title="Enviar por Gmail">
                    <i class="fas fa-envelope"></i> Gmail
                </a>
                <button onclick="copyLink()" class="share-btn share-btn-copy" id="btn-copy-link" title="Copiar enlace">
                    <i class="fas fa-link"></i> Copiar
                </button>
                <button onclick="nativeShare()" class="share-btn share-btn-more" id="btn-native-share" title="Más opciones" style="display:none">
                    <i class="fas fa-share-nodes"></i> Más
                </button>
            </div>

            <p class="cart-added-notice" id="added-notice" style="display:none">
                <i class="fas fa-check-circle" style="color:#86efac"></i>
                Producto en tu carrito · Pulsa <i class="fab fa-whatsapp" style="color:#25d366"></i> para finalizar el pedido
            </p>
        </div>
    </div>
</main>

{{-- ── CARRITO FLOTANTE ── --}}
<div id="carrito-panel">
    <div class="carrito-header">
        <span><i class="fas fa-shopping-basket text-amber-400 mr-2"></i>Mi pedido</span>
        <button onclick="cerrarCarrito()" title="Cerrar"><i class="fas fa-times"></i></button>
    </div>
    <div id="carrito-items" class="carrito-items">
        <div class="carrito-vacío" id="carrito-vacio">
            <i class="fas fa-shopping-basket"></i>
            Aún no has agregado productos.
        </div>
    </div>
    <div id="carrito-total-row" class="carrito-total-row" style="display:none">
        <span class="carrito-total-label">Total estimado</span>
        <span class="carrito-total-valor" id="carrito-total-valor">Bs. 0</span>
    </div>
    <div class="carrito-footer" id="carrito-footer" style="display:none">
        <button class="btn-wa-enviar" onclick="enviarPorWhatsApp()">
            <i class="fab fa-whatsapp text-xl"></i>
            Enviar pedido por WhatsApp
        </button>
    </div>
</div>

{{-- ── WHATSAPP FAB ── --}}
<button id="wa-fab" class="pulse" onclick="toggleCarrito()" title="Mi pedido" aria-label="Carrito">
    <i class="fab fa-whatsapp"></i>
    <span id="wa-badge"></span>
</button>

<script>
// ── Datos del producto ──────────────────────────────────────────────
const PRODUCTO_ID     = {{ $producto->id }};
const PRODUCTO_NOMBRE = '{{ addslashes($producto->nombre) }}';
const PRODUCTO_PRECIO = {{ $producto->precio_venta }};
const STOCK_MAX       = {{ $producto->stock_actual }};
const PRODUCTO_URL    = '{{ $canonicalUrl }}';

// ── Carrito (localStorage) ──────────────────────────────────────────
let carrito = {};
try { carrito = JSON.parse(localStorage.getItem('elixir_carrito') || '{}'); } catch(e) {}

let carritoAbierto = false;
let qtyDetalle = 1;

function saveCarrito() {
    try { localStorage.setItem('elixir_carrito', JSON.stringify(carrito)); } catch(e) {}
}

// ── Qty selector ────────────────────────────────────────────────────
function incrementQty() {
    if (qtyDetalle < STOCK_MAX) {
        qtyDetalle++;
        document.getElementById('qty-display').textContent = qtyDetalle;
        document.getElementById('btn-minus').disabled = false;
        if (qtyDetalle >= STOCK_MAX) document.getElementById('btn-plus').disabled = true;
    }
}
function decrementQty() {
    if (qtyDetalle > 1) {
        qtyDetalle--;
        document.getElementById('qty-display').textContent = qtyDetalle;
        document.getElementById('btn-plus').disabled = false;
        if (qtyDetalle <= 1) document.getElementById('btn-minus').disabled = true;
    }
}

// ── Agregar al carrito ───────────────────────────────────────────────
function agregarDetalle() {
    const id = String(PRODUCTO_ID);
    if (carrito[id]) {
        carrito[id].qty += qtyDetalle;
    } else {
        carrito[id] = { nombre: PRODUCTO_NOMBRE, precio: PRODUCTO_PRECIO, qty: qtyDetalle };
    }
    saveCarrito();
    renderCarrito();
    actualizarBadge();
    abrirCarrito();
    animarFab();

    const btn = document.getElementById('btn-add-cart');
    btn.classList.add('added');
    btn.innerHTML = '<i class="fas fa-check"></i> ¡Agregado!';
    document.getElementById('added-notice').style.display = '';
    setTimeout(() => {
        btn.classList.remove('added');
        btn.innerHTML = '<i class="fas fa-shopping-basket"></i> Agregar al carrito';
    }, 2500);
}

// ── Cart panel ───────────────────────────────────────────────────────
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
        const item     = carrito[id];
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

function cambiarCantidad(id, delta) {
    if (!carrito[id]) return;
    carrito[id].qty += delta;
    if (carrito[id].qty <= 0) delete carrito[id];
    saveCarrito();
    renderCarrito();
    actualizarBadge();
}

function quitarDelCarrito(id) {
    delete carrito[id];
    saveCarrito();
    renderCarrito();
    actualizarBadge();
}

function actualizarBadge() {
    const total  = Object.values(carrito).reduce((s,i) => s + i.qty, 0);
    const badge  = document.getElementById('wa-badge');
    const fab    = document.getElementById('wa-fab');
    const tbadge = document.getElementById('topbar-badge');
    badge.textContent = total > 0 ? (total > 99 ? '99+' : total) : '';
    badge.classList.toggle('show', total > 0);
    fab.classList.toggle('pulse', total === 0);
    if (tbadge) {
        tbadge.textContent = total > 99 ? '99+' : total;
        tbadge.style.display = total > 0 ? 'flex' : 'none';
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
    if (carritoAbierto) cerrarCarrito(); else abrirCarrito();
}
function animarFab() {
    const fab = document.getElementById('wa-fab');
    fab.classList.add('pop');
    fab.addEventListener('animationend', () => fab.classList.remove('pop'), { once: true });
}

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

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Compartir ────────────────────────────────────────────────────────
function shareWhatsapp() {
    const text = `🥃 *${PRODUCTO_NOMBRE}*\nBs. ${PRODUCTO_PRECIO.toLocaleString('es-BO')} — Elixir Dorado\n\n${PRODUCTO_URL}`;
    window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank', 'noopener');
}

function shareGmail() {
    const subject = `${PRODUCTO_NOMBRE} · Elixir Dorado`;
    const body    = `Te comparto este producto:\n\n${PRODUCTO_NOMBRE}\nPrecio: Bs. ${PRODUCTO_PRECIO.toLocaleString('es-BO')}\n\n${PRODUCTO_URL}`;
    window.open(
        'https://mail.google.com/mail/?view=cm&su=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body),
        '_blank', 'noopener'
    );
}

function copyLink() {
    const btn = document.getElementById('btn-copy-link');
    const restore = () => {
        btn.innerHTML = '<i class="fas fa-link"></i> Copiar';
        btn.classList.remove('share-btn-copied');
    };
    navigator.clipboard.writeText(PRODUCTO_URL).then(() => {
        btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
        btn.style.background    = 'rgba(34,197,94,0.2)';
        btn.style.borderColor   = 'rgba(34,197,94,0.5)';
        btn.style.color         = '#86efac';
        setTimeout(() => { btn.style.cssText = ''; restore(); }, 2200);
    }).catch(() => {
        // Fallback
        const tmp = document.createElement('textarea');
        tmp.value = PRODUCTO_URL; tmp.style.position = 'fixed'; tmp.style.opacity = '0';
        document.body.appendChild(tmp); tmp.select(); document.execCommand('copy');
        document.body.removeChild(tmp);
        btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
        setTimeout(restore, 2200);
    });
}

function nativeShare() {
    navigator.share({
        title: PRODUCTO_NOMBRE + ' · Elixir Dorado',
        text:  `${PRODUCTO_NOMBRE} — Bs. ${PRODUCTO_PRECIO.toLocaleString('es-BO')} 🥃`,
        url:   PRODUCTO_URL,
    }).catch(() => {});
}

// ── Init ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    renderCarrito();
    actualizarBadge();
    // Show "en carrito" notice if this product is already in cart
    if (carrito[String(PRODUCTO_ID)]) {
        document.getElementById('added-notice').style.display = '';
    }
    // Show native share button on devices that support it (mostly mobile)
    if (navigator.share) {
        document.getElementById('btn-native-share').style.display = 'inline-flex';
    }
    // Disable qty buttons at boundaries
    if (STOCK_MAX <= 1) document.getElementById('btn-plus').disabled = true;
    document.getElementById('btn-minus').disabled = true;
});

// Close cart on outside click
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
