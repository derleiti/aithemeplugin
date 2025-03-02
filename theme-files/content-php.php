<?php
/**
 * Template part für die Anzeige von Inhalten in der index.php
 *
 * @package Derleiti_Modern
 * @version 2.2
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?>>
    
    <?php if (has_post_thumbnail()) : ?>
    <div class="post-thumbnail">
        <a href="<?php the_permalink(); ?>">
            <?php the_post_thumbnail('derleiti-card'); ?>
        </a>
    </div>
    <?php endif; ?>
    
    <div class="post-content">
        <div class="post-meta">
            <span class="post-date">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <?php echo get_the_date(); ?>
            </span>
            <span class="post-author">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <?php the_author(); ?>
            </span>
        </div>
        
        <h2 class="post-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>
        
        <div class="post-excerpt">
            <?php the_excerpt(); ?>
        </div>
        
        <a href="<?php the_permalink(); ?>" class="read-more"><?php esc_html_e('Weiterlesen', 'derleiti-modern'); ?></a>
    </div>
</article>
