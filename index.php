<?php

/**
 * SAMRIDHI AGRO — Homepage
 * Core PHP + HTML5 + CSS3 + Vanilla JS
 * This file is written flat for now (per current task scope).
 * When wiring into the full app, the <head> block and the
 * top/bottom of <body> can be split into includes/header.php
 * and includes/footer.php without touching anything in between.
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Samridhi Agro — Growing Agriculture. Connecting Every Market.</title>
    <meta name="description" content="Samridhi Agro connects agricultural products with local shops and farmers, building a smarter, more efficient rural distribution network.">

    <!-- Fonts: Space Grotesk (display) + Inter (body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Noto+Sans+Devanagari:wght@500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- ============ SKIP LINK ============ -->
    <a href="#main" class="skip-link">Skip to content</a>

    <!-- ============ NAVBAR ============ -->
    <header class="navbar" id="navbar">
        <div class="navbar__inner container">
            <a href="index.php" class="brand" aria-label="Samridhi Agro home">
                <span class="brand__mark" aria-hidden="true">
                    <img src="assets/images/logo.png" alt="" width="36" height="36" class="brand__mark-img">
                </span>
                <span class="brand__text">SAMRIDHI<span class="brand__text--sub">AGRO</span></span>
            </a>

            <nav class="nav-links" id="navLinks" aria-label="Primary">
                <a href="#home">Home</a>
                <a href="#ecosystem">About</a>
                <a href="#products">Products</a>
                <a href="#network">Distribution</a>
                <a href="#network-map">Our Network</a>
                <a href="#footer">Contact</a>
            </nav>

            <div class="navbar__actions">
                <button class="btn btn--login" id="loginTrigger" type="button">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Login
                </button>
                <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false" aria-controls="navLinks">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <main id="main">

        <!-- ============ HERO ============ -->
        <section class="hero" id="home">
            <div class="hero__bg" aria-hidden="true">
                <div class="blob blob--1"></div>
                <div class="blob blob--2"></div>
                <svg class="hero__grid" width="100%" height="100%" aria-hidden="true">
                    <defs>
                        <pattern id="dotgrid" width="28" height="28" patternUnits="userSpaceOnUse">
                            <circle cx="1.5" cy="1.5" r="1.5" fill="rgba(20,83,45,0.10)" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#dotgrid)" />
                </svg>
            </div>

            <div class="container hero__inner">
                <div class="hero__content reveal" data-reveal>
                    <p class="eyebrow"><span class="eyebrow__dot"></span> Farm-to-Shop Distribution Network</p>
                    <h1 class="hero__title">Growing Agriculture.<br>Connecting Every Market.</h1>
                    <p class="hero__text">Samridhi Agro connects agricultural products with local shops and farmers, creating a smarter and more efficient rural distribution network.</p>

                    <div class="hero__ctas">
                        <a href="#ecosystem" class="btn btn--primary">Explore Platform</a>
                        <button class="btn btn--outline" data-open-login type="button">Login to Portal</button>
                    </div>

                    <ul class="trust-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 12l5 5L20 6" stroke="#16A34A" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg> Trusted Distribution Network</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 12l5 5L20 6" stroke="#16A34A" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg> Shop Friendly</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 12l5 5L20 6" stroke="#16A34A" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg> Farmer Focused</li>
                    </ul>
                </div>

                <!-- 3D-style agricultural composition -->
                <div class="hero__visual reveal" data-reveal data-reveal-delay="150">
                    <div class="scene" id="heroScene">

                        <div class="scene__ring scene__ring--1" aria-hidden="true"></div>
                        <div class="scene__ring scene__ring--2" aria-hidden="true"></div>

                        <!-- field + farmer composition, built from real image assets -->
                        <div class="scene__ground" style="background-image:url('assets/images/farm-field.png')" aria-hidden="true"></div>

                        <img class="scene__farmer-img" src="assets/images/farmer.png" alt="Indian farmer holding fresh agricultural produce">
                        <img class="scene__shop-img" src="assets/images/shop.png" alt="Small local agricultural shop">
                        <img class="scene__product-img" src="assets/images/agri-product.png" alt="Packaged agricultural product">

                        <!-- floating leaf shapes -->
                        <svg class="leaf leaf--a" viewBox="0 0 60 60" aria-hidden="true">
                            <path d="M5 55C5 25 25 5 55 5 55 35 35 55 5 55Z" fill="#22C55E" opacity="0.85" />
                        </svg>
                        <svg class="leaf leaf--b" viewBox="0 0 60 60" aria-hidden="true">
                            <path d="M5 55C5 25 25 5 55 5 55 35 35 55 5 55Z" fill="#65A30D" opacity="0.8" />
                        </svg>

                        <!-- floating stat cards -->
                        <div class="float-card float-card--1">
                            <span class="float-card__num">12K+</span>
                            <span class="float-card__label">Farmers</span>
                        </div>
                        <div class="float-card float-card--2">
                            <span class="float-card__num">850+</span>
                            <span class="float-card__label">Shops</span>
                        </div>
                        <div class="float-card float-card--3">
                            <span class="float-card__num">150+</span>
                            <span class="float-card__label">Products</span>
                        </div>
                        <div class="float-card float-card--4">
                            <span class="float-card__num">24/7</span>
                            <span class="float-card__label">Distribution</span>
                        </div>

                        <!-- connection lines -->
                        <svg class="scene__lines" viewBox="0 0 520 520" aria-hidden="true">
                            <path class="line-anim" d="M120 120 Q260 60 400 140" stroke="#16A34A" stroke-width="1.6" stroke-dasharray="5 7" fill="none" opacity="0.55" />
                            <path class="line-anim" d="M100 380 Q260 440 420 360" stroke="#16A34A" stroke-width="1.6" stroke-dasharray="5 7" fill="none" opacity="0.55" />
                        </svg>
                    </div>
                </div>
            </div>

            <svg class="hero__wave" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 40C240 90 480 0 720 20C960 40 1200 90 1440 40V90H0V40Z" fill="#F7FCF7" />
            </svg>
        </section>

        <!-- ============ LOGIN MODAL ============ -->
        <div class="login-modal" id="loginModal" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle" hidden>
            <div class="login-modal__backdrop" data-close-login></div>
            <div class="login-modal__panel">
                <button class="login-modal__close" data-close-login aria-label="Close login menu" type="button">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>

                <p class="eyebrow eyebrow--center"><span class="eyebrow__dot"></span> Portal Access</p>
                <h2 class="login-modal__title" id="loginModalTitle">Welcome to Samridhi Agro Portal</h2>
                <p class="login-modal__sub">Choose your access portal to continue.</p>

                <div class="portal-grid">
                    <a class="portal-card" href="admin/login.php">
                        <span class="portal-card__icon" style="--icon-a:#16A34A;--icon-b:#14532D;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                                <path d="M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3z" stroke="#fff" stroke-width="1.8" stroke-linejoin="round" />
                                <path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="portal-card__title">Admin Portal</span>
                        <span class="portal-card__desc">Manage the complete Samridhi Agro ecosystem.</span>
                        <span class="portal-card__cta">Admin Login <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg></span>
                    </a>

                    <a class="portal-card" href="staff/login.php">
                        <span class="portal-card__icon" style="--icon-a:#22C55E;--icon-b:#16A34A;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                                <rect x="4" y="8" width="16" height="12" rx="2" stroke="#fff" stroke-width="1.8" />
                                <path d="M9 8V6a3 3 0 0 1 6 0v2" stroke="#fff" stroke-width="1.8" />
                            </svg>
                        </span>
                        <span class="portal-card__title">Staff Portal</span>
                        <span class="portal-card__desc">Manage operations, orders and distribution.</span>
                        <span class="portal-card__cta">Staff Login <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg></span>
                    </a>

                    <a class="portal-card" href="agent/login.php">
                        <span class="portal-card__icon" style="--icon-a:#65A30D;--icon-b:#14532D;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                                <path d="M8 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM16 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" stroke="#fff" stroke-width="1.8" />
                                <path d="M3 20c0-3 2.5-5 5-5s5 2 5 5M11 20c0-3 2.5-5 5-5s5 2 5 5" stroke="#fff" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </span>
                        <span class="portal-card__title">Agent Portal</span>
                        <span class="portal-card__desc">Manage shops, orders and agricultural product distribution.</span>
                        <span class="portal-card__cta">Agent Login <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg></span>
                    </a>

                    <a class="portal-card" href="shop/login.php">
                        <span class="portal-card__icon" style="--icon-a:#EAB308;--icon-b:#B45309;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                                <path d="M4 9l1.5-5h13L20 9" stroke="#fff" stroke-width="1.8" stroke-linejoin="round" />
                                <path d="M4 9h16v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9z" stroke="#fff" stroke-width="1.8" stroke-linejoin="round" />
                                <path d="M9 20v-6h6v6" stroke="#fff" stroke-width="1.8" />
                            </svg>
                        </span>
                        <span class="portal-card__title">Shop Portal</span>
                        <span class="portal-card__desc">Order agricultural products directly from Samridhi Agro.</span>
                        <span class="portal-card__cta">Shop Login <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- ============ ECOSYSTEM ============ -->
        <section class="ecosystem" id="ecosystem">
            <div class="container">
                <div class="section-head reveal" data-reveal>
                    <p class="eyebrow"><span class="eyebrow__dot"></span> How It Works</p>
                    <h2>From Farm to Local Market</h2>
                    <p class="section-head__text">One connected chain — products move from Samridhi Agro through agents into local shops, and finally into farmers' hands.</p>
                </div>

                <div class="flow">
                    <svg class="flow__line" viewBox="0 0 1000 140" preserveAspectRatio="none" aria-hidden="true">
                        <path id="flowPath" d="M60 70 C260 10, 380 130, 500 70 S 740 10, 940 70" stroke="#16A34A" stroke-width="2" stroke-dasharray="2 10" fill="none" stroke-linecap="round" />
                    </svg>

                    <div class="flow__steps">
                        <div class="flow-step reveal" data-reveal>
                            <span class="flow-step__num">01</span>
                            <span class="flow-step__icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 21h18M5 21V9l7-6 7 6v12M9 21v-6h6v6" stroke="#14532D" stroke-width="1.8" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <h3>Samridhi Agro</h3>
                            <p>Central agricultural product distribution.</p>
                        </div>
                        <div class="flow-step reveal" data-reveal data-reveal-delay="100">
                            <span class="flow-step__num">02</span>
                            <span class="flow-step__icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M8 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM16 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" stroke="#14532D" stroke-width="1.8" />
                                </svg>
                            </span>
                            <h3>Agent</h3>
                            <p>Connects local markets.</p>
                        </div>
                        <div class="flow-step reveal" data-reveal data-reveal-delay="200">
                            <span class="flow-step__num">03</span>
                            <span class="flow-step__icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 9l1.5-5h13L20 9M4 9h16v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9z" stroke="#14532D" stroke-width="1.8" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <h3>Shop</h3>
                            <p>Brings products closer to farmers.</p>
                        </div>
                        <div class="flow-step reveal" data-reveal data-reveal-delay="300">
                            <span class="flow-step__num">04</span>
                            <span class="flow-step__icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="8" r="4" stroke="#14532D" stroke-width="1.8" />
                                    <path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" stroke="#14532D" stroke-width="1.8" stroke-linecap="round" />
                                </svg>
                            </span>
                            <h3>Farmer</h3>
                            <p>Gets reliable agricultural products.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ WHY SAMRIDHI AGRO ============ -->
        <section class="features">
            <div class="container">
                <div class="section-head reveal" data-reveal>
                    <p class="eyebrow"><span class="eyebrow__dot"></span> Why Samridhi Agro</p>
                    <h2>Built for a Stronger Agricultural Network</h2>
                </div>

                <div class="feature-grid">
                    <div class="feature-card reveal" data-reveal style="--tint:#16A34A">
                        <span class="feature-card__icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                                <path d="M3 12h18M12 3v18" stroke="#fff" stroke-width="1.8" stroke-linecap="round" />
                            </svg></span>
                        <h3>Direct Distribution</h3>
                        <p>Products move straight from Samridhi Agro to shops — fewer stops, faster movement.</p>
                    </div>
                    <div class="feature-card reveal" data-reveal data-reveal-delay="60" style="--tint:#22C55E">
                        <span class="feature-card__icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                                <path d="M4 12l5 5L20 6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg></span>
                        <h3>Trusted Products</h3>
                        <p>Every listing is verified for quality before it reaches a shop shelf.</p>
                    </div>
                    <div class="feature-card reveal" data-reveal data-reveal-delay="120" style="--tint:#65A30D">
                        <span class="feature-card__icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                                <circle cx="6" cy="6" r="2.5" stroke="#fff" stroke-width="1.8" />
                                <circle cx="18" cy="6" r="2.5" stroke="#fff" stroke-width="1.8" />
                                <circle cx="12" cy="18" r="2.5" stroke="#fff" stroke-width="1.8" />
                                <path d="M8 7.5L11 16M16 7.5L13 16M8.5 6H15.5" stroke="#fff" stroke-width="1.8" />
                            </svg></span>
                        <h3>Local Shop Network</h3>
                        <p>An expanding web of neighbourhood shops that put products within reach.</p>
                    </div>
                    <div class="feature-card reveal" data-reveal style="--tint:#14532D">
                        <span class="feature-card__icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                                <path d="M13 2L4 14h7l-1 8 9-12h-7l1-8z" stroke="#fff" stroke-width="1.6" stroke-linejoin="round" />
                            </svg></span>
                        <h3>Faster Availability</h3>
                        <p>Optimised routes keep shelves stocked when farmers need them most.</p>
                    </div>
                    <div class="feature-card reveal" data-reveal data-reveal-delay="60" style="--tint:#16A34A">
                        <span class="feature-card__icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                                <path d="M9 12l2 2 4-4M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3z" stroke="#fff" stroke-width="1.6" stroke-linejoin="round" />
                            </svg></span>
                        <h3>Transparent Operations</h3>
                        <p>Clear order tracking and pricing at every step of the chain.</p>
                    </div>
                    <div class="feature-card reveal" data-reveal data-reveal-delay="120" style="--tint:#22C55E">
                        <span class="feature-card__icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="8" r="4" stroke="#fff" stroke-width="1.8" />
                                <path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" stroke="#fff" stroke-width="1.8" stroke-linecap="round" />
                            </svg></span>
                        <h3>Farmer Focused</h3>
                        <p>Every part of the network exists to serve the farmer at the end of it.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ PRODUCTS ============ -->
        <section class="products" id="products">
            <div class="container">
                <div class="section-head reveal" data-reveal>
                    <p class="eyebrow"><span class="eyebrow__dot"></span> Catalogue</p>
                    <h2>Agricultural Products at Your Doorstep</h2>
                </div>

                <div class="product-grid">
                    <!-- Product images: assets/images/products/*.jpg, recommended 640x480, or the SVG fallback below renders automatically -->
                    <article class="product-card reveal" data-reveal>
                        <div class="product-card__media">
                            <img src="assets/images/products/seeds.png" alt="Assorted agricultural seeds">
                        </div>
                        <span class="product-card__tag">Seeds</span>
                        <p>High-yield, quality-checked seed varieties for every season.</p>
                        <a href="#" class="product-card__link">View Products <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg></a>
                    </article>

                    <article class="product-card reveal" data-reveal data-reveal-delay="60">
                        <div class="product-card__media">
                            <img src="assets/images/products/fertilizers.png" alt="Granular fertilizer">
                        </div>
                        <span class="product-card__tag">Fertilizers</span>
                        <p>Balanced nutrient blends to strengthen soil and boost growth.</p>
                        <a href="#" class="product-card__link">View Products <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg></a>
                    </article>

                    <article class="product-card reveal" data-reveal data-reveal-delay="120">
                        <div class="product-card__media">
                            <img src="assets/images/products/organic.png" alt="Fresh organic produce">
                        </div>
                        <span class="product-card__tag">Organic Products</span>
                        <p>Chemical-free options for farmers building sustainable yields.</p>
                        <a href="#" class="product-card__link">View Products <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg></a>
                    </article>

                    <article class="product-card reveal" data-reveal>
                        <div class="product-card__media">
                            <img src="assets/images/products/crop-protection.png" alt="Crop protection spray bottle">
                        </div>
                        <span class="product-card__tag">Crop Protection</span>
                        <p>Safe, tested solutions that guard crops against pests and disease.</p>
                        <a href="#" class="product-card__link">View Products <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg></a>
                    </article>

                    <article class="product-card reveal" data-reveal data-reveal-delay="60">
                        <div class="product-card__media">
                            <img src="assets/images/products/tools.png" alt="Farming hand tools">
                        </div>
                        <span class="product-card__tag">Farming Tools</span>
                        <p>Durable hand and power tools built for daily field work.</p>
                        <a href="#" class="product-card__link">View Products <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg></a>
                    </article>

                    <article class="product-card reveal" data-reveal data-reveal-delay="120">
                        <div class="product-card__media">
                            <img src="assets/images/products/supplies.png" alt="Agricultural supply boxes and sacks">
                        </div>
                        <span class="product-card__tag">Agricultural Supplies</span>
                        <p>Everyday inputs, packaging and essentials for shops and farms.</p>
                        <a href="#" class="product-card__link">View Products <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg></a>
                    </article>
                </div>
            </div>
        </section>
<img src="chhattisgarh-map.svg" alt="Chhattisgarh Map" width="500" />
        <!-- ============ FARMER ============ -->
        <section class="farmer">
            <div class="container farmer__inner">
                <div class="farmer__visual reveal" data-reveal>
                    <div class="farmer__photo-frame">
                        <img class="farmer__photo" src="assets/images/farmer-2.png" alt="Indian farmer smiling, representing the people Samridhi Agro serves">
                    </div>
                </div>

                <div class="farmer__content reveal" data-reveal data-reveal-delay="120">
                    <p class="eyebrow"><span class="eyebrow__dot"></span> Our Purpose</p>
                    <h2>Empowering the People Who Grow India</h2>
                    <p>Samridhi Agro works to make agricultural products more accessible by strengthening the connection between distributors, local shops and farmers.</p>

                    <div class="stat-grid">
                        <div class="stat">
                            <span class="stat__num" data-counter data-target="10000">0</span><span class="stat__plus">+</span>
                            <span class="stat__label">Farmers Connected</span>
                        </div>
                        <div class="stat">
                            <span class="stat__num" data-counter data-target="500">0</span><span class="stat__plus">+</span>
                            <span class="stat__label">Local Shops</span>
                        </div>
                        <div class="stat">
                            <span class="stat__num" data-counter data-target="100">0</span><span class="stat__plus">+</span>
                            <span class="stat__label">Products</span>
                        </div>
                        <div class="stat">
                            <span class="stat__num" data-counter data-target="25">0</span><span class="stat__plus">+</span>
                            <span class="stat__label">Distribution Locations</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ SHOP NETWORK ============ -->
        <section class="network" id="network-map">
            <div class="container network__inner">
                <div class="network__visual reveal" data-reveal>
                    <svg viewBox="0 0 420 480" aria-hidden="true">
                        <!-- Stylized Chhattisgarh outline (elongated N–S shape) -->
                        <path d="M195 15 L235 40 L260 30 L285 65 L270 110 L300 140 L290 190
             L320 220 L310 270 L335 300 L320 345 L280 370 L275 410
             L235 440 L200 465 L165 440 L150 400 L110 380 L95 335
             L60 310 L70 260 L45 220 L65 175 L50 130 L85 100
             L80 60 L120 45 L150 20 Z"
                            fill="#F0FDF4" stroke="#16A34A" stroke-width="1.6" opacity="0.95" />

                        <!-- connector lines: Raipur is the hub -->
                        <g class="network-points">
                            <path class="net-line" d="M200 250 L150 130" /> <!-- to Ambikapur -->
                            <path class="net-line" d="M200 250 L120 175" /> <!-- to Bilaspur -->
                            <path class="net-line" d="M200 250 L255 190" /> <!-- to Korba -->
                            <path class="net-line" d="M200 250 L110 300" /> <!-- to Durg -->
                            <path class="net-line" d="M200 250 L215 390" /> <!-- to Bastar -->

                            <!-- moving particles travelling along each connector -->
                            <circle r="3.4" fill="#22C55E">
                                <animateMotion dur="3.2s" repeatCount="indefinite" path="M200 250 L150 130" />
                            </circle>
                            <circle r="3.4" fill="#22C55E">
                                <animateMotion dur="2.6s" repeatCount="indefinite" path="M200 250 L120 175" />
                            </circle>
                            <circle r="3.4" fill="#22C55E">
                                <animateMotion dur="2.9s" repeatCount="indefinite" path="M200 250 L255 190" />
                            </circle>
                            <circle r="3.4" fill="#22C55E">
                                <animateMotion dur="3.6s" repeatCount="indefinite" path="M200 250 L110 300" />
                            </circle>
                            <circle r="3.4" fill="#22C55E">
                                <animateMotion dur="3.0s" repeatCount="indefinite" path="M200 250 L215 390" />
                            </circle>

                            <!-- city nodes -->
                            <circle class="net-dot" cx="150" cy="130" r="6" />
                            <circle class="net-dot" cx="120" cy="175" r="6" />
                            <circle class="net-dot" cx="255" cy="190" r="6" />
                            <circle class="net-dot" cx="110" cy="300" r="6" />
                            <circle class="net-dot" cx="215" cy="390" r="6" />

                            <!-- Raipur hub, slightly larger and gold-accented -->
                            <circle cx="200" cy="250" r="9" fill="#EAB308" opacity="0.25" />
                            <circle cx="200" cy="250" r="6" fill="#EAB308" />
                        </g>

                        <!-- city labels -->
                        <g font-family="Inter, sans-serif" font-size="10" font-weight="600" fill="#14532D">
                            <text x="150" y="122" text-anchor="middle">Ambikapur</text>
                            <text x="120" y="167" text-anchor="middle">Bilaspur</text>
                            <text x="255" y="182" text-anchor="middle">Korba</text>
                            <text x="110" y="292" text-anchor="middle">Durg</text>
                            <text x="215" y="382" text-anchor="middle">Bastar</text>
                            <text x="200" y="270" text-anchor="middle" font-size="11" fill="#052E16">Raipur</text>
                        </g>
                    </svg>
                </div>

                <div class="network__content reveal" data-reveal data-reveal-delay="120">
                    <p class="eyebrow"><span class="eyebrow__dot"></span> Shop Network</p>
                    <h2>Powering India's Local Agricultural Shops</h2>
                    <p>Your local shop can become part of the Samridhi Agro network.</p>
                    <a href="shop/login.php" class="btn btn--gold">Join as a Shop</a>
                </div>
            </div>
        </section>

        <!-- ============ AGENT ============ -->
        <section class="agent">
            <div class="container agent__inner">
                <div class="agent__content reveal" data-reveal>
                    <p class="eyebrow"><span class="eyebrow__dot"></span> Agent Program</p>
                    <h2>Grow Your Network. Grow With Samridhi Agro.</h2>
                    <p>Agents connect local shops with Samridhi Agro products — building the bridge between distribution and demand.</p>

                    <ul class="agent__list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 12l5 5L20 6" stroke="#16A34A" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg> Shop Management</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 12l5 5L20 6" stroke="#16A34A" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg> Order Management</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 12l5 5L20 6" stroke="#16A34A" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg> Product Distribution</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 12l5 5L20 6" stroke="#16A34A" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg> Sales Tracking</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M4 12l5 5L20 6" stroke="#16A34A" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg> Network Growth</li>
                    </ul>

                    <a href="agent/login.php" class="btn btn--primary">Become an Agent</a>
                </div>

                <div class="agent__visual reveal" data-reveal data-reveal-delay="120" aria-hidden="true">
                    <div class="agent-card agent-card--1">Shop A</div>
                    <div class="agent-card agent-card--2">Shop B</div>
                    <div class="agent-card agent-card--3">Shop C</div>
                    <div class="agent-hub">Agent</div>
                </div>
            </div>
        </section>

        <!-- ============ STATS (dark) ============ -->
        <section class="stats">
            <svg class="stats__pattern" viewBox="0 0 800 400" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 380C120 300 180 340 260 300 340 260 380 320 460 280 540 240 600 300 680 260 740 230 780 260 800 240"
                    stroke="#22C55E" stroke-width="2" fill="none" opacity="0.15" />
                <path d="M0 320C100 260 200 300 300 250 400 200 480 260 560 220 640 180 720 220 800 190"
                    stroke="#65A30D" stroke-width="2" fill="none" opacity="0.12" />
            </svg>
            <div class="container">
                <div class="stats-grid">
                    <div class="stat stat--light">
                        <span class="stat__num" data-counter data-target="10000">0</span><span class="stat__plus">+</span>
                        <span class="stat__label">Farmers</span>
                    </div>
                    <div class="stat stat--light">
                        <span class="stat__num" data-counter data-target="850">0</span><span class="stat__plus">+</span>
                        <span class="stat__label">Shops</span>
                    </div>
                    <div class="stat stat--light">
                        <span class="stat__num" data-counter data-target="150">0</span><span class="stat__plus">+</span>
                        <span class="stat__label">Products</span>
                    </div>
                    <div class="stat stat--light">
                        <span class="stat__num" data-counter data-target="25">0</span><span class="stat__plus">+</span>
                        <span class="stat__label">Locations</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ TESTIMONIALS ============ -->
        <section class="testimonials">
            <div class="container">
                <div class="section-head reveal" data-reveal>
                    <p class="eyebrow"><span class="eyebrow__dot"></span> Voices From the Network</p>
                    <h2>What Our Partners Say</h2>
                </div>

                <div class="testimonial-grid">
                    <blockquote class="testimonial-card reveal" data-reveal>
                        <p>"Samridhi Agro has made it easier for our shop to get agricultural products on time."</p>
                        <footer>— Local Shop Partner</footer>
                    </blockquote>
                    <blockquote class="testimonial-card reveal" data-reveal data-reveal-delay="80">
                        <p lang="hi">"अब किसानों तक सही उत्पाद पहुँचाना और आसान हो गया है।"</p>
                        <footer>— Distribution Partner</footer>
                    </blockquote>
                    <blockquote class="testimonial-card reveal" data-reveal data-reveal-delay="160">
                        <p>"Reliable products and a growing network."</p>
                        <footer>— Samridhi Agro Partner</footer>
                    </blockquote>
                </div>
            </div>
        </section>

        <!-- ============ FINAL CTA ============ -->
        <section class="cta">
            <div class="container cta__inner reveal" data-reveal>
                <h2>Be Part of the Samridhi Agro Network</h2>
                <p>Connect your shop, grow your business and help build a stronger agricultural ecosystem.</p>
                <div class="cta__buttons">
                    <a href="shop/login.php" class="btn btn--white">Join as Shop</a>
                    <a href="agent/login.php" class="btn btn--outline-light">Become an Agent</a>
                </div>
            </div>
        </section>

    </main>

    <!-- ============ FOOTER ============ -->
    <footer class="footer" id="footer">
        <div class="container footer__grid">
            <div class="footer__brand">
                <span class="brand__text">SAMRIDHI<span class="brand__text--sub">AGRO</span></span>
                <p>Connecting Agriculture. Empowering Markets.</p>
            </div>

            <div class="footer__col">
                <h4>Company</h4>
                <a href="#ecosystem">About</a>
                <a href="#network-map">Our Network</a>
                <a href="#products">Products</a>
                <a href="#footer">Contact</a>
            </div>

            <div class="footer__col">
                <h4>Portal</h4>
                <a href="admin/login.php">Admin Login</a>
                <a href="staff/login.php">Staff Login</a>
                <a href="agent/login.php">Agent Login</a>
                <a href="shop/login.php">Shop Login</a>
            </div>

            <div class="footer__col">
                <h4>Support</h4>
                <a href="#">Help Center</a>
                <a href="#">Contact</a>
                <a href="#">Terms</a>
                <a href="#">Privacy</a>
            </div>
        </div>

        <div class="footer__bottom">
            <p>&copy; <?php echo date('Y'); ?> Samridhi Agro. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>

</html>