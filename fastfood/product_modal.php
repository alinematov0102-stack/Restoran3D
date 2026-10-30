<?php
// product_modal.php - Mahsulot oynasi (rasm + premium 3D ko'rinish).
// index.php va products.php ichida bitta joyda ishlatiladi:  include __DIR__ . '/product_modal.php';
require_once __DIR__ . '/model3d_helper.php';
?>
<!-- Mahsulot haqida oyna (modal) -->
<div class="product-modal-overlay" id="productModalOverlay" onclick="if(event.target===this) closeProductModal()">
    <div class="product-modal pm3d">
        <button class="product-modal-close" onclick="closeProductModal()">✕</button>

        <div class="pm-stage" id="pmStage">
            <img id="modalImg" src="" alt="">

            <model-viewer id="modalModel" class="pm-model" alt="3D"
                camera-controls
                auto-rotate auto-rotate-delay="0" rotation-per-second="22deg"
                interaction-prompt="auto"
                camera-orbit="25deg 72deg auto"
                min-camera-orbit="auto 15deg auto" max-camera-orbit="auto 95deg auto"
                shadow-intensity="1.3" shadow-softness="1"
                exposure="1.05" environment-image="neutral" tone-mapping="neutral"
                ar ar-modes="webxr scene-viewer quick-look"
                touch-action="pan-y">
                <div slot="progress-bar" class="pm-progress"><span><?= htmlspecialchars(ff_3d_text('loading')) ?></span><i></i></div>
                <button slot="ar-button" class="pm-ar-btn" type="button">📱 <?= htmlspecialchars(ff_3d_text('ar')) ?></button>
            </model-viewer>

            <div class="pm-tabs" id="pmTabs" hidden>
                <button type="button" class="pm-tab" data-mode="photo">📷 <?= htmlspecialchars(ff_3d_text('photo')) ?></button>
                <button type="button" class="pm-tab active" data-mode="3d">🧊 3D</button>
            </div>
            <div class="pm-hint" id="pmHint" hidden><?= htmlspecialchars(ff_3d_text('hint')) ?></div>
        </div>

        <div class="product-modal-body">
            <h3 id="modalName"></h3>
            <p id="modalDesc"></p>
            <span class="price" id="modalPrice" style="font-size:20px;"></span>
            <a href="#" id="modalOrderBtn" class="btn" style="width:100%; display:block; text-align:center; margin-top:18px;"><?= t('order_now') ?></a>
        </div>
    </div>
</div>

<!-- Google <model-viewer>: 3D modelni ko'rsatish uchun (faqat shu oyna ochilganda model yuklanadi) -->
<script>
// model-viewer katta fayl: sahifa to'liq yuklangach, bo'sh vaqtda yuklanadi (sayt tezroq ochiladi)
(function(){function L(){var s=document.createElement('script');s.type='module';s.src='../js/model-viewer.min.js';document.head.appendChild(s)}
window.addEventListener('load',function(){('requestIdleCallback' in window)?requestIdleCallback(L,{timeout:3000}):setTimeout(L,1200)});})();
</script>
<script>
(function () {
    var overlay = document.getElementById('productModalOverlay');
    var stage   = document.getElementById('pmStage');
    var img     = document.getElementById('modalImg');
    var model   = document.getElementById('modalModel');
    var tabs    = document.getElementById('pmTabs');
    var hint    = document.getElementById('pmHint');

    function setMode(mode) {
        var is3d = (mode === '3d');
        stage.classList.toggle('mode-3d', is3d);
        hint.hidden = !is3d;
        tabs.querySelectorAll('.pm-tab').forEach(function (b) {
            b.classList.toggle('active', b.dataset.mode === mode);
        });
    }

    tabs.addEventListener('click', function (e) {
        var b = e.target.closest('.pm-tab');
        if (b) setMode(b.dataset.mode);
    });

    window.openProductModal = function (imgEl) {
        var d = imgEl.dataset;
        img.src = d.img;
        document.getElementById('modalName').textContent  = d.name;
        document.getElementById('modalDesc').textContent  = d.desc;
        document.getElementById('modalPrice').textContent = d.price + " so'm";
        document.getElementById('modalOrderBtn').href = 'cart.php?action=add&id=' + d.id;

        if (d.model) {
            model.setAttribute('poster', d.img);
            model.setAttribute('alt', d.name);
            model.setAttribute('src', d.model);   // model aynan shu paytda yuklanadi
            tabs.hidden = false;
            setMode('3d');                         // 3D birinchi ko'rinadi
        } else {
            model.removeAttribute('src');
            tabs.hidden = true;
            setMode('photo');
        }
        overlay.classList.add('open');
    };

    window.closeProductModal = function () {
        overlay.classList.remove('open');
        model.removeAttribute('src');              // fon rejimida resurs sarflamasin
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') window.closeProductModal();
    });
})();
</script>
