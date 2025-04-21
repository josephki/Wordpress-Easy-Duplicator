<?php
/**
 * Plugin Name: WP Easy Post Duplicator
 * Plugin URI: https://web-werkstatt.at
 * Description: Fügt eine "Duplizieren"-Funktion für Posts, Pages und Custom Post Types hinzu.
 * Version: 3.0
 * Author: Joseph Kisler - Webwerkstatt
 * Text Domain: wp-easy-post-duplicator
 * Domain Path: /languages
 */

// Laden der Textdomain für Übersetzungen
function wpepd_load_textdomain() {
    load_plugin_textdomain('wp-easy-post-duplicator', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'wpepd_load_textdomain');

// Sicherheitscheck
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// =============================================
// PRÄZISE ASENHA-DUPLIKATIONSFUNKTION-PRÜFUNG
// =============================================

// Prüfung nur auf aktive ASENHA-Duplikationsfunktion
$has_active_asenha_duplication = false;

// Prüfe direkt die ASENHA-Option auf aktivierte Duplikationsfunktion
$asenha_options = get_option('admin_site_enhancements');
if ($asenha_options && is_array($asenha_options) && 
    isset($asenha_options['content_duplication']) && 
    $asenha_options['content_duplication'] === true) {
    $has_active_asenha_duplication = true;
}

// Wenn die ASENHA-Duplikationsfunktion aktiv ist, führe keine der eigenen Funktionen aus
if ($has_active_asenha_duplication) {
    // Füge eine spezialisierte Funktion hinzu, um unsere "Duplizieren"-Links in der Beitragsübersicht zu entfernen
    add_action('admin_footer', 'wpepd_remove_duplicate_links');
    
    function wpepd_remove_duplicate_links() {
        global $pagenow;
        if ($pagenow === 'edit.php') {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Finde und entferne unsere Duplizieren-Links
                $('a').filter(function() {
                    return $(this).text() === <?php echo json_encode(__('Duplizieren', 'wp-easy-post-duplicator')); ?> && 
                           $(this).attr('href') && 
                           $(this).attr('href').indexOf('wpepd_duplicate_post') !== -1;
                }).closest('span').remove();
            });
            </script>
            <?php
        }
    }
    
    return; // Beende die Ausführung des Plugins
}

// =============================================
// FORTFÜHRUNG DES NORMALEN PLUGIN-CODES WENN ASENHA-DUPLIKATION INAKTIV IST
// =============================================

// Prüfen auf andere Duplikations-Plugins bei der Aktivierung
register_activation_hook(__FILE__, 'wpepd_check_for_duplicate_plugins');

function wpepd_check_for_duplicate_plugins() {
    // Liste bekannter Duplikations-Plugins
    $duplicate_plugins = array(
        'duplicate-post/duplicate-post.php',
        'duplicate-page/duplicatepage.php',
        'post-duplicator/post-duplicator.php',
        'duplicate-post-page-menu-custom-post-type/duplicate-post-page-menu-custom-post-type.php',
        'duplicator/duplicator.php',
        'yoast-duplicate-post/duplicate-post.php',
    );
    
    $active_plugins = get_option('active_plugins');
    $conflicting_plugins = array();
    
    foreach ($duplicate_plugins as $plugin) {
        if (in_array($plugin, $active_plugins)) {
            // Nur wenn die Datei existiert
            if (file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                $conflicting_plugins[] = $plugin_data['Name'];
            }
        }
    }
    
    // ASENHA speziell prüfen - nur wenn die Duplikationsfunktion aktiviert ist
    $asenha_options = get_option('admin_site_enhancements');
    if ($asenha_options && is_array($asenha_options) && 
        isset($asenha_options['content_duplication']) && 
        $asenha_options['content_duplication'] === true) {
        $conflicting_plugins[] = 'Admin and Site Enhancements (ASENHA) - Content Duplication';
    }
    
    if (!empty($conflicting_plugins)) {
        // Deaktiviere dieses Plugin
        deactivate_plugins(plugin_basename(__FILE__));
        
        // Erstelle Fehlermeldung
        $message = '<div class="error"><p><strong>' . __('WP Easy Post Duplicator konnte nicht aktiviert werden.', 'wp-easy-post-duplicator') . '</strong></p>';
        $message .= '<p>' . __('Es wurden folgende potenzielle Konflikte mit anderen Duplikations-Plugins erkannt:', 'wp-easy-post-duplicator') . '</p>';
        $message .= '<ul style="list-style-type: disc; margin-left: 20px;">';
        
        foreach (array_unique($conflicting_plugins) as $plugin_name) {
            $message .= '<li>' . esc_html($plugin_name) . '</li>';
        }
        
        $message .= '</ul>';
        $message .= '<p>' . __('Bitte deaktivieren Sie diese Plugins oder deren Duplizieren-Funktion zuerst, um Konflikte zu vermeiden.', 'wp-easy-post-duplicator') . '</p></div>';
        
        wp_die($message, 'Plugin-Aktivierungsfehler', array('back_link' => true));
    }
}

