<?php
/**
 * Template part für die Anzeige einer Nachricht, wenn keine Inhalte gefunden wurden
 *
 * @package Derleiti_Modern
 * @version 2.2
 */
?>

<section class="no-results not-found">
    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e('Nichts gefunden', 'derleiti-modern'); ?></h1>
    </header>

    <div class="page-content">
        <?php
        if (is_home() && current_user_can('publish_posts')) :
            printf(
                '<p>' . wp_kses(
                    /* translators: 1: link to WP admin new post page. */
                    __('Bereit, deinen ersten Beitrag zu veröffentlichen? <a href="%1$s">Hier geht\'s los</a>.', 'derleiti-modern'),
                    array(
                        'a' => array(
                            'href' => array(),
                        ),
                    )
                ) . '</p>',
                esc_url(admin_url('post-new.php'))
            );
        elseif (is_search()) :
            ?>
            <p><?php esc_html_e('Leider wurden keine passenden Ergebnisse für deine Suche gefunden. Bitte versuche es mit anderen Suchbegriffen.', 'derleiti-modern'); ?></p>
            <?php
            get_search_form();
        else :
            ?>
            <p><?php esc_html_e('Es sieht so aus, als könnten wir nicht finden, wonach du suchst. Vielleicht kann die Suche helfen.', 'derleiti-modern'); ?></p>
            <?php
            get_search_form();
        endif;
        ?>
    </div>
</section>
