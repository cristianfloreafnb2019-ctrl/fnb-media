<?php
function my_theme_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_uri(), array(), '1.0' );
    wp_enqueue_style( 'tailwindcss', 'https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css' );
    wp_enqueue_script( 'script', get_stylesheet_directory_uri() . '/javascript.js', array(), false, true );
    wp_enqueue_style( 'load-fa', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );


function custom_product_box($atts) {
    ob_start();

    $atts = shortcode_atts( array(
        'id' => ''
    ), $atts );

    if ( $atts['id'] ) {
        $product_id = $atts['id'];
        $post_object = get_post( $product_id );
        if ( $post_object ) {
            // Setăm contextul global
            $GLOBALS['post'] = $post_object;
            setup_postdata( $post_object );

            $product = wc_get_product( $product_id );

            echo '<div class="my-product-box">';

            // Imagine principală
//             echo wp_get_attachment_image( $product->get_image_id(), 'large' );

            // Variabile + Add to Cart
            if ( $product->is_type('variable') || $product->is_type('simple') ) {
                woocommerce_template_single_add_to_cart();
            }

            echo '</div>';

            // Resetăm postdata
            wp_reset_postdata();
        }
    }

    return ob_get_clean();
}
add_shortcode('custom_product_box', 'custom_product_box');


/**
 * Shortcode dinamic pentru galerie slideshow produs WooCommerce.
 * Preia imaginile atașate unui ID de produs.
 * Generează HTML și JavaScript-ul necesar pentru interactivitate.
 *
 * Exemplu de utilizare: [galerie_slideshow prod_id="123"]
 */

// Înregistrează shortcode-ul doar dacă WooCommerce este activ
if (class_exists('WooCommerce')) {

    function galerie_slideshow_shortcode_fix($atts) {
        // 1. Procesează atributele shortcode-ului
        $atts = shortcode_atts(array(
            'prod_id' => 0, // ID-ul produsului, default 0
        ), $atts, 'galerie_slideshow');

        $product_id = intval($atts['prod_id']);

        // 2. Verifică validitatea ID-ului și existența produsului
        if (!$product_id || get_post_type($product_id) !== 'product') {
            return '';
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return '';
        }

        // 3. Colectează ID-urile imaginilor
        $all_image_ids = [];

        // Adaugă imaginea principală (featured)
        $featured_image_id = $product->get_image_id();
        if ($featured_image_id) {
            $all_image_ids[] = $featured_image_id;
        }

        // Adaugă imaginile din galerie
        $gallery_image_ids = $product->get_gallery_image_ids();
        if (!empty($gallery_image_ids)) {
            $all_image_ids = array_merge($all_image_ids, $gallery_image_ids);
        }

        // Elimină duplicatele și re-indexează
        $all_image_ids = array_values(array_unique($all_image_ids));

        // 4. Verifică dacă există imagini
        if (empty($all_image_ids)) {
            return '';
        }

        // 5. Obține datele pentru imaginea mare (prima din listă)
        $first_image_id = $all_image_ids[0];
        $main_image_data = wp_get_attachment_image_src($first_image_id, 'large');
        $main_image_url = $main_image_data ? $main_image_data[0] : '';
        $main_image_alt = get_post_meta($first_image_id, '_wp_attachment_image_alt', true) ?: get_the_title($first_image_id);

        // 6. Începe buffer-ul de ieșire
        ob_start();
        ?>

        <div class="galerie-container-custom">
            <div class="thumbnails">
                <?php
                foreach ($all_image_ids as $index => $image_id) {
                    $thumb_data = wp_get_attachment_image_src($image_id, 'large');
                    $large_data = wp_get_attachment_image_src($image_id, 'large'); // Preia URL-ul imaginii 'large'
                    
                    if ($thumb_data) {
                        $thumb_url = $thumb_data[0];
                        $large_url = $large_data ? $large_data[0] : $thumb_url; // Folosește large_url, sau thumb_url ca fallback
                        $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: get_the_title($image_id);
                        $class = ($index === 0) ? 'active' : '';

                        // MODIFICARE CHEIE: Adaugă atributele data-large-src și data-large-alt
                        echo '<img src="' . esc_url($thumb_url) . '" ' .
                             'alt="' . esc_attr($image_alt) . '" ' .
                             'class="' . esc_attr($class) . '" ' .
                             'data-large-src="' . esc_url($large_url) . '" ' .
                             'data-large-alt="' . esc_attr($image_alt) . '">';
                    }
                }
                ?>
            </div>

            <div class="imagine-mare">
                <?php if ($main_image_url): ?>
                    <img id="mainImage" src="<?php echo esc_url($main_image_url); ?>" alt="<?php echo esc_attr($main_image_alt); ?>">
                <?php endif; ?>
            </div>
        </div>

        <?php
        // 7. Adaugă JavaScript-ul inline (o singură dată per pagină)
        static $js_added = false;
        if (!$js_added) {
            $js_added = true;
            // Folosește wp_footer pentru a adăuga scriptul la sfârșitul paginii
            add_action('wp_footer', function() {
                ?>
                <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    // Iterează prin fiecare container de galerie de pe pagină
                    document.querySelectorAll('.galerie-container-custom').forEach(function(gallery) {
                        
                        // Găsește elementele SPECIFICE acestei galerii
                        var thumbnails = gallery.querySelectorAll('.thumbnails img');
                        var mainImage = gallery.querySelector('#mainImage'); // Găsește mainImage *în interiorul* acestui container
                        
                        if (!mainImage || thumbnails.length === 0) {
                            return; // Nu continua dacă elementele esențiale lipsesc
                        }

                        thumbnails.forEach(function(thumbnail) {
                            thumbnail.addEventListener('click', function() {
                                
                                // Preia URL-ul și alt-textul din atributele data-*
                                var largeSrc = this.dataset.largeSrc;
                                var largeAlt = this.dataset.largeAlt;

                                if (largeSrc) {
                                    // Setează imaginea principală
                                    mainImage.src = largeSrc;
                                    mainImage.alt = largeAlt;

                                    // Găsește thumbnail-ul activ curent *în interiorul* galeriei
                                    var currentActive = gallery.querySelector('.thumbnails img.active');
                                    if (currentActive) {
                                        currentActive.classList.remove('active');
                                    }
                                    
                                    // Adaugă 'active' la thumbnail-ul pe care s-a dat click
                                    this.classList.add('active');
                                }
                            });
                        });
                    });
                });
                </script>
                <?php
            }, 99); // 99 asigură rularea târzie în footer
        }
        
        // 8. Returnează conținutul buffer-ului
        return ob_get_clean();
    }

    // Înregistrează shortcode-ul (înlocuiește funcția veche)
    add_shortcode('galerie_slideshow', 'galerie_slideshow_shortcode_fix');

} // Sfârșitul verificării class_exists('WooCommerce')
?>