// Funktion zur Überprüfung, ob es sich um einen Crocoblock CPT handelt
function wpepd_is_crocoblock_cpt($post_type) {
    // Typische Präfixe für Crocoblock CPTs
    $crocoblock_prefixes = array('jet_', 'cct_', 'jet-', 'cct-');
    
    foreach ($crocoblock_prefixes as $prefix) {
        if (strpos($post_type, $prefix) === 0) {
            return true;
        }
    }
    
    // Überprüfe auch, ob JetEngine aktiviert ist und der Post-Typ ein JetEngine CPT ist
    if (class_exists('Jet_Engine') && function_exists('jet_engine')) {
        $jet_cpts = get_option('jet_engine_cpts', array());
        if (!empty($jet_cpts) && is_array($jet_cpts)) {
            foreach ($jet_cpts as $cpt) {
                if (isset($cpt['slug']) && $cpt['slug'] === $post_type) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

// Hook für Admin-Notices
add_action('admin_notices', 'wpepd_admin_notice');

// Hook für die Aktionslinks in der Post-Übersicht
add_filter('post_row_actions', 'wpepd_add_duplicate_link', 10, 2);
add_filter('page_row_actions', 'wpepd_add_duplicate_link', 10, 2);

// Admin-Notice für erfolgreiche Duplizierung
function wpepd_admin_notice() {
    if (isset($_GET['post_duplicated']) && $_GET['post_duplicated'] == 'true') {
        // Prüfe auf Crocoblock CPT
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        if ($post_id) {
            $post = get_post($post_id);
            if ($post && wpepd_is_crocoblock_cpt($post->post_type)) {
                // Spezielle Warnung für Crocoblock CPTs
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>' . __('Beitrag erfolgreich dupliziert.', 'wp-easy-post-duplicator') . '</strong></p>';
                echo '<p style="color: #d63638;"><strong>' . __('Wichtiger Hinweis:', 'wp-easy-post-duplicator') . '</strong> ' . __('Da dies ein Crocoblock Custom Post Type ist, bitte speichern Sie den Beitrag, bevor Sie zurück zur Listenansicht gehen, um Fehler zu vermeiden.', 'wp-easy-post-duplicator') . '</p>';
                echo '</div>';
                return;
            }
        }
        
        // Standard-Erfolgsmeldung
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Beitrag erfolgreich dupliziert.', 'wp-easy-post-duplicator') . '</p></div>';
    }
}

// Funktion zum Hinzufügen des Duplizieren-Links in der Post-Übersicht
function wpepd_add_duplicate_link($actions, $post) {
    // Frühzeitige Prüfung auf existierende Duplikations-Links
    if (isset($actions['duplicate']) || isset($actions['clone']) || isset($actions['copy']) || isset($actions['asenha-duplicate'])) {
        return $actions; // Keine Modifikation, wenn bereits ein Duplikations-Link vorhanden ist
    }
    
    // Prüfen, ob es sich um eine Kopie handelt
    $is_copy = false;
    if (strpos($post->post_title, '(Kopie)') !== false) {
        $is_copy = true;
    }
    
    // Prüfen, ob es ein Crocoblock CPT ist
    $is_crocoblock = wpepd_is_crocoblock_cpt($post->post_type);
    
    if (current_user_can('edit_posts')) {
        if ($is_copy) {
            // Ausgegraut und ohne Link für Kopien
            $actions['duplicate'] = '<span class="wpepd-disabled-action" style="opacity: 0.5; cursor: not-allowed;">' . __('Duplizieren', 'wp-easy-post-duplicator') . '</span>';
        } else {
            // Normaler Link für Originale
            $nonce = wp_create_nonce('wpepd_duplicate_post_nonce');
            $action_text = $is_crocoblock ? __('Duplizieren (bitte danach speichern)', 'wp-easy-post-duplicator') : __('Duplizieren', 'wp-easy-post-duplicator');
            $actions['duplicate'] = '<a href="' . admin_url('admin.php?action=wpepd_duplicate_post&post=' . $post->ID . '&nonce=' . $nonce) . '" class="wpepd-row-action' . ($is_crocoblock ? ' wpepd-crocoblock' : '') . '" title="' . ($is_crocoblock ? __('Diesen Beitrag duplizieren - Bitte speichern Sie die Kopie, bevor Sie zur Listenansicht zurückkehren', 'wp-easy-post-duplicator') : __('Diesen Beitrag duplizieren', 'wp-easy-post-duplicator')) . '" rel="permalink">' . $action_text . '</a>';
        }
    }
    return $actions;
}

// Hinzufügen der JS für den Button über dem Papierkorb (funktioniert für Classic und Block Editor)
add_action('admin_footer', 'wpepd_admin_footer_script');
function wpepd_admin_footer_script() {
    global $post, $pagenow;
    
    // Zuerst füge allgemeines Button-Ausgrauen für Zeilen-Aktionen hinzu
    if ($pagenow === 'edit.php') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Ausgrauen der "Duplizieren"-Links in der Beitragsübersicht nach Klick
            $('.wpepd-row-action').on('click', function(e) {
                var $link = $(this);
                // Button ausgrauen und Text ändern
                $link.css('opacity', '0.5').css('pointer-events', 'none').text(<?php echo json_encode(__('Wird dupliziert...', 'wp-easy-post-duplicator')); ?>);
            });
            
            // Spezielle Tooltip-Erweiterung für Crocoblock-Warnungen
            $('.wpepd-crocoblock').each(function() {
                $(this).tooltip({
                    items: ".wpepd-crocoblock",
                    content: <?php echo json_encode(__('Bitte speichern Sie die Kopie, bevor Sie zur Listenansicht gehen!', 'wp-easy-post-duplicator')); ?>,
                    position: {
                        my: "center bottom-20",
                        at: "center top",
                        collision: "flipfit"
                    },
                    classes: {
                        "ui-tooltip": "ui-corner-all ui-widget-shadow wpepd-tooltip"
                    }
                });
            });
        });
        </script>
        <style type="text/css">
        /* Stil für ausgegraute Buttons */
        .wpepd-disabled {
            opacity: 0.5 !important;
            cursor: default !important;
            pointer-events: none !important;
        }
        
        /* Stil für Crocoblock-Warnungen */
        .wpepd-crocoblock {
            color: #d63638 !important;
            font-weight: bold;
        }
        
        /* Tooltip-Stil */
        .wpepd-tooltip {
            padding: 10px;
            background-color: #f0f0f1;
            border: 1px solid #c3c4c7;
            color: #3c434a;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            max-width: 300px;
            font-size: 13px;
        }
        </style>
        <?php
    }
    
    // Nur auf Post/Page Edit-Seiten ausführen
    if (!in_array($pagenow, array('post.php', 'post-new.php')) || empty($post)) {
        return;
    }

    if (!current_user_can('edit_posts')) {
        return;
    }
    
    // Prüfen, ob es sich um eine Kopie handelt
    $is_copy = false;
    if (strpos($post->post_title, '(Kopie)') !== false) {
        $is_copy = true;
    }
    
    // Prüfen, ob es ein Crocoblock CPT ist
    $is_crocoblock = wpepd_is_crocoblock_cpt($post->post_type);
    
    // Doppelte Prüfung auf aktive ASENHA Duplikation im Frontend
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Prüfe nur auf ASENHA-Duplikationslinks, nicht auf ASENHA allgemein
        var hasAsenhaduplication = false;
        
        // Nur nach aktiven Duplikationslinks suchen
        if ($('a[href*="asenha_duplicate"]').length > 0 || 
            $('a[href*="asenha_clone"]').length > 0 || 
            $('.asenha-duplicate').length > 0) {
            console.log('ASENHA Duplikationsfunktion erkannt, deaktiviere WP Easy Post Duplicator');
            hasAsenhaduplication = true;
        }
        
        // Wenn ASENHA Duplikation erkannt wurde, verstecke unsere Elemente
        if (hasAsenhaduplication) {
            $('.wpepd-duplicate-button-gutenberg, .wpepd-duplicate-classic').hide();
            return;
        }
        
        <?php if ($is_crocoblock && isset($_GET['post_duplicated']) && $_GET['post_duplicated'] == 'true'): ?>
        // Spezielle Warnung für frisch duplizierte Crocoblock CPTs
        $('body').append(
            '<div id="wpepd-crocoblock-warning" style="position: fixed; bottom: 30px; right: 30px; background: #fff; border-left: 4px solid #d63638; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 12px; max-width: 350px; z-index: 9999;">' +
            '<h3 style="margin-top: 0; color: #d63638;">' + <?php echo json_encode(__('Wichtiger Hinweis', 'wp-easy-post-duplicator')); ?> + '</h3>' +
            '<p>' + <?php echo json_encode(__('Da dies ein <strong>Crocoblock Custom Post Type</strong> ist, speichern Sie bitte den Beitrag, bevor Sie zur Listenansicht zurückkehren, um Fehler zu vermeiden.', 'wp-easy-post-duplicator')); ?> + '</p>' +
            '<button class="button button-secondary" style="margin-top: 10px;" onclick="document.getElementById(\'wpepd-crocoblock-warning\').style.display=\'none\';">' + <?php echo json_encode(__('Verstanden', 'wp-easy-post-duplicator')); ?> + '</button>' +
            '</div>'
        );
        <?php endif; ?>
    });
    </script>
    <?php
    
    // Verschiedene Button-Versionen vorbereiten
    if ($is_copy) {
        // Ausgegraut und ohne Funktion für Kopien
        $button_html_classic = '<div class="duplicate-action" style="margin-bottom: 10px;"><span class="button button-secondary wpepd-disabled" style="display: block; text-align: center; opacity: 0.5; cursor: not-allowed;">' . __('Keine weitere Duplizierung', 'wp-easy-post-duplicator') . '</span></div>';
        $button_html_gutenberg = '<span class="components-button is-secondary wpepd-disabled" style="margin-bottom: 10px; display: block; text-align: center; opacity: 0.5; cursor: not-allowed;">' . __('Keine weitere Duplizierung', 'wp-easy-post-duplicator') . '</span>';
    } else {
        // Normaler funktionsfähiger Button für Originale
        $nonce = wp_create_nonce('wpepd_duplicate_post_nonce');
        $duplicate_url = admin_url('admin.php?action=wpepd_duplicate_post&post=' . $post->ID . '&nonce=' . $nonce);
        
        if ($is_crocoblock) {
            // Spezielle Version für Crocoblock CPTs
            $button_html_classic = '<div class="duplicate-action" style="margin-bottom: 10px;"><a href="' . esc_url($duplicate_url) . '" class="button button-secondary wpepd-duplicate-classic" style="display: block; text-align: center;">' . __('Beitrag duplizieren', 'wp-easy-post-duplicator') . '</a><span class="wpepd-warning" style="display: block; color: #d63638; margin-top: 5px; font-size: 11px; line-height: 1.2;">' . __('Bitte speichern Sie die Kopie, bevor Sie zur Listenansicht gehen!', 'wp-easy-post-duplicator') . '</span></div>';
            
            $button_html_gutenberg = '<div><a href="' . esc_url($duplicate_url) . '" class="components-button is-secondary wpepd-duplicate-button-gutenberg" style="margin-bottom: 5px; display: block; text-align: center;">' . __('Beitrag duplizieren', 'wp-easy-post-duplicator') . '</a><span class="wpepd-warning" style="display: block; color: #d63638; margin-bottom: 10px; font-size: 11px; line-height: 1.2;">' . __('Bitte speichern Sie die Kopie, bevor Sie zur Listenansicht gehen!', 'wp-easy-post-duplicator') . '</span></div>';
        } else {
            // Standard-Version für normale Posts
            $button_html_classic = '<div class="duplicate-action" style="margin-bottom: 10px;"><a href="' . esc_url($duplicate_url) . '" class="button button-secondary wpepd-duplicate-classic" style="display: block; text-align: center;">' . __('Beitrag duplizieren', 'wp-easy-post-duplicator') . '</a></div>';
            $button_html_gutenberg = '<a href="' . esc_url($duplicate_url) . '" class="components-button is-secondary wpepd-duplicate-button-gutenberg" style="margin-bottom: 10px; display: block; text-align: center;">' . __('Beitrag duplizieren', 'wp-easy-post-duplicator') . '</a>';
        }
    }
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Prüfen, ob bereits ein Duplikations-Button existiert (von anderen Plugins)
    function checkExistingDuplicateButtons() {
        // Liste möglicher Selektoren für Duplikationsbuttons von anderen Plugins
        var selectors = [
            '.duplicate-post-button', 
            '.duplicate_post',
            '.duplicate-action a',
            'a:contains("Duplicate")',
            'a:contains("Duplizieren")',
            'a:contains("Duplicar")',
            'a:contains("Dupliquer")',
            'a:contains("Copy")',
            'a:contains("Clone")',
            '.clone-post',
            '.duplicate-post',
            '.copy-post',
            // ASENHA spezifische Duplikationsselektoren
            'a.asenha-duplicate',
            'a[href*="asenha_duplicate"]',
            'a[href*="asenha_clone"]'
        ];
        
        // Suche nach Buttons mit diesen Klassen/Texten
        for (var i = 0; i < selectors.length; i++) {
            // Überspringe unsere eigenen Buttons
            var buttons = $(selectors[i]).not('.wpepd-duplicate-button-gutenberg, .wpepd-duplicate-classic');
            if (buttons.length > 0) {
                console.log('Bestehendes Duplikations-Plugin erkannt, verstecke unseren Button');
                return true;
            }
        }
        
        return false;
    }
    
    // Wenn kein anderer Duplikations-Button gefunden wurde, füge unseren hinzu
    if (!checkExistingDuplicateButtons()) {
        // Füge Button vor dem Papierkorb-Link hinzu (Classic Editor)
        if ($('#delete-action').length > 0) {
            $('#delete-action').before('<?php echo addslashes($button_html_classic); ?>');
            
            <?php if (!$is_copy): ?>
            // Füge Click-Handler für Ausgrauen hinzu (nur für aktive Buttons)
            $('.wpepd-duplicate-classic').on('click', function(e) {
                var $btn = $(this);
                $btn.addClass('wpepd-disabled').text(<?php echo json_encode(__('Wird dupliziert...', 'wp-easy-post-duplicator')); ?>);
            });
            <?php endif; ?>
        }

        // Für Gutenberg Editor (warte auf DOM-Änderungen)
        var checkExist = setInterval(function() {
            // Suche den Papierkorb-Button im Gutenberg Editor
            var trashButton = $('.editor-post-trash');
            if (trashButton.length == 0) {
                trashButton = $('.components-button.is-destructive'); // Alternative Selektor für neuere WP-Versionen
            }
            
            if (trashButton.length > 0 && $('.wpepd-duplicate-button-gutenberg, .wpepd-disabled').length == 0 && !checkExistingDuplicateButtons()) {
                // Button hinzufügen
                trashButton.before('<?php echo addslashes($button_html_gutenberg); ?>');
                
                <?php if (!$is_copy): ?>
                // Füge Click-Handler für Ausgrauen hinzu (nur für aktive Buttons)
                $('.wpepd-duplicate-button-gutenberg').on('click', function(e) {
                    var $btn = $(this);
                    $btn.addClass('wpepd-disabled').text(<?php echo json_encode(__('Wird dupliziert...', 'wp-easy-post-duplicator')); ?>);
                });
                <?php endif; ?>
                
                clearInterval(checkExist);
            }
        }, 500); // Prüfe alle 500ms
        
        // Stoppe die Überprüfung nach 10 Sekunden
        setTimeout(function() {
            clearInterval(checkExist);
        }, 10000);
    }
});
</script>
<?php
}

