<?php
/**
 * Die Footer-Template-Datei
 *
 * @package Derleiti_Modern
 * @version 2.2
 */
?>

    <footer id="colophon" class="site-footer">
        <div class="footer-content">
            <div class="footer-widgets">
                <?php if (is_active_sidebar('footer-1')) : ?>
                    <div class="footer-widget-area">
                        <?php dynamic_sidebar('footer-1'); ?>
                    </div>
                <?php endif; ?>
                <?php if (is_active_sidebar('footer-2')) : ?>
                    <div class="footer-widget-area">
                        <?php dynamic_sidebar('footer-2'); ?>
                    </div>
                <?php endif; ?>
                <?php if (is_active_sidebar('footer-3')) : ?>
                    <div class="footer-widget-area">
                        <?php dynamic_sidebar('footer-3'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div><!-- .footer-content -->
        
        <div class="footer-bottom">
            <div class="container">
                <div class="site-info">
                    <?php
                    $footer_text = get_theme_mod('footer_text', sprintf(__('© %s %s. Alle Rechte vorbehalten.', 'derleiti-modern'), date('Y'), get_bloginfo('name')));
                    echo wp_kses_post($footer_text);
                    ?>
                </div><!-- .site-info -->
            </div>
        </div><!-- .footer-bottom -->
    </footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
