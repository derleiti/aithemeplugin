<?php
/**
 * The header for our theme
 *
 * @package Derleiti_Modern
 * @version 2.2
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e('Skip to content', 'derleiti-modern'); ?></a>

    <header id="masthead" class="site-header">
        <div class="container">
            <div class="site-branding">
                <?php
                if (has_custom_logo()) :
                    the_custom_logo();
                else :
                ?>
                    <h1 class="site-title">
                        <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                            <?php bloginfo('name'); ?>
                        </a>
                    </h1>
                <?php endif; ?>

                <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span class="screen-reader-text"><?php esc_html_e('Menu', 'derleiti-modern'); ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                    </svg>
                </button>
            </div><!-- .site-branding -->

            <nav id="site-navigation" class="site-navigation">
                <?php
                // Fix: Register the menu location if not already registered
                if (!has_nav_menu('primary')) {
                    register_nav_menus(array(
                        'primary' => esc_html__('Primary Menu', 'derleiti-modern'),
                    ));
                }

                // Display the menu with a fallback
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'container'      => false,
                    'fallback_cb'    => function() {
                        echo '<ul id="primary-menu">';
                        echo '<li><a href="' . esc_url(admin_url('nav-menus.php')) . '">' . esc_html__('Add a menu', 'derleiti-modern') . '</a></li>';
                        echo '</ul>';
                    }
                ));
                ?>
            </nav><!-- #site-navigation -->
        </div><!-- .container -->
    </header><!-- #masthead -->