// Hauptfunktion zum Duplizieren eines Posts
function wpepd_duplicate_post_function($post_id) {
    // Sicherstellen, dass wir einen Post haben
    if (!$post_id) {
        wp_die(__('Kein Beitrag zum Duplizieren angegeben.', 'wp-easy-post-duplicator'));
    }

    // Original-Post-Daten abrufen
    $post = get_post($post_id);
    
    // Wenn Post nicht existiert
    if (!$post) {
        wp_die(__('Post-Erstellung fehlgeschlagen, der Originalbeitrag wurde nicht gefunden.', 'wp-easy-post-duplicator'));
    }
    
    // Sicherheitscheck: Verweigere das Duplizieren, wenn der Titel bereits "(Kopie)" enthält
    if (strpos($post->post_title, '(Kopie)') !== false) {
        wp_die(__('Dieser Beitrag ist bereits eine Kopie und kann nicht erneut dupliziert werden.', 'wp-easy-post-duplicator'));
    }
    
    // Aktuellen Benutzer prüfen
    $current_user = wp_get_current_user();
    
    // Neue Post-Daten vorbereiten
    $args = array(
        'post_author'    => $current_user->ID,
        'post_content'   => $post->post_content,
        'post_title'     => $post->post_title . ' (Kopie)',
        'post_excerpt'   => $post->post_excerpt,
        'post_status'    => 'draft',
        'post_type'      => $post->post_type,
        'comment_status' => $post->comment_status,
        'ping_status'    => $post->ping_status,
        'post_password'  => $post->post_password,
        'to_ping'        => $post->to_ping,
        'menu_order'     => $post->menu_order
    );
    
    // Neuen Post einfügen
    $new_post_id = wp_insert_post($args);
    
    // Bei Fehler abbrechen
    if (!$new_post_id) {
        wp_die(__('Post-Duplizierung fehlgeschlagen.', 'wp-easy-post-duplicator'));
    }
    
    // Taxonomien kopieren
    $taxonomies = get_object_taxonomies($post->post_type);
    foreach ($taxonomies as $taxonomy) {
        $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
        wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
    }
    
    // Custom Fields kopieren
    $post_meta = get_post_meta($post_id);
    if ($post_meta) {
        foreach ($post_meta as $meta_key => $meta_values) {
            if ($meta_key == '_wp_old_slug' || $meta_key == '_edit_lock') continue; // Diese Meta nicht kopieren
            foreach ($meta_values as $meta_value) {
                add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
            }
        }
    }
    
    // Featured Image kopieren, falls vorhanden
    if (has_post_thumbnail($post_id)) {
        $thumbnail_id = get_post_thumbnail_id($post_id);
        set_post_thumbnail($new_post_id, $thumbnail_id);
    }
    
    return $new_post_id;
}

