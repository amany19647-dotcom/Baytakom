<?php
require_once 'config.php';
$current_page = basename($_SERVER['PHP_SELF']);
renderPageStart('من نحن', $current_page, 'ربك وكوستي - ولاية النيل الأبيض');
?>

<div class="page-header">
    <div class="container">
        <h1 data-aos="fade-up"><i class="fas fa-circle-info"></i> من نحن</h1>
        <p data-aos="fade-up" data-aos-delay="100">منصة بيتكم تقدم خدمات منزلية متكاملة في <strong>ربك وكوستي</strong> بولاية النيل الأبيض.</p>
    </div>
</div>

<section>
    <div class="container">
        <div class="about-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:50px; align-items:center; margin:40px 0;">
            <div class="content" data-aos="fade-up">
                <div style="display:inline-block; background:rgba(11,143,122,0.12); color:var(--primary-teal); padding:6px 20px; border-radius:30px; font-size:14px; font-weight:600; margin-bottom:16px; border:1px solid rgba(11,143,122,0.15);">
                    <i class="fas fa-map-marker-alt"></i> ربك وكوستي - ولاية النيل الأبيض
                </div>
                <h2 style="font-size:34px; font-weight:800; color:var(--text-white); margin-bottom:16px;">
                    <i class="fas fa-flag" style="color:var(--primary-teal); margin-left:10px;"></i> قصة منصة بيتكم
                </h2>
                <p style="color:var(--text-gray); font-size:17px; line-height:1.8; margin-bottom:16px;">
                    انطلقت منصة <span style="color:var(--primary-teal); font-weight:700;">بيتكم</span> من <strong>مدينة ربك</strong> بولاية النيل الأبيض، لتكون أول منصة متخصصة في تقديم الخدمات المنزلية في المنطقة.
                </p>
                <p style="color:var(--text-gray); font-size:17px; line-height:1.8; margin-bottom:16px;">
                    واجهنا تحديات العثور على فنيين موثوقين، وعدم وضوح الأسعار، وغياب الضمانات. لذا قررنا بناء منصة تجمع بين <span style="color:var(--primary-teal); font-weight:700;">الجودة، الشفافية، والثقة</span>.
                </p>
                <p style="color:var(--text-gray); font-size:17px; line-height:1.8; margin-bottom:16px;">
                    اليوم، نفخر بأن نكون الوجهة الأولى للكثير من الأسر في <strong>ربك وكوستي</strong>.
                </p>
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn-primary" style="display:inline-block; margin-top:20px;"><i class="fas fa-user-plus"></i> انضم إلينا اليوم</a>
                <?php endif; ?>
            </div>
            <div style="background:var(--bg-glass); border:1px solid var(--border-glass); border-radius:30px; padding:30px; text-align:center;" data-aos="fade-up" data-aos-delay="200">
                <div style="background:rgba(11,143,122,0.05); padding:40px; border-radius:20px; text-align:center;">
                    <i class="fas fa-home" style="font-size:100px; color:var(--primary-teal); opacity:0.3;"></i>
                    <h3 style="color:var(--text-white); margin-top:20px;">بيتكم - خدمات منزلك</h3>
                    <p style="color:var(--text-gray);">ربك وكوستي</p>
                </div>
            </div>
        </div>

        <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-glass); border-radius:30px; padding:60px 40px; margin:40px 0;">
            <h2 style="text-align:center; font-size:36px; font-weight:800; margin-bottom:50px; color:var(--text-white);">
                <i class="fas fa-gem" style="color:var(--primary-teal); margin-left:10px;"></i> قيمنا
            </h2>
            <div class="values-grid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:30px;">
                <div class="value-item" data-aos="fade-up" style="background:rgba(255,255,255,0.03); border:1px solid var(--border-glass); border-radius:24px; padding:35px 25px; text-align:center;">
                    <div style="width:75px; height:75px; border-radius:50%; background:rgba(11,143,122,0.08); display:flex; align-items:center; justify-content:center; margin:0 auto 18px; font-size:34px; color:var(--primary-teal);">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 style="font-size:22px; font-weight:800; color:var(--text-white); margin-bottom:8px;">الثقة والأمان</h3>
                    <p style="color:var(--text-gray); font-size:15px;">جميع مقدمي الخدمات موثوقون ومجازون</p>
                </div>
                <div class="value-item" data-aos="fade-up" data-aos-delay="100" style="background:rgba(255,255,255,0.03); border:1px solid var(--border-glass); border-radius:24px; padding:35px 25px; text-align:center;">
                    <div style="width:75px; height:75px; border-radius:50%; background:rgba(11,143,122,0.08); display:flex; align-items:center; justify-content:center; margin:0 auto 18px; font-size:34px; color:var(--primary-teal);">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h3 style="font-size:22px; font-weight:800; color:var(--text-white); margin-bottom:8px;">الجودة والاحترافية</h3>
                    <p style="color:var(--text-gray); font-size:15px;">نختار أفضل الفنيين لضمان أعلى الجودة</p>
                </div>
                <div class="value-item" data-aos="fade-up" data-aos-delay="200" style="background:rgba(255,255,255,0.03); border:1px solid var(--border-glass); border-radius:24px; padding:35px 25px; text-align:center;">
                    <div style="width:75px; height:75px; border-radius:50%; background:rgba(11,143,122,0.08); display:flex; align-items:center; justify-content:center; margin:0 auto 18px; font-size:34px; color:var(--primary-teal);">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <h3 style="font-size:22px; font-weight:800; color:var(--text-white); margin-bottom:8px;">الشفافية</h3>
                    <p style="color:var(--text-gray); font-size:15px;">أسعار واضحة ومعلنة مسبقاً</p>
                </div>
                <div class="value-item" data-aos="fade-up" data-aos-delay="300" style="background:rgba(255,255,255,0.03); border:1px solid var(--border-glass); border-radius:24px; padding:35px 25px; text-align:center;">
                    <div style="width:75px; height:75px; border-radius:50%; background:rgba(11,143,122,0.08); display:flex; align-items:center; justify-content:center; margin:0 auto 18px; font-size:34px; color:var(--primary-teal);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 style="font-size:22px; font-weight:800; color:var(--text-white); margin-bottom:8px;">السرعة والمرونة</h3>
                    <p style="color:var(--text-gray); font-size:15px;">خدمات فورية مع إمكانية الحجز في أي وقت</p>
                </div>
            </div>
        </div>

        <div style="background:var(--bg-glass); border:1px solid var(--border-glass); border-radius:30px; padding:50px 35px; text-align:center; margin:40px 0;" data-aos="fade-up">
            <h2 style="font-size:34px; font-weight:800; color:var(--text-white);">
                <i class="fas fa-handshake" style="color:var(--primary-teal); margin-left:10px;"></i> كن جزءاً من قصة نجاحنا
            </h2>
            <p style="color:var(--text-gray); font-size:18px; margin:10px 0 25px;">سواء كنت عميلاً أو مقدم خدمة، نحن هنا لدعمك في ربك وكوستي</p>
            <div style="display:flex; gap:18px; justify-content:center; flex-wrap:wrap;">
                <a href="register.php" class="btn-primary"><i class="fas fa-user-plus"></i> سجل كعميل</a>
                <a href="register.php" class="btn-outline"><i class="fas fa-briefcase"></i> سجل كمقدم خدمة</a>
            </div>
        </div>
    </div>
</section>

<?php renderPageEnd(); ?>