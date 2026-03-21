<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaah Fast Food | New Hargeisa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #fff7f2;
            --surface: #ffffff;
            --text: #1f1f29;
            --muted: #65657a;
            --primary: #ff5a1f;
            --primary-dark: #db4712;
            --accent: #ffd15c;
            --line: #ece7e2;
            --radius-lg: 24px;
            --radius-md: 16px;
            --shadow: 0 14px 40px rgba(31, 31, 41, 0.1);
            --container: 1160px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: "Inter", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: linear-gradient(180deg, #fff7f2 0%, #fff 35%, #fff 100%);
            line-height: 1.6;
        }

        .container { width: min(var(--container), 92%); margin: 0 auto; }
        .section { padding: 88px 0; }
        .section-title { font-size: clamp(1.7rem, 2.8vw, 2.4rem); margin-bottom: 14px; }
        .section-lead { color: var(--muted); max-width: 680px; }
        .kicker {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            color: var(--primary-dark);
            background: #ffe8de;
            border: 1px solid #ffd7c7;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.85rem;
            margin-bottom: 14px;
            font-weight: 600;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 90;
            backdrop-filter: blur(10px);
            background: rgba(255, 247, 242, 0.88);
            border-bottom: 1px solid rgba(236, 231, 226, 0.8);
        }
        .nav-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 76px;
            gap: 20px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: 0.2px;
        }
        .brand i { color: var(--primary); }
        .nav-links {
            display: flex;
            list-style: none;
            gap: 22px;
            align-items: center;
        }
        .nav-links a {
            text-decoration: none;
            color: var(--text);
            font-weight: 600;
            font-size: 0.95rem;
        }
        .nav-links a:hover { color: var(--primary-dark); }
        .nav-actions { display: flex; gap: 10px; align-items: center; }

        .btn {
            border: 0;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 700;
            padding: 12px 18px;
            border-radius: 12px;
            transition: all .2s ease;
            cursor: pointer;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-light { background: #fff; color: var(--text); border: 1px solid var(--line); }
        .btn-light:hover { border-color: #d8d1c9; }
        .btn-outline { border: 1px solid var(--primary); color: var(--primary); background: #fff; }
        .btn-outline:hover { background: #fff3ee; }

        .menu-toggle {
            display: none;
            background: #fff;
            border: 1px solid var(--line);
            width: 44px;
            height: 44px;
            border-radius: 10px;
            cursor: pointer;
        }

        .hero {
            padding: 80px 0 60px;
        }
        .hero-grid {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 44px;
            align-items: center;
        }
        .hero h1 {
            font-size: clamp(2rem, 4.8vw, 3.6rem);
            line-height: 1.08;
            margin-bottom: 18px;
        }
        .hero p {
            color: var(--muted);
            margin-bottom: 24px;
            max-width: 560px;
            font-size: 1.05rem;
        }
        .hero-cta { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
        .hero-badges {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            color: var(--muted);
            font-size: .93rem;
        }
        .hero-badges span {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 8px 12px;
        }

        .hero-card {
            position: relative;
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 20px;
            border: 1px solid #f2ece7;
        }
        .hero-card img {
            width: 100%;
            height: 390px;
            object-fit: cover;
            border-radius: 18px;
        }
        .hero-chip {
            position: absolute;
            right: 30px;
            top: 28px;
            background: rgba(31,31,41,0.82);
            color: #fff;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: .85rem;
        }
        .hero-price {
            position: absolute;
            left: 36px;
            bottom: 34px;
            background: var(--accent);
            color: #3f3409;
            padding: 10px 14px;
            border-radius: 12px;
            font-weight: 800;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: stretch;
        }
        .about-panel, .stats-panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            padding: 28px;
        }
        .about-list { list-style: none; margin-top: 16px; }
        .about-list li {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            color: #37374c;
        }
        .about-list i { color: var(--primary); margin-top: 4px; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-top: 14px;
        }
        .stat {
            background: #fff8f4;
            border: 1px solid #ffe4d6;
            border-radius: 14px;
            padding: 16px;
        }
        .stat h3 { font-size: 1.5rem; color: var(--primary-dark); }
        .stat p { color: var(--muted); font-size: .92rem; }

        .menu-grid {
            margin-top: 26px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        .menu-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,.07);
        }
        .menu-card img { width: 100%; height: 190px; object-fit: cover; }
        .menu-content { padding: 16px; }
        .menu-content h3 { font-size: 1.1rem; margin-bottom: 6px; }
        .menu-content p { color: var(--muted); font-size: .92rem; margin-bottom: 10px; }
        .menu-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
        }
        .menu-price { color: var(--primary-dark); }

        .services-grid {
            margin-top: 26px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        .service {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 20px;
        }
        .service i {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: #fff3ee;
            color: var(--primary);
            margin-bottom: 10px;
        }
        .service h4 { margin-bottom: 6px; }
        .service p { color: var(--muted); font-size: .92rem; }

        .testimonials-wrap {
            margin-top: 24px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .testimonial {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 20px;
        }
        .stars { color: #f59f00; margin-bottom: 10px; }
        .testimonial p { color: #424259; margin-bottom: 16px; }
        .person { color: var(--muted); font-size: .9rem; font-weight: 600; }

        .location-band {
            margin-top: 70px;
            background: linear-gradient(135deg, #ff5a1f 0%, #ff7b2f 100%);
            color: #fff;
            border-radius: 20px;
            padding: 26px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .location-band h3 { font-size: clamp(1.25rem, 2.1vw, 1.6rem); }
        .location-band p { opacity: .95; }

        .footer {
            margin-top: 90px;
            border-top: 1px solid var(--line);
            padding: 42px 0 24px;
            background: #fff;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr .9fr;
            gap: 28px;
        }
        .footer h4 { margin-bottom: 10px; }
        .footer p, .footer li, .footer a {
            color: var(--muted);
            text-decoration: none;
            font-size: .95rem;
        }
        .footer ul { list-style: none; }
        .footer li { margin-bottom: 8px; }
        .copyright {
            border-top: 1px solid var(--line);
            margin-top: 26px;
            padding-top: 14px;
            color: var(--muted);
            font-size: .9rem;
            text-align: center;
        }

        @media (max-width: 1050px) {
            .hero-grid, .about-grid { grid-template-columns: 1fr; }
            .services-grid { grid-template-columns: repeat(2, 1fr); }
            .menu-grid, .testimonials-wrap { grid-template-columns: repeat(2, 1fr); }
            .hero-card img { height: 320px; }
        }

        @media (max-width: 760px) {
            .section { padding: 70px 0; }
            .menu-toggle { display: inline-grid; place-items: center; }
            .nav-links {
                position: absolute;
                top: 76px;
                left: 0;
                right: 0;
                padding: 14px 4%;
                background: #fff;
                border-bottom: 1px solid var(--line);
                flex-direction: column;
                align-items: flex-start;
                display: none;
            }
            .nav-links.show { display: flex; }
            .nav-actions { display: none; }
            .menu-grid, .services-grid, .testimonials-wrap, .stats-grid, .footer-grid {
                grid-template-columns: 1fr;
            }
            .hero { padding-top: 54px; }
            .hero-card img { height: 260px; }
            .location-band { padding: 20px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="container nav-wrap">
            <a href="#home" class="brand">
                <i class="fas fa-utensils"></i>
                <span>Kaah Fast Food</span>
            </a>

            <ul class="nav-links" id="navLinks">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#menu">Menu</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#testimonials">Testimonials</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>

            <div class="nav-actions">
                <a href="php/login.php" class="btn btn-light"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="php/signup.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Sign Up</a>
            </div>

            <button class="menu-toggle" id="menuToggle" aria-label="Open navigation">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <main>
        <section class="hero" id="home">
            <div class="container hero-grid">
                <div>
                    <span class="kicker"><i class="fas fa-map-marker-alt"></i> New Hargeisa</span>
                    <h1>Fresh, Fast, and Full of Flavor at <span style="color: var(--primary);">Kaah Fast Food</span></h1>
                    <p>Enjoy a modern fast food experience with quality ingredients, quick delivery, and bold taste made for every craving in New Hargeisa.</p>
                    <div class="hero-cta">
                        <a href="php/signup.php" class="btn btn-primary"><i class="fas fa-bolt"></i> Start Ordering</a>
                        <a href="php/login.php" class="btn btn-outline"><i class="fas fa-arrow-right"></i> Login to Account</a>
                    </div>
                    <div class="hero-badges">
                        <span><i class="fas fa-check-circle"></i> Halal Friendly</span>
                        <span><i class="fas fa-clock"></i> 30 min average delivery</span>
                        <span><i class="fas fa-star"></i> Trusted by local customers</span>
                    </div>
                </div>

                <div class="hero-card">
                    <img src="https://images.unsplash.com/photo-1565299507177-b0ac66763828?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80" alt="Kaah Fast Food signature meal">
                    <span class="hero-chip">Chef Recommended</span>
                    <span class="hero-price">From $4.99</span>
                </div>
            </div>
        </section>

        <section class="section" id="about">
            <div class="container">
                <span class="kicker"><i class="fas fa-fire"></i> About Us</span>
                <h2 class="section-title">A local fast food brand built for speed and quality</h2>
                <p class="section-lead">Kaah Fast Food serves delicious meals with consistent quality, clean preparation, and reliable service for families, workers, and students across New Hargeisa.</p>

                <div class="about-grid" style="margin-top: 24px;">
                    <div class="about-panel">
                        <h3>What makes us different</h3>
                        <ul class="about-list">
                            <li><i class="fas fa-check"></i><span>Freshly prepared meals made to order every day.</span></li>
                            <li><i class="fas fa-check"></i><span>Hygienic kitchen standards and careful food handling.</span></li>
                            <li><i class="fas fa-check"></i><span>Quick service with a focus on customer satisfaction.</span></li>
                            <li><i class="fas fa-check"></i><span>Convenient ordering through your account dashboard.</span></li>
                        </ul>
                    </div>
                    <div class="stats-panel">
                        <h3>Kaah by numbers</h3>
                        <div class="stats-grid">
                            <div class="stat"><h3>4.8/5</h3><p>Average customer rating</p></div>
                            <div class="stat"><h3>1,200+</h3><p>Orders completed monthly</p></div>
                            <div class="stat"><h3>25+</h3><p>Popular menu options</p></div>
                            <div class="stat"><h3>7 Days</h3><p>Open all week</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="menu">
            <div class="container">
                <span class="kicker"><i class="fas fa-burger"></i> Menu Preview</span>
                <h2 class="section-title">Most-loved choices at Kaah Fast Food</h2>
                <p class="section-lead">Explore popular meals customers order frequently. Create an account to unlock full menu and ordering.</p>

                <div class="menu-grid">
                    <article class="menu-card">
                        <img src="https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=900&q=80" alt="Chicken pizza">
                        <div class="menu-content">
                            <h3>Loaded Chicken Pizza</h3>
                            <p>Crispy crust topped with seasoned chicken and melted cheese.</p>
                            <div class="menu-footer"><span class="menu-price">$6.99</span><span>Popular</span></div>
                        </div>
                    </article>
                    <article class="menu-card">
                        <img src="https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=900&q=80" alt="Classic burger">
                        <div class="menu-content">
                            <h3>Kaah Classic Burger</h3>
                            <p>Juicy beef patty with fresh lettuce, tomato, and signature sauce.</p>
                            <div class="menu-footer"><span class="menu-price">$5.49</span><span>Best Seller</span></div>
                        </div>
                    </article>
                    <article class="menu-card">
                        <img src="https://images.unsplash.com/photo-1562967916-eb82221dfb92?auto=format&fit=crop&w=900&q=80" alt="Crispy fries">
                        <div class="menu-content">
                            <h3>Golden Fries Basket</h3>
                            <p>Hand-cut fries with choice of spicy or garlic dip.</p>
                            <div class="menu-footer"><span class="menu-price">$2.99</span><span>Quick Bite</span></div>
                        </div>
                    </article>
                    <article class="menu-card">
                        <img src="https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=80" alt="Fried chicken">
                        <div class="menu-content">
                            <h3>Crunchy Chicken Box</h3>
                            <p>Marinated fried chicken served with fries and sauce.</p>
                            <div class="menu-footer"><span class="menu-price">$7.29</span><span>Family Pick</span></div>
                        </div>
                    </article>
                    <article class="menu-card">
                        <img src="https://images.unsplash.com/photo-1625944230945-1b7dd3b949ab?auto=format&fit=crop&w=900&q=80" alt="Shawarma wrap">
                        <div class="menu-content">
                            <h3>Shawarma Wrap</h3>
                            <p>Soft wrap packed with grilled meat, veggies, and sauce.</p>
                            <div class="menu-footer"><span class="menu-price">$4.99</span><span>Hot Pick</span></div>
                        </div>
                    </article>
                    <article class="menu-card">
                        <img src="https://images.unsplash.com/photo-1544148103-0773bf10d330?auto=format&fit=crop&w=900&q=80" alt="Fresh juice">
                        <div class="menu-content">
                            <h3>Fresh Juice Mix</h3>
                            <p>Refreshing fruit blend prepared daily.</p>
                            <div class="menu-footer"><span class="menu-price">$1.99</span><span>Cold Drink</span></div>
                        </div>
                    </article>
                </div>

                <div style="margin-top: 24px;">
                    <a href="php/login.php" class="btn btn-primary"><i class="fas fa-cart-plus"></i> Login and Order Now</a>
                </div>
            </div>
        </section>

        <section class="section" id="services">
            <div class="container">
                <span class="kicker"><i class="fas fa-rocket"></i> Services</span>
                <h2 class="section-title">Fast service designed around your convenience</h2>
                <p class="section-lead">From easy ordering to on-time delivery, every step is optimized for a smooth customer experience.</p>

                <div class="services-grid">
                    <article class="service">
                        <i class="fas fa-motorcycle"></i>
                        <h4>Fast Delivery</h4>
                        <p>Quick dispatch and reliable delivery in New Hargeisa.</p>
                    </article>
                    <article class="service">
                        <i class="fas fa-mobile-alt"></i>
                        <h4>Easy Ordering</h4>
                        <p>Simple account flow with clear order tracking.</p>
                    </article>
                    <article class="service">
                        <i class="fas fa-shield-heart"></i>
                        <h4>Food Safety</h4>
                        <p>Clean preparation and quality checks for every order.</p>
                    </article>
                    <article class="service">
                        <i class="fas fa-headset"></i>
                        <h4>Customer Support</h4>
                        <p>Responsive assistance when you need help with orders.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="testimonials">
            <div class="container">
                <span class="kicker"><i class="fas fa-comments"></i> Testimonials</span>
                <h2 class="section-title">What customers say about Kaah Fast Food</h2>
                <p class="section-lead">Real feedback from regular customers in New Hargeisa.</p>

                <div class="testimonials-wrap">
                    <article class="testimonial">
                        <div class="stars"><i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i></div>
                        <p>"Very fast delivery and fresh food every time. The burger combo is my favorite."</p>
                        <div class="person">Amina H. - New Hargeisa</div>
                    </article>
                    <article class="testimonial">
                        <div class="stars"><i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i></div>
                        <p>"Clean service, good portions, and consistent quality. Highly recommended."</p>
                        <div class="person">Mohamed A. - New Hargeisa</div>
                    </article>
                    <article class="testimonial">
                        <div class="stars"><i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star-half-alt"></i></div>
                        <p>"Ordering online is easy and the team responds quickly when needed."</p>
                        <div class="person">Sahra M. - New Hargeisa</div>
                    </article>
                </div>

                <div class="location-band" id="contact">
                    <div>
                        <h3><i class="fas fa-location-dot"></i> Serving New Hargeisa daily</h3>
                        <p>Call us at +25259875 or email info@kaahfastfood.com</p>
                    </div>
                    <a href="php/signup.php" class="btn btn-light"><i class="fas fa-user-plus"></i> Create Account</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h4>Kaah Fast Food</h4>
                    <p>Modern fast food experience with fast delivery, quality meals, and reliable service in New Hargeisa.</p>
                </div>
                <div>
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#about">About</a></li>
                        <li><a href="#menu">Menu Preview</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#testimonials">Testimonials</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Account</h4>
                    <ul>
                        <li><a href="php/login.php">Login</a></li>
                        <li><a href="php/signup.php">Sign Up</a></li>
                        <li><a href="php/login.php">Start Ordering</a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                &copy; 2026 Kaah Fast Food. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');

        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('show');
            const icon = menuToggle.querySelector('i');
            icon.className = navLinks.classList.contains('show') ? 'fas fa-times' : 'fas fa-bars';
        });

        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('show');
                menuToggle.querySelector('i').className = 'fas fa-bars';
            });
        });
    </script>
</body>
</html>
