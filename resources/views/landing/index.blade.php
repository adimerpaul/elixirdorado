<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Elixir Dorado · Licorería Premium en Bolivia</title>
    <meta name="description" content="Tu licorería de confianza. Whisky, ron, vodka, vinos y más. Pide por WhatsApp y recoge o recibe a domicilio.">
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
        body {
            margin: 0;
            font-family: 'Poppins', system-ui, sans-serif;
            background: var(--dark);
            color: var(--cream);
            overflow-x: hidden;
        }
        h1, h2, h3, .serif { font-family: 'Playfair Display', Georgia, serif; }

        /* ────── HERO ────── */
        .hero {
            position: relative; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: radial-gradient(ellipse at top, #2a1f15 0%, var(--dark) 60%);
            overflow: hidden;
        }
        .hero::before, .hero::after {
            content: ''; position: absolute; border-radius: 50%; filter: blur(80px); opacity: .25;
            animation: float 14s ease-in-out infinite;
        }
        .hero::before { width: 480px; height: 480px; background: var(--gold); top: -120px; left: -120px; }
        .hero::after  { width: 380px; height: 380px; background: #b96f3f; bottom: -100px; right: -100px; animation-delay: -5s; }
        @keyframes float {
            0%, 100% { transform: translate(0,0) scale(1); }
            50%      { transform: translate(40px,-40px) scale(1.08); }
        }

        .glass {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(212,165,116,0.15);
        }

        .gold-text {
            background: linear-gradient(135deg, #f5d896 0%, var(--gold) 50%, var(--gold-deep) 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }

        .btn-gold {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 14px 28px; border-radius: 999px; font-weight: 600;
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 50%, var(--gold-deep) 100%);
            color: #1a0f00; border: none; cursor: pointer;
            box-shadow: 0 10px 30px rgba(212,165,116,0.35);
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .btn-gold:hover { transform: translateY(-3px); box-shadow: 0 16px 40px rgba(212,165,116,0.5); }

        .btn-ghost {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 22px; border-radius: 999px; font-weight: 600;
            color: var(--cream); border: 1px solid rgba(212,165,116,0.4);
            background: rgba(212,165,116,0.08);
            transition: background .25s ease, border-color .25s ease;
        }
        .btn-ghost:hover { background: rgba(212,165,116,0.18); border-color: var(--gold); }

        /* ────── BOTÓN WHATSAPP FLOTANTE ────── */
        .whatsapp-fab {
            position: fixed; right: 24px; bottom: 24px; z-index: 60;
            width: 64px; height: 64px; border-radius: 50%;
            background: #25d366; color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; box-shadow: 0 8px 28px rgba(37,211,102,0.55);
            animation: pulse 2s ease-in-out infinite;
            transition: transform .25s ease;
        }
        .whatsapp-fab:hover { transform: scale(1.08); }
        @keyframes pulse {
            0%   { box-shadow: 0 8px 28px rgba(37,211,102,0.55), 0 0 0 0 rgba(37,211,102,0.5); }
            70%  { box-shadow: 0 8px 28px rgba(37,211,102,0.55), 0 0 0 22px rgba(37,211,102,0); }
            100% { box-shadow: 0 8px 28px rgba(37,211,102,0.55), 0 0 0 0 rgba(37,211,102,0); }
        }

        /* ────── PRODUCT CARD ────── */
        .product-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.04) 0%, rgba(212,165,116,0.06) 100%);
            border: 1px solid rgba(212,165,116,0.18);
            border-radius: 16px; padding: 18px; transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease;
            position: relative; overflow: hidden;
        }
        .product-card::after {
            content: ''; position: absolute; inset: -1px; border-radius: 16px;
            background: linear-gradient(135deg, transparent 50%, rgba(212,165,116,0.2) 100%);
            opacity: 0; transition: opacity .3s ease; pointer-events: none;
        }
        .product-card:hover { transform: translateY(-6px); border-color: var(--gold); box-shadow: 0 18px 40px rgba(0,0,0,0.5); }
        .product-card:hover::after { opacity: 1; }
        .product-img {
            width: 100%; aspect-ratio: 1/1; border-radius: 12px;
            background: radial-gradient(circle at 30% 30%, #3a2a1a, #15100a);
            display: flex; align-items: center; justify-content: center;
            font-size: 64px; margin-bottom: 14px; overflow: hidden;
        }
        .product-img img { width: 100%; height: 100%; object-fit: cover; }
        .badge-stock {
            display: inline-block; padding: 3px 10px; border-radius: 999px;
            font-size: 10px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;
            background: rgba(34,197,94,0.18); color: #86efac; border: 1px solid rgba(134,239,172,0.3);
        }

        /* ────── SECTION DECOR ────── */
        .section-title {
            font-size: clamp(28px, 4vw, 44px); font-weight: 900;
            letter-spacing: -0.02em; line-height: 1.1; margin: 0 0 8px 0;
        }
        .section-subtitle {
            color: rgba(245,236,217,0.65); max-width: 600px; margin: 0 auto;
        }
        .divider-gold {
            width: 60px; height: 3px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
            margin: 14px auto 24px;
        }

        /* ────── FORM ────── */
        .input {
            width: 100%; padding: 12px 16px; border-radius: 10px;
            background: rgba(255,255,255,0.05); color: var(--cream);
            border: 1px solid rgba(212,165,116,0.2); font-size: 15px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .input:focus {
            outline: none; border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212,165,116,0.18);
        }
        .input::placeholder { color: rgba(245,236,217,0.35); }
        textarea.input { resize: vertical; min-height: 100px; }
        .label { font-size: 13px; font-weight: 600; color: rgba(245,236,217,0.85); margin-bottom: 6px; display: block; }
        .field-error { color: #fca5a5; font-size: 12px; margin-top: 4px; }
        .radio-card {
            border: 1px solid rgba(212,165,116,0.25); border-radius: 12px;
            padding: 14px; cursor: pointer; transition: all .2s ease; text-align: center;
            background: rgba(255,255,255,0.02);
        }
        .radio-card:hover { background: rgba(212,165,116,0.06); }
        .radio-card.active {
            border-color: var(--gold); background: rgba(212,165,116,0.12);
            box-shadow: 0 0 0 3px rgba(212,165,116,0.18);
        }
        .radio-card i { font-size: 22px; color: var(--gold); margin-bottom: 6px; display: block; }

        /* ────── REVEAL ON SCROLL ────── */
        .reveal { opacity: 0; transform: translateY(30px); transition: opacity .8s ease, transform .8s ease; }
        .reveal.in { opacity: 1; transform: translateY(0); }

        /* Navigation */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 50;
            padding: 16px 24px; transition: background .3s ease, backdrop-filter .3s ease;
        }
        .navbar.scrolled {
            background: rgba(13,13,18,0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(212,165,116,0.18);
        }

        /* Step cards */
        .step {
            text-align: center; padding: 24px;
            background: linear-gradient(180deg, rgba(255,255,255,0.03) 0%, transparent 100%);
            border: 1px solid rgba(212,165,116,0.12); border-radius: 16px;
            transition: transform .3s ease, border-color .3s ease;
        }
        .step:hover { transform: translateY(-4px); border-color: var(--gold); }
        .step-num {
            width: 50px; height: 50px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--gold), var(--gold-deep));
            color: #1a0f00; font-weight: 900; font-size: 22px;
            margin: 0 auto 14px; box-shadow: 0 8px 20px rgba(212,165,116,0.35);
        }

        /* Modal */
        .modal-bg {
            position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 100;
            display: none; align-items: center; justify-content: center; padding: 20px;
            backdrop-filter: blur(6px);
        }
        .modal-bg.show { display: flex; animation: fadeIn .25s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-content {
            background: var(--dark-soft); border: 1px solid rgba(212,165,116,0.25);
            border-radius: 16px; max-width: 520px; width: 100%;
            max-height: 92vh; overflow-y: auto;
            animation: slideUp .35s cubic-bezier(.25,.8,.3,1);
        }
        @keyframes slideUp { from { transform: translateY(40px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        /* Phone prefix */
        .tel-wrap { display: flex; align-items: stretch; }
        .tel-prefix {
            display: flex; align-items: center; padding: 0 14px;
            background: rgba(212,165,116,0.12); border: 1px solid rgba(212,165,116,0.25);
            border-right: none; border-radius: 10px 0 0 10px;
            font-weight: 600; color: var(--gold);
        }
        .tel-wrap .input { border-radius: 0 10px 10px 0; }

        /* Footer */
        footer { background: #06060a; padding: 40px 24px 80px; }
    </style>
</head>
<body>

{{-- ═══════════════ NAVBAR ═══════════════ --}}
<nav class="navbar" id="navbar">
    <div class="max-w-6xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-3xl">🥃</span>
            <div>
                <div class="serif text-xl font-bold gold-text leading-none">Elixir Dorado</div>
                <div class="text-[11px] text-amber-200/60 tracking-widest uppercase">Licorería Premium</div>
            </div>
        </div>
        <div class="hidden md:flex items-center gap-7 text-sm font-medium">
            <a href="#inicio" class="hover:text-amber-300 transition">Inicio</a>
            <a href="#productos" class="hover:text-amber-300 transition">Catálogo</a>
            <a href="#como-pedir" class="hover:text-amber-300 transition">Cómo pedir</a>
            <a href="#contacto" class="hover:text-amber-300 transition">Contacto</a>
        </div>
        <button onclick="abrirPedido()" class="btn-gold !py-2 !px-5 text-sm">
            <i class="fas fa-shopping-bag"></i> Pedir
        </button>
    </div>
</nav>

{{-- ═══════════════ HERO ═══════════════ --}}
<section class="hero" id="inicio">
    <div class="relative z-10 max-w-5xl mx-auto px-6 text-center reveal">
        <span class="inline-block px-4 py-2 rounded-full text-xs font-semibold tracking-widest uppercase glass mb-6">
            <i class="fas fa-star text-amber-400"></i>
            La mejor licorería de Bolivia
        </span>
        <h1 class="serif font-black leading-[1.05] mb-6" style="font-size: clamp(40px, 7vw, 80px);">
            Sabores que <span class="gold-text">despiertan</span><br>
            <span class="gold-text">los sentidos</span>
        </h1>
        <p class="text-base md:text-lg text-amber-100/70 max-w-2xl mx-auto mb-10">
            Whisky, ron, vodka, vinos, cervezas y más. Pide en línea y recoge en tienda
            o pídelo a domicilio en pocos minutos.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <button onclick="abrirPedido()" class="btn-gold">
                <i class="fab fa-whatsapp text-lg"></i> Hacer pedido por WhatsApp
            </button>
            <a href="#productos" class="btn-ghost">
                <i class="fas fa-wine-glass-alt"></i> Ver catálogo
            </a>
        </div>

        {{-- Mini stats --}}
        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto mt-16">
            <div class="reveal" style="transition-delay: .15s">
                <div class="serif text-3xl md:text-4xl font-bold gold-text">+500</div>
                <div class="text-xs uppercase tracking-widest text-amber-100/50 mt-1">Productos</div>
            </div>
            <div class="reveal" style="transition-delay: .3s">
                <div class="serif text-3xl md:text-4xl font-bold gold-text">30 min</div>
                <div class="text-xs uppercase tracking-widest text-amber-100/50 mt-1">Entrega</div>
            </div>
            <div class="reveal" style="transition-delay: .45s">
                <div class="serif text-3xl md:text-4xl font-bold gold-text">100%</div>
                <div class="text-xs uppercase tracking-widest text-amber-100/50 mt-1">Original</div>
            </div>
        </div>
    </div>

    {{-- Indicador scroll --}}
    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 text-amber-200/40 text-xs flex flex-col items-center gap-2 animate-bounce">
        <span>Desliza</span>
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

{{-- ═══════════════ FEATURES ═══════════════ --}}
<section class="py-20 px-6">
    <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-6">
        @php
            $features = [
                ['fa-truck-fast', 'Entrega rápida', 'Recibe tu pedido a domicilio en menos de 30 minutos.'],
                ['fa-shield-halved', 'Productos originales', 'Solo trabajamos con marcas certificadas y de origen confirmado.'],
                ['fa-tags', 'Mejores precios', 'Precios competitivos y promociones todos los días.'],
            ];
        @endphp
        @foreach($features as $i => $f)
            <div class="step reveal" style="transition-delay: {{ $i * .15 }}s">
                <i class="fas {{ $f[0] }} text-3xl mb-3" style="color: var(--gold);"></i>
                <h3 class="text-xl font-bold mb-2">{{ $f[1] }}</h3>
                <p class="text-sm text-amber-100/60">{{ $f[2] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ═══════════════ PRODUCTOS ═══════════════ --}}
<section class="py-20 px-6" id="productos">
    <div class="max-w-6xl mx-auto">
        <div class="text-center reveal">
            <span class="text-xs uppercase tracking-[4px] text-amber-300/70 font-semibold">Nuestra selección</span>
            <h2 class="section-title gold-text">Productos Destacados</h2>
            <div class="divider-gold"></div>
            <p class="section-subtitle">Una cuidada selección de bebidas para cada ocasión.</p>
        </div>

        @if($productos->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 mt-12">
                @foreach($productos as $i => $p)
                    <div class="product-card reveal" style="transition-delay: {{ ($i % 4) * .08 }}s">
                        <div class="product-img">
                            @if(!empty($p->imagen))
                                <img src="{{ asset('storage/' . $p->imagen) }}" alt="{{ $p->nombre }}" loading="lazy">
                            @else
                                <span>🍾</span>
                            @endif
                        </div>
                        <div class="text-[10px] uppercase tracking-widest text-amber-400/70 font-semibold mb-1">
                            {{ $p->categoria_nombre ?? 'Premium' }}
                        </div>
                        <h3 class="font-semibold text-base leading-tight mb-2 line-clamp-2">{{ $p->nombre }}</h3>
                        <div class="flex items-center justify-between mt-3">
                            <span class="serif text-xl font-bold gold-text">Bs. {{ number_format($p->precio_venta, 2) }}</span>
                            <span class="badge-stock">En stock</span>
                        </div>
                        <button onclick="abrirPedido('{{ addslashes($p->nombre) }}')"
                                class="w-full mt-4 py-2 rounded-lg font-semibold text-sm flex items-center justify-center gap-2"
                                style="background: rgba(212,165,116,0.15); color: var(--gold-light); border: 1px solid rgba(212,165,116,0.3);">
                            <i class="fab fa-whatsapp"></i> Pedir
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center mt-12 glass rounded-2xl py-12 px-6 reveal">
                <i class="fas fa-wine-bottle text-5xl text-amber-300/40 mb-4"></i>
                <p class="text-amber-100/70">El catálogo se mostrará aquí cuando haya productos disponibles.</p>
                <button onclick="abrirPedido()" class="btn-gold mt-6">
                    <i class="fab fa-whatsapp"></i> Pedir por WhatsApp
                </button>
            </div>
        @endif

        <div class="text-center mt-12 reveal">
            <button onclick="abrirPedido()" class="btn-gold">
                <i class="fas fa-shopping-basket"></i> Hacer mi pedido ahora
            </button>
        </div>
    </div>
</section>

{{-- ═══════════════ CÓMO PEDIR ═══════════════ --}}
<section class="py-20 px-6" id="como-pedir" style="background: linear-gradient(180deg, transparent 0%, rgba(212,165,116,0.04) 50%, transparent 100%);">
    <div class="max-w-5xl mx-auto">
        <div class="text-center reveal">
            <span class="text-xs uppercase tracking-[4px] text-amber-300/70 font-semibold">Tan fácil como</span>
            <h2 class="section-title gold-text">3 pasos para tu pedido</h2>
            <div class="divider-gold"></div>
        </div>
        <div class="grid md:grid-cols-3 gap-6 mt-12">
            @php
                $pasos = [
                    ['1', 'Llena el formulario', 'Cuéntanos qué quieres pedir y tus datos de contacto.'],
                    ['2', 'Confirma por WhatsApp', 'Te enviamos al chat con tu pedido pre-cargado.'],
                    ['3', 'Recibe o recoge', 'Llevamos tu pedido a casa o lo tienes listo en tienda.'],
                ];
            @endphp
            @foreach($pasos as $i => $p)
                <div class="step reveal" style="transition-delay: {{ $i * .15 }}s">
                    <div class="step-num">{{ $p[0] }}</div>
                    <h3 class="text-lg font-bold mb-2">{{ $p[1] }}</h3>
                    <p class="text-sm text-amber-100/60">{{ $p[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════ CONTACTO + CTA ═══════════════ --}}
<section class="py-20 px-6" id="contacto">
    <div class="max-w-4xl mx-auto glass rounded-3xl p-10 md:p-14 text-center reveal">
        <h2 class="section-title gold-text">¿Listo para tu pedido?</h2>
        <div class="divider-gold"></div>
        <p class="section-subtitle">Pide ahora por WhatsApp y recibe atención personalizada al instante.</p>
        <div class="flex flex-wrap justify-center gap-4 mt-8">
            <button onclick="abrirPedido()" class="btn-gold">
                <i class="fab fa-whatsapp text-lg"></i> Pedir por WhatsApp
            </button>
            <a href="https://wa.me/59168289548" target="_blank" rel="noopener" class="btn-ghost">
                <i class="fas fa-phone-alt"></i> +591 6 828 9548
            </a>
        </div>
        @if($sucursal)
            <div class="mt-10 grid sm:grid-cols-3 gap-6 text-sm">
                <div>
                    <i class="fas fa-store text-amber-400 mb-1"></i>
                    <div class="font-semibold">{{ $sucursal->nombre }}</div>
                </div>
                @if($sucursal->direccion)
                    <div>
                        <i class="fas fa-map-marker-alt text-amber-400 mb-1"></i>
                        <div class="font-semibold">{{ $sucursal->direccion }}</div>
                    </div>
                @endif
                @if($sucursal->telefono)
                    <div>
                        <i class="fas fa-phone text-amber-400 mb-1"></i>
                        <div class="font-semibold">{{ $sucursal->telefono }}</div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>

{{-- ═══════════════ FOOTER ═══════════════ --}}
<footer>
    <div class="max-w-6xl mx-auto text-center text-amber-100/40 text-sm">
        <div class="serif text-2xl gold-text font-bold mb-2">🥃 Elixir Dorado</div>
        <p class="mb-3">Licorería premium · Bolivia</p>
        <p class="text-xs">© {{ now()->year }} Elixir Dorado. Bebe responsablemente. Prohibida la venta a menores de 18 años.</p>
    </div>
</footer>

{{-- ═══════════════ BOTÓN WHATSAPP FLOTANTE ═══════════════ --}}
<button onclick="abrirPedido()" class="whatsapp-fab" title="Pedir por WhatsApp" aria-label="Pedir por WhatsApp">
    <i class="fab fa-whatsapp"></i>
</button>

{{-- ═══════════════ MODAL PEDIDO ═══════════════ --}}
<div class="modal-bg" id="modal-pedido" onclick="if(event.target===this) cerrarPedido()">
    <div class="modal-content p-6 md:p-8">
        <div class="flex items-start justify-between mb-5">
            <div>
                <h3 class="serif text-2xl font-bold gold-text">Hacer mi pedido</h3>
                <p class="text-xs text-amber-100/60 mt-1">Completa tus datos y serás dirigido a WhatsApp.</p>
            </div>
            <button onclick="cerrarPedido()" class="text-amber-200/60 hover:text-amber-100 text-xl"><i class="fas fa-times"></i></button>
        </div>

        <form id="form-pedido" onsubmit="enviarPedido(event)" novalidate>
            @csrf

            <div class="mb-4">
                <label class="label">Nombre completo</label>
                <input type="text" name="nombre" id="f-nombre" required maxlength="100" class="input" placeholder="Ej: María Pérez">
                <div class="field-error hidden" id="err-nombre"></div>
            </div>

            <div class="mb-4">
                <label class="label">Teléfono celular (Bolivia)</label>
                <div class="tel-wrap">
                    <span class="tel-prefix">+591</span>
                    <input type="tel" name="telefono" id="f-telefono" required
                           inputmode="numeric" pattern="[67][0-9]{7}" maxlength="8"
                           class="input" placeholder="6XXXXXXX o 7XXXXXXX">
                </div>
                <div class="text-[11px] text-amber-100/50 mt-1">8 dígitos. Debe empezar con 6 o 7.</div>
                <div class="field-error hidden" id="err-telefono"></div>
            </div>

            <div class="mb-4">
                <label class="label">Tipo de pedido</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="radio-card active" id="rc-recoger" onclick="selectTipo('recoger')">
                        <input type="radio" name="tipo" value="recoger" class="hidden" checked>
                        <i class="fas fa-store"></i>
                        <div class="font-semibold text-sm">Recoger en tienda</div>
                    </label>
                    <label class="radio-card" id="rc-llevar" onclick="selectTipo('llevar')">
                        <input type="radio" name="tipo" value="llevar" class="hidden">
                        <i class="fas fa-motorcycle"></i>
                        <div class="font-semibold text-sm">A domicilio</div>
                    </label>
                </div>
                <div class="field-error hidden" id="err-tipo"></div>
            </div>

            <div class="mb-4 hidden" id="campo-direccion">
                <label class="label">Dirección de entrega</label>
                <input type="text" name="direccion" id="f-direccion" maxlength="255" class="input" placeholder="Av. Principal #123, Zona Sur">
                <div class="field-error hidden" id="err-direccion"></div>
            </div>

            <div class="mb-5">
                <label class="label">¿Qué quieres pedir?</label>
                <textarea name="pedido" id="f-pedido" required maxlength="1000" class="input"
                          placeholder="Ej:&#10;- 1 botella de Whisky Johnnie Walker&#10;- 2 cervezas Paceña"></textarea>
                <div class="field-error hidden" id="err-pedido"></div>
            </div>

            <button type="submit" class="btn-gold w-full justify-center text-base" id="btn-enviar">
                <i class="fab fa-whatsapp text-xl"></i> Continuar a WhatsApp
            </button>
            <p class="text-[11px] text-amber-100/40 text-center mt-3">
                Al enviar serás redirigido a WhatsApp con tu pedido pre-cargado.
            </p>
        </form>
    </div>
</div>

<script>
// ── Modal helpers ────────────────────────────────────────────────────
function abrirPedido(producto) {
    document.getElementById('modal-pedido').classList.add('show');
    document.body.style.overflow = 'hidden';
    if (producto) {
        const ta = document.getElementById('f-pedido');
        if (!ta.value.includes(producto)) {
            ta.value = (ta.value ? ta.value + '\n' : '') + '- ' + producto;
        }
    }
    setTimeout(() => document.getElementById('f-nombre').focus(), 80);
}
function cerrarPedido() {
    document.getElementById('modal-pedido').classList.remove('show');
    document.body.style.overflow = '';
}

// ── Tipo de pedido ───────────────────────────────────────────────────
function selectTipo(tipo) {
    document.querySelectorAll('input[name="tipo"]').forEach(r => r.checked = (r.value === tipo));
    document.getElementById('rc-recoger').classList.toggle('active', tipo === 'recoger');
    document.getElementById('rc-llevar').classList.toggle('active',  tipo === 'llevar');
    document.getElementById('campo-direccion').classList.toggle('hidden', tipo !== 'llevar');
    if (tipo === 'llevar') document.getElementById('f-direccion').setAttribute('required','required');
    else document.getElementById('f-direccion').removeAttribute('required');
}

// ── Restringir teléfono a solo dígitos y 8 chars ──────────────────────
const telInput = document.getElementById('f-telefono');
telInput.addEventListener('input', e => {
    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 8);
});

// ── Limpiar errores ──────────────────────────────────────────────────
function limpiarErrores() {
    ['nombre','telefono','tipo','direccion','pedido'].forEach(k => {
        const el = document.getElementById('err-' + k);
        if (el) { el.textContent = ''; el.classList.add('hidden'); }
    });
}
function mostrarError(campo, mensaje) {
    const el = document.getElementById('err-' + campo);
    if (el) { el.textContent = mensaje; el.classList.remove('hidden'); }
}

// ── Enviar pedido ────────────────────────────────────────────────────
let enviando = false;
async function enviarPedido(e) {
    e.preventDefault();
    if (enviando) return;
    limpiarErrores();

    const tel = telInput.value;
    // Validación rápida cliente: 8 dígitos, comienza con 6 o 7
    if (!/^[67]\d{7}$/.test(tel)) {
        mostrarError('telefono', 'Número inválido. Debe ser celular boliviano de 8 dígitos que empiece con 6 o 7.');
        telInput.focus();
        return;
    }

    const form = document.getElementById('form-pedido');
    const fd = new FormData(form);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const btn = document.getElementById('btn-enviar');

    enviando = true;
    btn.disabled = true;
    btn.dataset.original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

    try {
        const res = await fetch('{{ url('/pedido/whatsapp') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: fd,
        });

        // Si por alguna razón el servidor devuelve HTML (404, 419, 500), mostrar diagnóstico
        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            const txt = await res.text();
            console.error('Respuesta no-JSON', res.status, txt.slice(0, 300));
            mostrarError('pedido', 'Error ' + res.status + ' del servidor. Revisa la consola del navegador (F12) para más detalles.');
            return;
        }

        const data = await res.json().catch(() => ({}));

        if (res.status === 422 && data.errors) {
            Object.keys(data.errors).forEach(k => mostrarError(k, data.errors[k][0]));
        } else if (res.ok && data.ok && data.url) {
            // Abrir WhatsApp en nueva pestaña
            window.open(data.url, '_blank', 'noopener');
            cerrarPedido();
            form.reset();
            selectTipo('recoger');
        } else {
            console.error('Respuesta inesperada', res.status, data);
            mostrarError('pedido', (data.message || 'No se pudo procesar.') + ' (HTTP ' + res.status + ')');
        }
    } catch (err) {
        console.error(err);
        mostrarError('pedido', 'Error de conexión: ' + err.message);
    } finally {
        enviando = false;
        btn.disabled = false;
        btn.innerHTML = btn.dataset.original;
    }
}

// ── Reveal on scroll ─────────────────────────────────────────────────
const io = new IntersectionObserver(entries => {
    entries.forEach(en => { if (en.isIntersecting) en.target.classList.add('in'); });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(el => io.observe(el));

// ── Navbar al hacer scroll ───────────────────────────────────────────
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 30);
}, { passive: true });

// ── ESC cierra modal ─────────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') cerrarPedido();
});
</script>
</body>
</html>
