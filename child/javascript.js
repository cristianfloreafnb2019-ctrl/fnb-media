jQuery(($) => {
    if (!$('body').hasClass('postid-73') && !$('.cutom_product_album').length) return;

    var $tip = $('select[name="attribute_pa_alege-tipul-de-coperta"]');
    var $colorBR = $('select[name="attribute_pa_alege-culoarea-copertii-br"]');

    var $MaterialSelect = $('select[name="attribute_pa_material"]');

    var $colorBRRow = $colorBR.closest('tr, .woocommerce-product-attributes-item, .form-row');

    var $form = $('form.cart');
    var $addToCart = $form.find('button[type="submit"]');
    var colorTerm = 'nu';
    var img_upload = $(".wc-dnd-file-upload");

    var $notice = $('<div class="custom-color-notice" style="color:red; margin-bottom:10px; display:none;">⚠️ Te rog selectează o culoare înainte de a adăuga produsul în coș.</div>');
    $notice.insertBefore($addToCart.closest('.single_variation_wrap'));

    $colorBR.find(`option[value="${colorTerm}"]`).hide();

    function syncMaterial() {
        var material = ($MaterialSelect.val() || '').toLowerCase();
        console.log('syncMaterial', material);

        img_upload.show();
        $colorBRRow.show();

        // show all li items inside the $Color
        $colorBRRow.find('li').show();

        // reset color select
        $colorBR.val('');

        // hide values that do not start with material first letter
        $colorBRRow.find('li').each(function () {
            var $li = $(this);
            var val = ($li.data('value') || '').toLowerCase();
            if (!val.startsWith(material[0])) {
                $li.hide();
            }
        });

        checkValidSelection();
    }

    function syncColor() {
        var tipVal = ($tip.val() || '').toLowerCase();
        console.log('syncColor', tipVal);

        img_upload.hide();

        //hide file input for piele-ecologica
        if (tipVal === 'piele-ecologica') {
            img_upload.hide();
        } else {
            img_upload.show();
        }

        checkValidSelection();
    }

    function checkValidSelection() {
        var tipVal = ($tip.val() || '').toLowerCase();
        var invalid = false;

        if (tipVal === 'piele-ecologica' && ($colorBR.val() || '').toLowerCase() === colorTerm) invalid = true;

        if (invalid) {
            $addToCart.prop('disabled', true);
            $notice.show();
        } else {
            $addToCart.prop('disabled', false);
            $notice.hide();
        }
    }

    $tip.on('change', syncColor);
    $colorBR.on('change', checkValidSelection);
    $MaterialSelect.on('change', syncMaterial);

    //hide color row initially
    $colorBRRow.hide();

    let syncRunning = false;
    $(document).ajaxComplete((settings) => {
        if (syncRunning) return;
        if (settings.data?.includes('get_table_with_product_bulk_table')) {
            syncRunning = true;
            setTimeout(() => {
                syncColor();
                syncRunning = false;
            }, 500);
        }
    });

    $('.woocommerce-product-attributes-item__value p').each(function () {
        var $p = $(this);
        var text = $p.text().trim();
        var items = text.split(',').map(i => i.trim()).filter(i => i.toLowerCase() !== 'nu');
        $p.text(items.join(', '));
    });
});

document.addEventListener("DOMContentLoaded", () => {
    const galerie = document.querySelector('.galerie-container-custom');
    if (galerie) {
        const thumbnails = galerie.querySelectorAll('.thumbnails img');
        const mainImage = galerie.querySelector('#mainImage');

        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', () => {
                mainImage.src = thumb.src;
                thumbnails.forEach(img => { img.classList.remove('active'); });
                thumb.classList.add('active');
            });
        });
    }
});

document.addEventListener("scroll", () => {
    const header = document.querySelector(".header_section_custom .e-con-inner");
    const top_bar = document.querySelector(".top_bar_custom");
    const logo_custom = document.querySelector(".logo_custom");

    if (window.scrollY > 100) {
        header.classList.add("with-shadow");
        top_bar.classList.add("hidden-bar");
        logo_custom.classList.add("logo_small");
    } else {
        header.classList.remove("with-shadow");
        top_bar.classList.remove("hidden-bar");
        logo_custom.classList.remove("logo_small");
    }
});
