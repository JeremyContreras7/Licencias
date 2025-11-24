<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Creador - Jeremy Contreras</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --accent: #ff7e5f;
            --dark: #0f0c29;
            --light: #ffffff;
            --gray: #e0e0e0;
        }

        body {
            background: linear-gradient(135deg, var(--dark), #302b63, #24243e);
            color: var(--light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(106, 17, 203, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(37, 117, 252, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 126, 95, 0.05) 0%, transparent 50%);
            z-index: -1;
        }

        .header-actions {
            position: absolute;
            top: 30px;
            left: 30px;
            z-index: 100;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: var(--light);
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
        }

        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 60px;
            position: relative;
        }

        header h1 {
            font-size: 4rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--accent), #feb47b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            letter-spacing: -1px;
            position: relative;
            display: inline-block;
        }

        header h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(135deg, var(--accent), #feb47b);
            border-radius: 2px;
        }

        header p {
            font-size: 1.4rem;
            opacity: 0.9;
            font-weight: 300;
            letter-spacing: 0.5px;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .profile-section {
            display: flex;
            flex-wrap: wrap;
            gap: 50px;
            justify-content: center;
            margin-bottom: 60px;
            position: relative;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            z-index: 1;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        }

        .profile-card:hover {
            transform: translateY(-20px) scale(1.02);
            box-shadow: 
                0 35px 70px rgba(0, 0, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 50px 30px 40px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 20%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.05) 0%, transparent 50%);
        }

        .profile-avatar {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            margin: 0 auto 25px;
            overflow: hidden;
            background: linear-gradient(135deg, #fff, #f0f0f0);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .profile-title {
            font-size: 1.3rem;
            opacity: 0.95;
            font-weight: 400;
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.1);
            display: inline-block;
            padding: 8px 20px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
        }

        .profile-body {
            padding: 40px;
            position: relative;
        }

        .profile-description {
            color: var(--gray);
            line-height: 1.8;
            margin-bottom: 30px;
            text-align: center;
            font-size: 1.15rem;
            font-weight: 300;
            position: relative;
        }

        .profile-description::before {
            content: '"';
            font-size: 4rem;
            color: rgba(255, 255, 255, 0.1);
            position: absolute;
            top: -20px;
            left: -10px;
            font-family: serif;
        }

        .skills-section {
            margin-bottom: 35px;
        }

        .skills-title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--light);
            position: relative;
            display: inline-block;
            left: 50%;
            transform: translateX(-50%);
        }

        .skills-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
        }

        .profile-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .skill-tag {
            background: linear-gradient(135deg, rgba(106, 17, 203, 0.3), rgba(37, 117, 252, 0.3));
            padding: 10px 22px;
            border-radius: 50px;
            font-size: 0.95rem;
            color: var(--light);
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .skill-tag::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .skill-tag:hover {
            background: linear-gradient(135deg, rgba(106, 17, 203, 0.5), rgba(37, 117, 252, 0.5));
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .skill-tag:hover::before {
            left: 100%;
        }

        .profile-footer {
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .profile-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        }

        .github-link {
            display: inline-flex;
            align-items: center;
            gap: 15px;
            background: linear-gradient(135deg, #333, #000);
            color: white;
            padding: 16px 35px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.4s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            font-size: 1.1rem;
        }

        .github-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.6s ease;
        }

        .github-link:hover {
            background: linear-gradient(135deg, #000, #333);
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }

        .github-link:hover::before {
            left: 100%;
        }

        .stats-section {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
            margin-top: 50px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            padding: 30px 25px;
            text-align: center;
            width: 180px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.4s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .stat-card:hover {
            transform: translateY(-15px) scale(1.05);
            box-shadow: 0 25px 40px rgba(0, 0, 0, 0.3);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--light);
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, var(--accent), #feb47b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            font-size: 1.1rem;
            color: var(--gray);
            font-weight: 400;
        }

        footer {
            margin-top: 80px;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 1rem;
            padding: 25px;
            width: 100%;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        }

        @media (max-width: 768px) {
            .profile-card {
                max-width: 100%;
            }
            
            header h1 {
                font-size: 2.8rem;
            }
            
            .profile-avatar {
                width: 140px;
                height: 140px;
            }
            
            .stat-card {
                width: 150px;
                padding: 25px 20px;
            }

            .header-actions {
                top: 20px;
                left: 20px;
            }
        }

        /* Animación de partículas de fondo */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -2;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(720deg);
                opacity: 0;
            }
        }

        /* Efectos de brillo */
        .glow {
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(106, 17, 203, 0.2) 0%, transparent 70%);
            filter: blur(40px);
            z-index: -1;
            animation: pulse 8s ease-in-out infinite;
        }

        .glow-1 {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .glow-2 {
            bottom: 10%;
            right: 10%;
            animation-delay: -4s;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.1); }
        }
    </style>
