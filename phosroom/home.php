<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhosRoom - Gestion Premium des Formations</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Light Mode - Palette élégante bleu/or */
            --primary: #3A5A78; /* Bleu profond */
            --primary-light: #6B8D9E; /* Bleu clair */
            --primary-dark: #1D2F3F; /* Bleu nuit */
            --accent: #D4AF37; /* Or */
            --light: #F8F9FA; /* Gris très clair */
            --text: #2D3748; /* Gris foncé */
            --text-light: #718096; /* Gris moyen */
            --white: #FFFFFF;
            --gray: #E2E8F0; /* Gris clair */
            
            /* Dark Mode - Palette luxueuse */
            --dark-bg: #0F172A; /* Bleu nuit profond */
            --dark-surface: #1E293B; /* Bleu surface */
            --dark-text: #F1F5F9; /* Blanc cassé */
            --dark-text-light: #94A3B8; /* Gris bleuté */
            --dark-gray: #334155; /* Gris bleu */
            --dark-primary: #5B8FB9; /* Bleu clair */
            --dark-primary-light: #7FB3D5; /* Bleu très clair */
            --dark-primary-dark: #1E3A8A; /* Bleu royal */
            --dark-accent: #FBBF24; /* Or chaud */
            
            /* Commun */
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            --glass: rgba(255, 255, 255, 0.3);
            --dark-glass: rgba(30, 41, 59, 0.5);
            --radius: 16px;
            --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        /* Dark Mode */
        body.dark-mode {
            --primary: var(--dark-primary);
            --primary-light: var(--dark-primary-light);
            --primary-dark: var(--dark-primary-dark);
            --accent: var(--dark-accent);
            --light: var(--dark-bg);
            --text: var(--dark-text);
            --text-light: var(--dark-text-light);
            --white: var(--dark-surface);
            --gray: var(--dark-gray);
            --glass: var(--dark-glass);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Poppins", sans-serif;
            color: var(--text);
            line-height: 1.7;
            background-color: var(--light);
            overflow-x: hidden;
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Toggle Switch élégant */
        .theme-toggle-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .theme-toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .theme-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to right, var(--primary), var(--accent));
            transition: var(--transition);
            border-radius: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 24px;
            width: 24px;
            left: 3px;
            bottom: 3px;
            background-color: var(--white);
            transition: var(--transition);
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }

        .theme-label {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .dark-mode .theme-label {
            color: var(--dark-text-light);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Video Background */
        .video-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
            opacity: 0.2;
            filter: brightness(0.8);
        }

        .dark-mode .video-background {
            opacity: 0.15;
            filter: brightness(0.4) contrast(1.2);
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(58, 90, 120, 0.3) 0%, rgba(212, 175, 55, 0.1) 100%);
            z-index: 2;
        }

        .dark-mode .hero-overlay {
            background: linear-gradient(45deg, rgba(15, 23, 42, 0.8) 0%, rgba(91, 143, 185, 0.3) 100%);
        }

        /* Content Card */
        .content-card {
            max-width: 800px;
            background: var(--glass);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: var(--radius);
            padding: 4rem;
            box-shadow: var(--shadow-lg);
            animation: fadeInUp 0.8s ease-out 0.3s both;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 3;
            text-align: center;
            overflow: hidden;
        }

        .dark-mode .content-card {
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .content-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, var(--primary-light) 0%, rgba(255,255,255,0) 70%);
            opacity: 0.15;
            z-index: -1;
            animation: float 15s infinite linear;
        }

        .dark-mode .content-card::before {
            background: radial-gradient(circle, var(--dark-accent) 0%, rgba(30,30,30,0) 70%);
            opacity: 0.1;
        }

        .content-card h1 {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .dark-mode .content-card h1 {
            color: var(--dark-accent);
        }

        .content-card p {
            color: var(--text);
            font-size: 1.25rem;
            margin-bottom: 2rem;
            line-height: 1.6;
            opacity: 0.9;
        }

        .highlight {
            color: var(--primary-dark);
            font-weight: 600;
            position: relative;
            display: inline-block;
        }

        .dark-mode .highlight {
            color: var(--dark-accent);
        }

        .highlight::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 0;
            width: 100%;
            height: 6px;
            background: var(--accent);
            z-index: -1;
            opacity: 0.3;
            border-radius: 3px;
        }

        /* CTA Button */
        .cta-button {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1.1rem 2.5rem;
            background: var(--gradient);
            color: var(--white);
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            margin-top: 1rem;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            font-size: 1.1rem;
            animation: fadeInUp 0.8s ease-out 0.6s both;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .cta-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            animation: gradientShift 3s ease infinite;
            background-size: 200% 200%;
        }

        .cta-button i {
            transition: var(--transition);
        }

        .cta-button:hover i {
            transform: translateX(5px);
        }

        /* Floating Elements */
        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: var(--glass);
            backdrop-filter: blur(5px);
            animation: float 6s ease-in-out infinite;
            z-index: 2;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dark-mode .floating-element {
            background: var(--dark-glass);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-card {
                padding: 3rem 2rem;
            }
            
            .content-card h1 {
                font-size: 2.4rem;
            }
            
            .content-card p {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding: 1.5rem;
            }
            
            .content-card {
                padding: 2rem 1.5rem;
            }
            
            .content-card h1 {
                font-size: 2rem;
            }

            .cta-button {
                padding: 1rem 2rem;
                font-size: 1rem;
            }

            .theme-toggle-container {
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
</head>
<body>
   
    <section class="hero">
        <!-- Video Background -->
        <video autoplay muted loop class="video-background">
            <source src="./images/afaf.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>

        <!-- Floating Elements -->
        <div class="floating-element" style="width: 100px; height: 100px; top: 20%; left: 10%; animation-delay: 0s;"></div>
        <div class="floating-element" style="width: 150px; height: 150px; top: 70%; left: 80%; animation-delay: 2s;"></div>
        <div class="floating-element" style="width: 80px; height: 80px; top: 40%; left: 85%; animation-delay: 4s;"></div>

        <!-- Content Card -->
        <div class="content-card">
            <h1>Gestion de formation </h1>
            
            <p>
                <span class="highlight">PhosRoom</span> révolutionne la gestion des espaces de formation avec une plateforme intelligente et intuitive, spécialement conçue pour répondre aux besoins exigeants des professionnels.
            </p>

            <p>
                Optimisez l'utilisation de vos salles, simplifiez la planification des sessions et bénéficiez d'analyses en temps réel pour une gestion optimale de vos ressources.
            </p>

            <a href="form.php" class="cta-button">
                <span>Découvrir la plateforme</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dark Mode Toggle
            const themeToggle = document.getElementById('theme-toggle');
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
            const lightLabel = document.querySelectorAll('.theme-label')[0];
            const darkLabel = document.querySelectorAll('.theme-label')[1];

            // Check for saved preference or system preference
            const currentTheme = localStorage.getItem('theme');
            if (currentTheme === 'dark' || (!currentTheme && prefersDarkScheme.matches)) {
                document.body.classList.add('dark-mode');
                themeToggle.checked = true;
                updateLabels();
            }

            // Toggle theme
            themeToggle.addEventListener('change', function() {
                if (this.checked) {
                    document.body.classList.add('dark-mode');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.body.classList.remove('dark-mode');
                    localStorage.setItem('theme', 'light');
                }
                updateLabels();
            });

            function updateLabels() {
                if (document.body.classList.contains('dark-mode')) {
                    lightLabel.style.opacity = '0.5';
                    darkLabel.style.opacity = '1';
                    darkLabel.style.fontWeight = '600';
                    lightLabel.style.fontWeight = '400';
                } else {
                    lightLabel.style.opacity = '1';
                    darkLabel.style.opacity = '0.5';
                    lightLabel.style.fontWeight = '600';
                    darkLabel.style.fontWeight = '400';
                }
            }

            // Ripple effect for CTA button
            const ctaButton = document.querySelector('.cta-button');
            if (ctaButton) {
                ctaButton.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    ripple.classList.add('ripple-effect');
                    
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size/2;
                    const y = e.clientY - rect.top - size/2;
                    
                    ripple.style.width = ripple.style.height = `${size}px`;
                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 800);
                });
            }

            // Add ripple effect style
            const style = document.createElement('style');
            style.textContent = `
                .ripple-effect {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple 0.8s ease-out;
                    pointer-events: none;
                }
                
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);

            // Parallax effect for hero section
            const hero = document.querySelector('.hero');
            if (hero) {
                window.addEventListener('scroll', function() {
                    const scrollPosition = window.pageYOffset;
                    hero.style.transform = `translateY(${scrollPosition * 0.4}px)`;
                });
            }
        });
    </script>
</body>
</html>