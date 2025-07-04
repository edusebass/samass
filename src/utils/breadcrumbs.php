<?php
/**
 * Breadcrumbs - Navegación tipo migas de pan
 * 
 * Utilidad para renderizar navegación breadcrumbs
 * en las páginas del sistema.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */


function render_breadcrumbs(array $trail = [], string $divider = '>') {
    echo '<nav style="--bs-breadcrumb-divider: \'' . $divider . '\';" aria-label="breadcrumb">';
    echo '<ol class="breadcrumb">';

    $last_index = count($trail) - 1;

    foreach ($trail as $index => $crumb) {
        $label = htmlspecialchars($crumb['label']);

        if (isset($crumb['url']) && $index !== $last_index) {
            $url = htmlspecialchars($crumb['url']);
            echo "<li class='breadcrumb-item'><a href='{$url}'>{$label}</a></li>";
        } else {
            echo "<li class='breadcrumb-item active' aria-current='page'>{$label}</li>";
        }
    }

    echo '</ol>';
    echo '</nav>';
}