</head>
<body>
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>
    <div class="particles" id="particles"></div>
    
    <div class="header-actions">
        <a class="back-btn" href="../index.php">
            <i class="fas fa-arrow-left"></i> Volver al Sistema
        </a>
    </div>

    <div class="container">
        <header>
            <h1>Conoce al Creador</h1>
            <p>Descubre la mente detrás de este proyecto innovador</p>
        </header>

        <div class="profile-section">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <img src="../img/perfil.png" alt="Jeremy Contreras" class="logo">
                    </div>
                    <h2 class="profile-name">Jeremy Contreras Arias</h2>
                    <p class="profile-title">Desarrollador Full Stack</p>
                </div>
                
                <div class="profile-body">
                    <p class="profile-description">
                        Titulado de la carrera Analista Programador con 3 años de experiencia en desarrollo de software. 
                        Apasionado por crear soluciones innovadoras y eficientes que resuelvan problemas reales.
                    </p>
                    
                    <div class="skills-section">
                        <h3 class="skills-title">Tecnologías Principales</h3>
                        <div class="profile-skills">
                            <span class="skill-tag">JavaScript</span>
                            <span class="skill-tag">PHP</span>
                            <span class="skill-tag">CSS</span>
                            <span class="skill-tag">HTML5</span>
                            <span class="skill-tag">MySQL</span>
                            <span class="skill-tag">React</span>
                            <span class="skill-tag">Node.js</span>
                            <span class="skill-tag">Git</span>
                        </div>
                    </div>
                </div>
                
                <div class="profile-footer">
                    <a href="https://github.com/JeremyContreras7" class="github-link" target="_blank">
                        <i class="fab fa-github"></i>
                        Visitar mi GitHub
                    </a>
                </div>
            </div>
        </div>

        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-number">5+</div>
                <div class="stat-label">Proyectos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">3</div>
                <div class="stat-label">Años Exp.</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">8+</div>
                <div class="stat-label">Tecnologías</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">100%</div>
                <div class="stat-label">Dedicación</div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 Jeremy Contreras. Todos los derechos reservados.</p>
    </footer>

    <script>
        // Efecto de contador para las estadísticas
        document.addEventListener('DOMContentLoaded', function() {
            const statNumbers = document.querySelectorAll('.stat-number');
            
            statNumbers.forEach(stat => {
                const originalText = stat.textContent;
                const target = parseInt(originalText);
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        stat.textContent = originalText;
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(current) + (originalText.includes('+') ? '+' : '');
                    }
                }, 30);
            });

            // Crear partículas de fondo mejoradas
            const particlesContainer = document.getElementById('particles');
            const particleCount = 40;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Tamaño aleatorio
                const size = Math.random() * 25 + 5;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Color aleatorio con transparencia
                const colors = [
                    'rgba(106, 17, 203, 0.3)',
                    'rgba(37, 117, 252, 0.3)',
                    'rgba(255, 126, 95, 0.3)',
                    'rgba(254, 180, 123, 0.3)'
                ];
                particle.style.background = colors[Math.floor(Math.random() * colors.length)];
                
                // Posición inicial aleatoria
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100 + 100}%`;
                
                // Animación con duración y delay aleatorios
                const duration = Math.random() * 25 + 15;
                const delay = Math.random() * 15;
                particle.style.animation = `float ${duration}s infinite linear`;
                particle.style.animationDelay = `${delay}s`;
                
                particlesContainer.appendChild(particle);
            }
        });
    </script>
</body>
</html>