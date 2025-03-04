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
<?php if ( have_posts() ) : ?>

<?php if ( is_home() && ! is_front_page() ) : ?>
<header class="page-header">
<h1 class="page-title"><?php single_post_title(); ?></h1>
</header>
<?php endif; ?>

<div class="posts-grid">
<?php
/* Beginne die Schleife */
while ( have_posts() ) :
    the_post();
// Lade das post-spezifische Template (z. B. content-post.php)
get_template_part( 'template-parts/content', get_post_type() );
endwhile;
?>
</div><!-- .posts-grid -->

<?php the_posts_pagination(); ?>

<?php else : ?>

<?php get_template_part( 'template-parts/content', 'none' ); ?>

<?php endif; ?>
</main><!-- .content-area -->

<?php get_sidebar(); ?>
</div><!-- .site-content -->

<?php get_footer(); ?>
