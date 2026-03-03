/**
 * YetWater - Blog Posts Visual Shortcode
 * Uso: [yetwater_blog cantidad="6" categoria=""]
 * Instalar en: Code Snippets > Add New > Run Snippet Everywhere
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function yetwater_blog_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'cantidad'  => 6,
        'categoria' => '',
    ], $atts, 'yetwater_blog' );

    $args = [
        'post_type'      => 'post',
        'posts_per_page' => intval( $atts['cantidad'] ),
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    if ( ! empty( $atts['categoria'] ) ) {
        $args['category_name'] = sanitize_text_field( $atts['categoria'] );
    }

    $query = new WP_Query( $args );

    if ( ! $query->have_posts() ) {
        return '<p style="text-align:center;color:#5da3a8;">No hay entradas disponibles.</p>';
    }

    // ─── CSS ─────────────────────────────────────────────────────────────────
    ob_start(); ?>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Assistant:wght@400;600;700;800;900&display=swap');

    .yw-blog-wrap {
        font-family: 'Assistant', sans-serif;
        padding: 60px 20px;
        background-image: linear-gradient(-45deg, #ffffff, #e6f7f8, #5da3a8, #ffffff);
        background-size: 400% 400%;
        animation: ywBgFlow 18s ease infinite;
        border-radius: 40px;
        position: relative;
        overflow: hidden;
    }
    .yw-blog-wrap::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 60% 0%, rgba(255,255,255,0.7) 0%, transparent 60%);
        pointer-events: none;
    }
    @keyframes ywBgFlow {
        0%   { background-position: 0% 50%; }
        50%  { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* Burbujas flotantes de fondo */
    .yw-bubbles {
        position: absolute;
        inset: 0;
        pointer-events: none;
        overflow: hidden;
        z-index: 0;
    }
    .yw-bubble {
        position: absolute;
        border-radius: 50%;
        background: rgba(93,163,168,0.12);
        border: 1px solid rgba(93,163,168,0.2);
        animation: ywBubbleRise linear infinite;
    }
    .yw-bubble:nth-child(1)  { width:60px;  height:60px;  left:5%;   animation-duration:12s; animation-delay:0s;   }
    .yw-bubble:nth-child(2)  { width:40px;  height:40px;  left:15%;  animation-duration:9s;  animation-delay:2s;   }
    .yw-bubble:nth-child(3)  { width:90px;  height:90px;  left:30%;  animation-duration:15s; animation-delay:1s;   }
    .yw-bubble:nth-child(4)  { width:30px;  height:30px;  left:50%;  animation-duration:10s; animation-delay:4s;   }
    .yw-bubble:nth-child(5)  { width:70px;  height:70px;  left:65%;  animation-duration:13s; animation-delay:0.5s; }
    .yw-bubble:nth-child(6)  { width:50px;  height:50px;  left:80%;  animation-duration:11s; animation-delay:3s;   }
    .yw-bubble:nth-child(7)  { width:35px;  height:35px;  left:90%;  animation-duration:8s;  animation-delay:1.5s; }
    @keyframes ywBubbleRise {
        0%   { transform: translateY(110%) scale(1); opacity: 0; }
        10%  { opacity: 1; }
        90%  { opacity: 0.6; }
        100% { transform: translateY(-20%) scale(1.2); opacity: 0; }
    }

    /* ── Encabezado ── */
    .yw-blog-header {
        text-align: center;
        margin-bottom: 50px;
        position: relative;
        z-index: 2;
    }
    .yw-blog-label {
        display: inline-block;
        font-size: 13px;
        font-weight: 800;
        color: #5da3a8;
        text-transform: uppercase;
        letter-spacing: 3px;
        margin-bottom: 12px;
    }
    .yw-blog-title {
        font-size: 42px;
        font-weight: 900;
        color: #1a2a3a;
        line-height: 1.1;
        margin: 0;
    }
    .yw-blog-title span { color: #5da3a8; }
    .yw-blog-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-top: 20px;
    }
    .yw-blog-divider::before,
    .yw-blog-divider::after {
        content: '';
        width: 60px;
        height: 2px;
        background: linear-gradient(to right, transparent, #5da3a8);
    }
    .yw-blog-divider::after { background: linear-gradient(to left, transparent, #5da3a8); }
    .yw-drop-icon {
        width: 10px; height: 12px;
        background: #5da3a8;
        border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
        transform: rotate(180deg);
    }

    /* ── Grid de Tarjetas ── */
    .yw-blog-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 28px;
        max-width: 1300px;
        margin: 0 auto;
        position: relative;
        z-index: 2;
    }
    @media (max-width: 1024px) { .yw-blog-grid { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 640px)  { .yw-blog-grid { grid-template-columns: 1fr; } }

    /* ── Tarjeta Individual ── */
    .yw-card {
        background: rgba(255,255,255,0.55);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.75);
        border-radius: 28px;
        overflow: hidden;
        transition: transform 0.4s ease, box-shadow 0.4s ease;
        box-shadow: 0 8px 30px rgba(93,163,168,0.1);
        opacity: 0;
        animation: ywCardIn 0.6s ease forwards;
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: inherit;
    }
    .yw-card:nth-child(1) { animation-delay: 0.05s; }
    .yw-card:nth-child(2) { animation-delay: 0.15s; }
    .yw-card:nth-child(3) { animation-delay: 0.25s; }
    .yw-card:nth-child(4) { animation-delay: 0.35s; }
    .yw-card:nth-child(5) { animation-delay: 0.45s; }
    .yw-card:nth-child(6) { animation-delay: 0.55s; }
    @keyframes ywCardIn {
        from { opacity:0; transform: translateY(30px); }
        to   { opacity:1; transform: translateY(0); }
    }
    .yw-card:hover {
        transform: translateY(-10px) scale(1.01);
        box-shadow: 0 25px 50px rgba(93,163,168,0.25);
    }

    /* ── Imagen con overlay de ola ── */
    .yw-card-img-wrap {
        position: relative;
        height: 210px;
        overflow: hidden;
        flex-shrink: 0;
    }
    .yw-card-img-wrap img {
        width: 100%; height: 100%;
        object-fit: cover;
        transition: transform 0.6s ease;
        display: block;
    }
    .yw-card:hover .yw-card-img-wrap img { transform: scale(1.08); }

    /* Overlay de ola SVG */
    .yw-wave-overlay {
        position: absolute;
        bottom: -2px; left: 0;
        width: 100%; height: 55px;
        z-index: 2;
    }

    /* Categoría badge */
    .yw-cat-badge {
        position: absolute;
        top: 14px; left: 14px;
        background: rgba(93,163,168,0.85);
        backdrop-filter: blur(8px);
        color: white;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        padding: 5px 12px;
        border-radius: 20px;
        z-index: 3;
    }

    /* Placeholder si no hay imagen */
    .yw-card-no-img {
        height: 210px;
        background: linear-gradient(135deg, #e6f7f8 0%, #b2dfe2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        position: relative;
    }
    .yw-no-img-icon {
        font-size: 48px;
        opacity: 0.4;
    }

    /* ── Contenido de la tarjeta ── */
    .yw-card-body {
        padding: 22px 24px 26px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    .yw-card-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: #7a9aaa;
        font-weight: 600;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .yw-meta-dot {
        width: 4px; height: 4px;
        border-radius: 50%;
        background: #5da3a8;
        display: inline-block;
    }
    .yw-card-title {
        font-size: 18px;
        font-weight: 800;
        color: #1a2a3a;
        line-height: 1.3;
        margin: 0 0 12px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .yw-card-excerpt {
        font-size: 14px;
        color: #5a7080;
        line-height: 1.65;
        flex: 1;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .yw-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 16px;
        border-top: 1px solid rgba(93,163,168,0.15);
    }
    .yw-read-more {
        font-size: 13px;
        font-weight: 800;
        color: #5da3a8;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: gap 0.3s ease;
        text-decoration: none;
    }
    .yw-card:hover .yw-read-more { gap: 12px; }
    .yw-arrow {
        width: 20px; height: 20px;
        background: linear-gradient(45deg, #5da3a8, #76c5ca);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        transition: transform 0.3s ease;
    }
    .yw-card:hover .yw-arrow { transform: rotate(-45deg); }
    .yw-arrow svg { width: 10px; height: 10px; fill: white; }

    /* Ripple en hover de la tarjeta */
    .yw-card-ripple {
        position: absolute;
        bottom: 0; left: 50%;
        transform: translateX(-50%) scale(0);
        width: 100%; height: 6px;
        background: linear-gradient(to right, transparent, rgba(93,163,168,0.5), transparent);
        border-radius: 50%;
        transition: transform 0.5s ease, opacity 0.5s ease;
        opacity: 0;
        pointer-events: none;
    }
    .yw-card:hover .yw-card-ripple {
        transform: translateX(-50%) scale(1.2);
        opacity: 1;
    }
    </style>

    <?php
    // ─── HTML ────────────────────────────────────────────────────────────────
    ?>
    <div class="yw-blog-wrap">

        <!-- Burbujas -->
        <div class="yw-bubbles">
            <?php for ($b = 0; $b < 7; $b++) echo '<div class="yw-bubble"></div>'; ?>
        </div>

        <!-- Encabezado -->
        <div class="yw-blog-header">
            <span class="yw-blog-label">Novedades &amp; Tendencias</span>
            <h2 class="yw-blog-title">Últimas <span>Noticias</span></h2>
            <div class="yw-blog-divider"><div class="yw-drop-icon"></div></div>
        </div>

        <!-- Grid -->
        <div class="yw-blog-grid">
        <?php while ( $query->have_posts() ) : $query->the_post();
            $id        = get_the_ID();
            $link      = get_permalink();
            $title     = get_the_title();
            $date      = get_the_date('d M, Y');
            $excerpt   = wp_trim_words( get_the_excerpt(), 22, '…' );
            $thumb     = get_the_post_thumbnail_url( $id, 'medium_large' );
            $cats      = get_the_category();
            $cat_name  = ! empty( $cats ) ? esc_html( $cats[0]->name ) : '';
        ?>
            <a class="yw-card" href="<?php echo esc_url( $link ); ?>">

                <?php if ( $thumb ) : ?>
                <div class="yw-card-img-wrap">
                    <?php if ( $cat_name ) : ?>
                        <span class="yw-cat-badge"><?php echo $cat_name; ?></span>
                    <?php endif; ?>
                    <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
                    <!-- Ola SVG -->
                    <svg class="yw-wave-overlay" viewBox="0 0 1440 54" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0,32 C240,54 480,0 720,32 C960,64 1200,8 1440,32 L1440,54 L0,54 Z" fill="rgba(255,255,255,0.55)"/>
                    </svg>
                </div>
                <?php else : ?>
                <div class="yw-card-no-img">
                    <?php if ( $cat_name ) : ?>
                        <span class="yw-cat-badge"><?php echo $cat_name; ?></span>
                    <?php endif; ?>
                    <span class="yw-no-img-icon">💧</span>
                    <svg class="yw-wave-overlay" viewBox="0 0 1440 54" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0,32 C240,54 480,0 720,32 C960,64 1200,8 1440,32 L1440,54 L0,54 Z" fill="rgba(255,255,255,0.55)"/>
                    </svg>
                </div>
                <?php endif; ?>

                <div class="yw-card-body">
                    <div class="yw-card-meta">
                        <?php echo esc_html( $date ); ?>
                        <?php if ( $cat_name ) : ?>
                            <span class="yw-meta-dot"></span>
                            <?php echo $cat_name; ?>
                        <?php endif; ?>
                    </div>
                    <h3 class="yw-card-title"><?php echo esc_html( $title ); ?></h3>
                    <?php if ( $excerpt ) : ?>
                        <p class="yw-card-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                    <?php endif; ?>
                    <div class="yw-card-footer">
                        <span class="yw-read-more">
                            Leer más
                            <span class="yw-arrow">
                                <svg viewBox="0 0 10 10"><path d="M2 8 L8 2 M3 2 L8 2 L8 7"/></svg>
                            </span>
                        </span>
                    </div>
                </div>

                <div class="yw-card-ripple"></div>
            </a>
        <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'yetwater_blog', 'yetwater_blog_shortcode' );
