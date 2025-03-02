<?php
/**
 * Die Sidebar Template-Datei
 *
 * @package Derleiti_Modern
 * @version 2.2
 */

if (!is_active_sidebar('sidebar-1')) {
    return;
}
?>

<aside id="secondary" class="sidebar widget-area">
    <?php dynamic_sidebar('sidebar-1'); ?>
</aside><!-- #secondary -->
