function autocompletar_alt_vacio($content) {
    global $post;
    
    // Si no hay post o contenido, no hacemos nada
    if (!$post || empty($content)) {
        return $content;
    }

    // El texto que pondremos: "Título de la página - Yet! Water"
    $texto_alt = esc_attr($post->post_title) . ' - Yet! Water';

    // Buscamos todas las etiquetas de imagen en el código
    $patron = '/<img(.*?)src="(.*?)"(.*?)>/i';

    $content = preg_replace_callback($patron, function($matches) use ($texto_alt) {
        $img_tag = $matches[0];

        // Si la imagen ya tiene la etiqueta alt="" (aunque esté vacía)
        if (preg_match('/alt=([\'"])(.*?)\1/i', $img_tag, $alt_matches)) {
            $alt_actual = $alt_matches[2];
            
            // Si está literalmente vacía, la reemplazamos
            if (empty(trim($alt_actual))) {
                $img_tag = preg_replace('/alt=([\'"])(.*?)\1/i', 'alt="' . $texto_alt . '"', $img_tag);
            }
        } else {
            // Si ni siquiera tiene la palabra "alt=", se la agregamos al final
            $img_tag = str_replace('>', ' alt="' . $texto_alt . '">', $img_tag);
        }

        return $img_tag;
    }, $content);

    return $content;
}

// Aplicar este filtro a todo el contenido de la web y a las imágenes destacadas
add_filter('the_content', 'autocompletar_alt_vacio', 99);
add_filter('post_thumbnail_html', 'autocompletar_alt_vacio', 99);
