<?php
require_once 'config.php';
if (isLoggedIn()) redirect('home.php');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>بيتكم | الصفحة الرئيسية</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Tajawal', sans-serif;
            overflow: hidden;
            background: #0d1f1b;
            color: #fff;
            width: 100vw; height: 100vh;
            direction: rtl;
        }
        #three-container {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 0;
        }
        .hero {
            position: relative; z-index: 10;
            width: 100%; height: 100vh;
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            text-align: center; padding: 2rem;
            pointer-events: none;
        }
        .hero * { pointer-events: auto; }
        .logo-wrapper {
            margin-bottom: 1.5rem;
            opacity: 0;
            transform: translateY(30px) scale(0.9);
            display: flex; flex-direction: column; align-items: center;
        }
        .main-logo { display: flex; flex-direction: column; align-items: center; gap: 12px; }
        .main-logo .logo-image {
            width: clamp(140px, 18vw, 240px);
            height: clamp(140px, 18vw, 240px);
            border-radius: 30px;
            object-fit: cover;
            border: 3px solid rgba(18, 168, 143, 0.15);
            box-shadow: 0 15px 60px rgba(0, 0, 0, 0.4), 0 0 80px rgba(18, 168, 143, 0.06);
            transition: all 0.6s ease;
            background: rgba(18, 168, 143, 0.05);
        }
        .main-logo .logo-image img { width: 100%; height: 100%; object-fit: cover; border-radius: 30px; }
        .main-logo .logo-text {
            font-size: clamp(3rem, 7vw, 5.5rem);
            font-weight: 900;
            color: #F8FAF9;
            letter-spacing: 6px;
            text-shadow: 0 0 60px rgba(18, 168, 143, 0.08);
            margin-top: 4px;
            line-height: 1.2;
        }
        .main-logo .logo-text .green { color: #12A88F; text-shadow: 0 0 40px rgba(18, 168, 143, 0.15); }
        .logo-underline {
            width: 0; height: 3px;
            background: linear-gradient(90deg, transparent, #12A88F, transparent);
            margin: 0.6rem auto 0;
            border-radius: 10px;
        }
        .subtitle {
            font-size: clamp(1rem, 1.8vw, 1.5rem);
            font-weight: 300;
            letter-spacing: 6px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0.5rem;
            opacity: 0;
            transform: translateY(30px);
        }
        .subtitle i { color: #12A88F; margin: 0 8px; font-size: 0.6rem; opacity: 0.4; }
        .description {
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
            color: rgba(255, 255, 255, 0.35);
            max-width: 550px;
            margin: 0.8rem auto 0;
            opacity: 0;
            transform: translateY(25px);
            line-height: 1.9;
            letter-spacing: 1.5px;
        }
        .btn-group {
            display: flex; gap: 1rem;
            flex-wrap: wrap; justify-content: center;
            margin-top: 2rem;
            opacity: 0; transform: translateY(25px);
        }
        .cta-btn {
            padding: 0.9rem 3.5rem;
            font-size: 1.05rem;
            font-weight: 700;
            font-family: 'Tajawal', sans-serif;
            border: none; border-radius: 60px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
            letter-spacing: 2px;
            display: inline-flex; align-items: center; gap: 12px;
        }
        .cta-btn-primary {
            background: linear-gradient(135deg, #12A88F, #0B8F7A);
            color: #fff;
            box-shadow: 0 6px 40px rgba(18, 168, 143, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.04);
        }
        .cta-btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 60px rgba(18, 168, 143, 0.25);
            background: linear-gradient(135deg, #19B394, #0B8F7A);
        }
        .info-line {
            position: absolute; bottom: 2rem;
            font-size: 0.7rem;
            letter-spacing: 4px;
            color: rgba(255, 255, 255, 0.12);
            opacity: 0;
            display: flex; align-items: center; gap: 15px;
        }
        .info-line .dot {
            width: 4px; height: 4px;
            border-radius: 50%;
            background: #12A88F;
            display: inline-block; opacity: 0.2;
        }
        @media (max-width: 768px) {
            .main-logo .logo-image { width: 120px; height: 120px; border-radius: 24px; }
            .main-logo .logo-text { font-size: 2.8rem; letter-spacing: 4px; }
            .subtitle { font-size: 0.95rem; letter-spacing: 3px; }
            .description { font-size: 0.85rem; padding: 0 1rem; }
            .cta-btn { padding: 0.8rem 2.5rem; font-size: 0.95rem; }
            .info-line { bottom: 1.2rem; font-size: 0.6rem; flex-direction: column; gap: 4px; }
        }
        @media (max-width: 480px) {
            .main-logo .logo-image { width: 100px; height: 100px; border-radius: 20px; }
            .main-logo .logo-text { font-size: 2.2rem; letter-spacing: 2px; }
            .cta-btn { padding: 0.7rem 2rem; font-size: 0.85rem; width: 100%; justify-content: center; }
            .btn-group { width: 100%; padding: 0 1rem; }
        }
    </style>
</head>
<body>
    <div id="three-container"></div>

    <section class="hero">
        <div class="logo-wrapper" id="logoWrapper">
            <div class="main-logo">
                <div class="logo-image">
                    <img src="uploads/sh.png" alt="بيتكم">
                </div>
                <span class="logo-text"><span class="green">بيت</span>كم</span>
            </div>
            <div class="logo-underline" id="underline"></div>
        </div>

        <p class="subtitle" id="subtitle">
            <i class="fas fa-circle"></i> خدمات منزلك بسهولة <i class="fas fa-circle"></i>
        </p>

        <p class="description" id="description">
            منصة متكاملة تجمع لك جميع الخدمات المنزلية من فنيين موثوقين في ربك وكوستي
        </p>

        <div class="btn-group" id="btnGroup">
            <a href="home.php" class="cta-btn cta-btn-primary">
                <i class="fas fa-arrow-left"></i> استكشف الخدمات
            </a>
        </div>

        <div class="info-line" id="infoLine">
            <span class="dot"></span>
            <span>© 2026 بيتكم - ربك وكوستي</span>
            <span class="dot"></span>
        </div>
    </section>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>

    <script>
        (function initThree() {
            const container = document.getElementById('three-container');
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0x0d1f1b);

            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 35;

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            renderer.setClearColor(0x0d1f1b, 1);
            container.appendChild(renderer.domElement);

            const particlesCount = 800;
            const positions = new Float32Array(particlesCount * 3);
            const colors = new Float32Array(particlesCount * 3);

            const colorGreen = new THREE.Color(0x12A88F);
            const colorWhite = new THREE.Color(0xffffff);

            for (let i = 0; i < particlesCount; i++) {
                const radius = 20 + Math.random() * 12;
                const theta = Math.random() * Math.PI * 2;
                const phi = Math.acos((Math.random() * 2) - 1);
                positions[i * 3] = radius * Math.sin(phi) * Math.cos(theta);
                positions[i * 3 + 1] = radius * Math.sin(phi) * Math.sin(theta) * 0.4;
                positions[i * 3 + 2] = radius * Math.cos(phi);
                const mix = Math.random() * 0.3;
                const col = colorGreen.clone().lerp(colorWhite, mix);
                colors[i * 3] = col.r;
                colors[i * 3 + 1] = col.g;
                colors[i * 3 + 2] = col.b;
            }

            const geometry = new THREE.BufferGeometry();
            geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
            geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

            const material = new THREE.PointsMaterial({
                size: 0.2, vertexColors: true,
                transparent: true, opacity: 0.35,
                blending: THREE.AdditiveBlending,
                depthWrite: false, sizeAttenuation: true,
            });

            const particles = new THREE.Points(geometry, material);
            scene.add(particles);

            const bigGeo = new THREE.BufferGeometry();
            const bigPos = new Float32Array(120 * 3);
            for (let i = 0; i < 120; i++) {
                const r = 22 + Math.random() * 10;
                const th = Math.random() * Math.PI * 2;
                const ph = Math.acos((Math.random() * 2) - 1);
                bigPos[i * 3] = r * Math.sin(ph) * Math.cos(th);
                bigPos[i * 3 + 1] = r * Math.sin(ph) * Math.sin(th) * 0.35;
                bigPos[i * 3 + 2] = r * Math.cos(ph);
            }
            bigGeo.setAttribute('position', new THREE.BufferAttribute(bigPos, 3));
            const bigMat = new THREE.PointsMaterial({
                size: 0.5, color: 0x12A88F,
                transparent: true, opacity: 0.05,
                blending: THREE.AdditiveBlending,
                depthWrite: false, sizeAttenuation: true,
            });
            const bigParticles = new THREE.Points(bigGeo, bigMat);
            scene.add(bigParticles);

            let mouseX = 0, mouseY = 0, targetX = 0, targetY = 0;

            document.addEventListener('mousemove', (e) => {
                mouseX = (e.clientX / window.innerWidth) * 2 - 1;
                mouseY = -(e.clientY / window.innerHeight) * 2 + 1;
            });

            function animate() {
                requestAnimationFrame(animate);
                particles.rotation.x += 0.00006;
                particles.rotation.y += 0.00015;
                bigParticles.rotation.x += 0.00004;
                bigParticles.rotation.y += 0.00012;
                targetX += (mouseX * 0.8 - targetX) * 0.01;
                targetY += (mouseY * 0.6 - targetY) * 0.01;
                camera.position.x = targetX;
                camera.position.y = targetY;
                camera.lookAt(0, 0, 0);
                renderer.render(scene, camera);
            }
            animate();

            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        })();

        document.addEventListener('DOMContentLoaded', () => {
            const tl = gsap.timeline({ defaults: { ease: 'power2.out' } });
            tl.to('#logoWrapper', { opacity: 1, y: 0, scale: 1, duration: 1.8 }, 0.2)
              .to('#underline', { width: '50%', duration: 1.2, ease: 'power1.inOut' }, '-=1')
              .to('#subtitle', { opacity: 1, y: 0, duration: 1 }, '-=0.6')
              .to('#description', { opacity: 1, y: 0, duration: 1 }, '-=0.5')
              .to('#btnGroup', { opacity: 1, y: 0, duration: 1 }, '-=0.4')
              .to('#infoLine', { opacity: 1, duration: 0.8 }, '-=0.2');

            gsap.to('.logo-wrapper', { y: -5, duration: 5, repeat: -1, yoyo: true, ease: 'sine.inOut' });
            gsap.to('.cta-btn-primary', { boxShadow: '0 6px 50px rgba(18,168,143,0.2)', duration: 3, repeat: -1, yoyo: true, ease: 'sine.inOut' });
            gsap.to('.dot', { opacity: 0.05, duration: 2.5, repeat: -1, yoyo: true, ease: 'sine.inOut', stagger: 0.3 });
        });
    </script>
</body>
</html>