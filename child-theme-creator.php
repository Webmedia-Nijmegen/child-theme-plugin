<?php
/*
Plugin Name: Child Theme Creator
Plugin URI: https://webmedia-nijmegen.nl
Description: Een plugin om een child theme te genereren en te activeren.
Version: 1.0
Author: Webmedia - Nijmegen B.V.
Author URI: https://webmedia-nijmegen.nl
License: GPL2
*/

// Voeg een menu-item toe aan het admin-dashboard
add_action('admin_menu', 'child_theme_creator_menu');

function child_theme_creator_menu() {
    add_menu_page(
        'Child Theme Creator',
        'Child Theme Creator',
        'manage_options',
        'child-theme-creator',
        'child_theme_creator_page',
        'dashicons-admin-customizer',
        100
    );
}

// Functie voor de pagina van de plugin
function child_theme_creator_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $parent_theme = sanitize_text_field($_POST['parent_theme']);
        $child_theme_name = sanitize_text_field($_POST['child_theme_name']);

        if (create_child_theme($parent_theme, $child_theme_name)) {
            echo '<div class="updated"><p>Child theme succesvol aangemaakt en geactiveerd!</p></div>';
        } else {
            echo '<div class="error"><p>Er is iets misgegaan bij het aanmaken van het child theme.</p></div>';
        }
    }

    $themes = wp_get_themes();

    echo '<div class="wrap">';
    echo '<h1>Child Theme Creator</h1>';
    echo '<form method="POST">';
    echo '<label for="parent_theme">Kies een parent theme:</label><br/>';
    echo '<select name="parent_theme" id="parent_theme">';

    foreach ($themes as $slug => $theme) {
        echo '<option value="' . esc_attr($slug) . '">' . esc_html($theme->get('Name')) . '</option>';
    }

    echo '</select><br/><br/>';
    echo '<label for="child_theme_name">Naam van het child theme:</label><br/>';
    echo '<input type="text" name="child_theme_name" id="child_theme_name" required /><br/><br/>';
    echo '<input type="submit" class="button button-primary" value="Maak Child Theme" />';
    echo '</form>';
    echo '</div>';
}

// Functie om het child theme te maken
function create_child_theme($parent_theme, $child_theme_name) {
    $parent_theme_dir = get_theme_root() . '/' . $parent_theme;

    if (!is_dir($parent_theme_dir)) {
        return false;
    }

    $child_theme_dir = get_theme_root() . '/' . sanitize_title($child_theme_name);

    if (!mkdir($child_theme_dir) && !is_dir($child_theme_dir)) {
        return false;
    }

    $style_css = "/*\nTheme Name: $child_theme_name\nTemplate: $parent_theme\n*/";

    if (!file_put_contents($child_theme_dir . '/style.css', $style_css)) {
        return false;
    }

    $functions_php = "<?php\n// Voeg hier eventuele functies toe voor het child theme\n";

    if (!file_put_contents($child_theme_dir . '/functions.php', $functions_php)) {
        return false;
    }

    switch_theme(sanitize_title($child_theme_name));

    return true;
}