// Action-Hook für unsere Duplikationsfunktion
add_action('admin_action_wpepd_duplicate_post', 'wpepd_duplicate_post_callback');

// Callback für den Duplikationslink
function wpepd_duplicate_post_callback() {
    // Sicherheits-Check mit korrektem Nonce-Namen
    if (!isset($_GET['post']) || !isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'wpepd_duplicate_post_nonce')) {
        wp_die(__('Sicherheits-Check fehlgeschlagen. Bitte versuchen Sie es erneut.', 'wp-easy-post-duplicator'));
    }
    
    // Berechtigungsprüfung
    if (!current_user_can('edit_posts')) {
        wp_die(__('Sie haben nicht die erforderlichen Berechtigungen', 'wp-easy-post-duplicator'));
    }
    
    $post_id = absint($_GET['post']);
    
    // Hole den Post und prüfe, ob es bereits eine Kopie ist
    $post = get_post($post_id);
    if ($post && strpos($post->post_title, '(Kopie)') !== false) {
        wp_die(__('Dieser Beitrag ist bereits eine Kopie und kann nicht erneut dupliziert werden.', 'wp-easy-post-duplicator'));
    }
    
    $new_post_id = wpepd_duplicate_post_function($post_id);
    
    // Prüfe, ob es sich um einen Crocoblock CPT handelt
    $is_crocoblock = $post && wpepd_is_crocoblock_cpt($post->post_type);
    
    // Weiterleitung zur Edit-Seite des neuen Posts
    if ($new_post_id && !is_wp_error($new_post_id)) {
        wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id . '&post_duplicated=true'));
        exit;
    } else {
        wp_die(__('Post-Duplizierung fehlgeschlagen', 'wp-easy-post-duplicator'));
    }
}