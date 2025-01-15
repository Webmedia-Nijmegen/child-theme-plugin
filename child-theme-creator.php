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

// Controleer op een geldige licentiesleutel
add_action('admin_init', 'child_theme_creator_check_license');

function child_theme_creator_check_license() {
    $saved_license_key = get_option('child_theme_creator_license_key');

    // De gehashte waarde van de licentiecode (bijvoorbeeld: hash('sha256', 'JOUW-UNIEKE-LICENSE-KEY'))
    $valid_license_hash = '5e884898da28047151d0e56f8dc6292773603d0d6aabbddc05a6c4d689d6db3c';

    if (!$saved_license_key || hash('sha256', $saved_license_key) !== $valid_license_hash) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>De Child Theme Creator plugin vereist een geldige licentiesleutel om te werken. Voeg uw licentiesleutel toe in de instellingen.</p></div>';
        });

        if (!isset($_GET['page']) || $_GET['page'] !== 'child-theme-creator-license') {
            wp_redirect(admin_url('admin.php?page=child-theme-creator-license'));
            exit;
        }
    }
}

// Voeg een licentiepagina toe aan het menu
add_action('admin_menu', 'child_theme_creator_license_menu');

function child_theme_creator_license_menu() {
    add_submenu_page(
        'child-theme-creator',
        'Licentie Sleutel',
        'Licentie',
        'manage_options',
        'child-theme-creator-license',
        'child_theme_creator_license_page'
    );
}

// Functie voor de licentiepagina
function child_theme_creator_license_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $license_key = sanitize_text_field($_POST['license_key']);
        update_option('child_theme_creator_license_key', $license_key);
        echo '<div class="updated"><p>Licentiesleutel opgeslagen.</p></div>';
    }

    $saved_license_key = get_option('child_theme_creator_license_key', '');

    echo '<div class="wrap">';
    echo '<h1>Licentiesleutel</h1>';
    echo '<form method="POST">';
    echo '<label for="license_key">Voer uw licentiesleutel in:</label><br/>';
    echo '<input type="text" name="license_key" id="license_key" value="' . esc_attr($saved_license_key) . '" required /><br/><br/>';
    echo '<input type="submit" class="button button-primary" value="Opslaan" />';
    echo '</form>';
    echo '</div>';
}

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
