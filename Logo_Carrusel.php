function modern_glass_logo_slider() {
ob_start();
?>

<style>
.logo-slider-wrapper{
    position:relative;
    overflow:hidden;
    max-width:880px;
    margin:60px auto;
    padding:30px 0;
}

.logo-slider-wrapper{
    mask-image:linear-gradient(to right,transparent,black 10%,black 90%,transparent);
    -webkit-mask-image:linear-gradient(to right,transparent,black 10%,black 90%,transparent);
}

.logo-track{
    display:flex;
    gap:55px;
    width:max-content;
    animation:scroll 28s linear infinite;
}

.logo-slider-wrapper:hover .logo-track{
    animation-play-state:paused;
}

.logo-item{
    position:relative;
    width:140px;
    height:100px;
    flex-shrink:0;
    display:flex;
    justify-content:center;
    align-items:center;
    text-decoration:none;
    cursor:pointer;
    overflow:visible;
}

/* LOGO */
.logo-item img{
    max-width:75%;
    max-height:70%;
    object-fit:contain;
    position:relative;
    z-index:3;
    transition:.6s ease;
}

.logo-item:hover img{
    filter:brightness(1.4) saturate(1.7) contrast(1.2);
    transform:scale(1.15);
}

/* CHARCO */
.logo-item::before{
    content:"";
    position:absolute;
    width:120%;
    height:125%;
    background:rgba(255,255,255,0.18);
    backdrop-filter:blur(28px) saturate(190%);
    -webkit-backdrop-filter:blur(28px) saturate(190%);
    border:1px solid rgba(255,255,255,0.35);

    border-radius:
        60% 40% 55% 45% /
        45% 60% 40% 55%;

    box-shadow:
        inset 0 3px 6px rgba(255,255,255,0.6),
        inset 0 -6px 12px rgba(255,255,255,0.15),
        0 15px 40px rgba(0,0,0,0.08);

    transition:all .8s ease;
    z-index:1;
}

.logo-item:hover::before{
    transform:scale(1.12) rotate(6deg);
    backdrop-filter:blur(20px) saturate(240%);
}

/* RIPPLE REAL */
.ripple{
    position:absolute;
    border-radius:50%;
    border:2px solid rgba(255,255,255,0.8);
    pointer-events:none;
    transform:scale(0);
    animation:rippleOut 1.2s ease-out forwards;
    z-index:2;
}

.ripple.second{
    animation-delay:.15s;
}

@keyframes rippleOut{
    0%{
        transform:scale(0);
        opacity:.9;
    }
    70%{
        opacity:.4;
    }
    100%{
        transform:scale(8);
        opacity:0;
    }
}

@keyframes scroll{
    0%{transform:translateX(0);}
    100%{transform:translateX(-50%);}
}
</style>

<div class="logo-slider-wrapper">
    <div class="logo-track">

        <a href="https://cliente1.cl" target="_blank" class="logo-item">
            <img src="https://yetwater.cl/wp-content/uploads/2026/03/logo5.webp" alt="">
        </a>

        <a href="https://cliente2.cl" target="_blank" class="logo-item">
            <img src="https://yetwater.cl/wp-content/uploads/2026/03/logo4.webp" alt="">
        </a>

        <a href="https://cliente3.cl" target="_blank" class="logo-item">
            <img src="https://yetwater.cl/wp-content/uploads/2026/03/logo3.webp" alt="">
        </a>

        <a href="https://cliente1.cl" target="_blank" class="logo-item">
            <img src="https://yetwater.cl/wp-content/uploads/2026/03/logo2.webp" alt="">
        </a>

        <a href="https://cliente2.cl" target="_blank" class="logo-item">
            <img src="https://yetwater.cl/wp-content/uploads/2026/03/logo1.webp" alt="">
        </a>

        <!-- duplicados -->
        <a href="https://cliente1.cl" target="_blank" class="logo-item">
            <img src="https://yetwater.cl/wp-content/uploads/2026/03/logo5.webp" alt="">
        </a>

        <a href="https://cliente2.cl" target="_blank" class="logo-item">
            <img src="https://yetwater.cl/wp-content/uploads/2026/03/logo4.webp" alt="">
        </a>

    </div>
</div>

<script>
document.querySelectorAll('.logo-item').forEach(item=>{
    item.addEventListener('mouseenter', function(e){

        const rect = this.getBoundingClientRect();
        const size = 20;

        const ripple1 = document.createElement('span');
        ripple1.classList.add('ripple');

        const ripple2 = document.createElement('span');
        ripple2.classList.add('ripple','second');

        [ripple1, ripple2].forEach(r=>{
            r.style.width = size + "px";
            r.style.height = size + "px";
            r.style.left = (e.clientX - rect.left - size/2) + "px";
            r.style.top = (e.clientY - rect.top - size/2) + "px";
            this.appendChild(r);

            setTimeout(()=>{ r.remove(); },1200);
        });

    });
});
</script>

<?php
return ob_get_clean();
}
add_shortcode('modern_logo_slider','modern_glass_logo_slider');
