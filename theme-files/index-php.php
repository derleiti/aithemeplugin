<?php
/**
 * Die Haupttemplate-Datei
 *
 * @package Derleiti_Modern
 * @version 2.2
 */

get_header();
?>

<div class="site-content">
    <main id="primary" class="content-area">
        <?php
        if (have_posts()) :
            
            if (is_home() && !is_front_page()) :
                ?>
                <header class="page-header">
                    <h1 class="page-title"><?php single_post_title(); ?></h1>
                </header>
                <?php
            endif;
            
            echo '<div class="posts-grid">';
            
            /* Beginne die Schleife */
            while (have_posts()) :
                the_post();
                
                /*
                 * Include the Post-Type-specific template for the content.
                 * If you want to override this in a child theme, then include a file
                 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
                 */
                get_template_part('template-parts/content', get_post_type());
                
            endwhile;
            
            echo '</div><!-- .posts-grid -->';
            
            // Previous/next page navigation.
            the_posts_pagination();
            
        else :
            
            get_template_part('template-parts/content', 'none');
            
        endif;
        ?>
    </main><!-- .content-area -->

    <?php get_sidebar(); ?>
</div><!-- .site-content -->

<?php
get_footer();
