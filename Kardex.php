/**
 * ============================================================
 * Registro Stock Woo – Dashboard + Kardex (JetWater) (CONSOLIDADO + EDICIÓN + IVA REAL)
 * ============================================================
 *
 * - Menú admin: "Registro Stock Woo"
 * - Dashboard resumen (stock + umbrales + accesos rápidos)
 * - Kardex: Entradas / Salidas / Venta externa (ajusta stock del SKU base)
 * - Venta externa:
 *    - Precio unitario NETO (sin impuesto)
 *    - Selección de IMPUESTO REAL (lista de tasas configuradas en Woo, NO “Estándar” 0%)
 *    - Selección de cliente (usuarios)
 *    - Calcula: neto_total = unit_net * qty; impuesto = neto_total * tasa; total = neto_total + impuesto
 * - Fecha personalizada (datetime) para registros retroactivos
 * - Historial mejorado dentro del menú (tablas + pestañas)
 * - Timeline: pedidos Woo + ventas externas ordenadas por fecha
 * - Consolidado: stock + resumen ventas externas
 * - CPT interno "rsw_move" para historial
 * - Al borrar un registro (CPT), revierte el stock automáticamente
 * - EDICIÓN de registros desde “Historial (Vista)”:
 *    - Edita datos (tipo/motivo/cantidad/fecha/cliente/origen/para quién/nota)
 *    - Recalcula totales e impuesto si corresponde
 *    - Ajusta stock automáticamente según diferencia (revierte lo anterior y aplica lo nuevo)
 *
 * Requisitos:
 * - WooCommerce activo
 * - Producto base (bodega) con SKU: JET-BASE
 * - Producto base debe tener "Gestionar inventario" activado
 */

if ( ! defined('ABSPATH') ) exit;

// ============================================================
// Post Type (Historial)
// ============================================================
add_action('init', function () {
    register_post_type('rsw_move', [
        'labels' => [
            'name'          => 'Movimientos Stock',
            'singular_name' => 'Movimiento Stock',
        ],
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => false,
        'supports'      => ['title'],
        'capability_type' => 'post',
    ]);
});

// ============================================================
// Columnas del historial (CPT modo WP)
// ============================================================
add_filter('manage_edit-rsw_move_columns', function($cols){
    return [
        'cb'           => $cols['cb'],
        'title'        => 'Resumen',
        'rsw_date'     => 'Fecha',
        'rsw_dir'      => 'Tipo',
        'rsw_reason'   => 'Motivo',
        'rsw_qty'      => 'Cantidad',
        'rsw_customer' => 'Cliente (usuario)',
        'rsw_counter'  => 'Para quién',
        'rsw_origin'   => 'Origen',
        'rsw_sale'     => 'Venta (unit/neto/imp/total)',
    ];
});

add_action('manage_rsw_move_posts_custom_column', function($col, $post_id){
    $dir      = (string) get_post_meta($post_id,'direction',true);
    $reason   = (string) get_post_meta($post_id,'reason',true);
    $qty      = (int) get_post_meta($post_id,'qty',true);
    $counter  = (string) get_post_meta($post_id,'counterparty',true);
    $origin   = (string) get_post_meta($post_id,'origin',true);

    $dt = get_post_meta($post_id,'move_datetime',true);
    if ( ! $dt ) $dt = get_post($post_id)->post_date;

    $customer_id = (int) get_post_meta($post_id,'customer_id',true);
    $customer_txt = '—';
    if ( $customer_id > 0 ) {
        $u = get_user_by('id', $customer_id);
        if ( $u ) {
            $customer_txt = $u->display_name;
            if ( $u->user_email ) $customer_txt .= ' (' . $u->user_email . ')';
        }
    }

    $unit_net = (float) get_post_meta($post_id,'unit_price',true);
    $net      = (float) get_post_meta($post_id,'net_total',true);
    $tax      = (float) get_post_meta($post_id,'tax_amount',true);
    $gross    = (float) get_post_meta($post_id,'gross_total',true);
    $rate     = (float) get_post_meta($post_id,'tax_rate',true);

    if ($col === 'rsw_date') {
        echo esc_html( date_i18n('Y-m-d H:i', strtotime($dt)) );
    }
    if ($col === 'rsw_dir') {
        if ($dir==='in')   echo '<b style="color:#116329;">IN</b>';
        elseif ($dir==='out') echo '<b style="color:#8a1c1c;">OUT</b>';
        elseif ($dir==='sale') echo '<b style="color:#7a4b00;">SALE</b>';
        else echo '—';
    }
    if ($col === 'rsw_reason')  echo esc_html($reason ?: '—');
    if ($col === 'rsw_qty')     echo '<code>' . esc_html((string)$qty) . '</code>';
    if ($col === 'rsw_customer') echo esc_html($customer_txt);
    if ($col === 'rsw_counter') echo esc_html($counter ?: '—');
    if ($col === 'rsw_origin')  echo esc_html($origin ?: '—');

    if ($col === 'rsw_sale') {
        if ($dir !== 'sale') { echo '—'; return; }
        echo '<small>Unit neto: $' . esc_html(number_format($unit_net,0,',','.')) . '</small>';
        echo '<br>$' . esc_html(number_format($net,0,',','.'));
        echo '<br><small>Imp: $' . esc_html(number_format($tax,0,',','.')) . ' (' . esc_html($rate) . '%)</small>';
        echo '<br><small><b>Total: $' . esc_html(number_format($gross,0,',','.')) . '</b></small>';
    }
}, 10, 2);

// ============================================================
// Menú Admin
// ============================================================
add_action('admin_menu', function () {

    add_menu_page(
        'Registro Stock Woo',
        'Registro Stock Woo',
        'manage_woocommerce',
        'rsw-dashboard',
        'rsw_dashboard_page',
        'dashicons-clipboard',
        56
    );

    add_submenu_page('rsw-dashboard','Dashboard','Dashboard','manage_woocommerce','rsw-dashboard','rsw_dashboard_page');
    add_submenu_page('rsw-dashboard','Movimientos (Kardex)','Movimientos (Kardex)','manage_woocommerce','rsw-movimientos','rsw_moves_page');

    add_submenu_page('rsw-dashboard','Historial (Vista)','Historial (Vista)','manage_woocommerce','rsw-historial-vista','rsw_history_view_page');
    add_submenu_page('rsw-dashboard','Ventas externas','Ventas externas','manage_woocommerce','rsw-ventas-externas','rsw_external_sales_page');
    add_submenu_page('rsw-dashboard','Timeline (Woo + Ventas externas)','Timeline (Woo + Ventas externas)','manage_woocommerce','rsw-timeline','rsw_timeline_page');
    add_submenu_page('rsw-dashboard','Consolidado','Consolidado','manage_woocommerce','rsw-consolidado','rsw_consolidated_page');

    // NUEVO: edición dentro del menú (mejor UX)
    add_submenu_page('rsw-dashboard','Editar movimiento','Editar movimiento','manage_woocommerce','rsw-edit','rsw_edit_move_page');

    add_submenu_page('rsw-dashboard','Historial (modo WP)','Historial (modo WP)','manage_woocommerce','rsw-historial','rsw_history_redirect');
    add_submenu_page('rsw-dashboard','Pedidos WooCommerce','Pedidos WooCommerce','manage_woocommerce','rsw-pedidos','rsw_orders_redirect');
});

// ============================================================
// Redirects
// ============================================================
function rsw_history_redirect() {
    if ( ! current_user_can('manage_woocommerce') ) wp_die('Sin permisos.');
    wp_safe_redirect( admin_url('edit.php?post_type=rsw_move') );
    exit;
}
function rsw_orders_redirect() {
    if ( ! current_user_can('manage_woocommerce') ) wp_die('Sin permisos.');
    wp_safe_redirect( admin_url('edit.php?post_type=shop_order') );
    exit;
}

// ============================================================
// Helpers
// ============================================================
function rsw_get_base_product() {
    if ( ! function_exists('wc_get_product_id_by_sku') ) return [null, null];

    $base_sku = 'JET-BASE';
    $base_id  = wc_get_product_id_by_sku($base_sku);
    if ( ! $base_id ) return [null, null];

    $base_product = wc_get_product($base_id);
    if ( ! $base_product || ! $base_product->managing_stock() ) return [null, null];

    return [$base_product, $base_id];
}

function rsw_get_low_stock_threshold( $product_id ) {
    $per_product = get_post_meta( $product_id, '_low_stock_amount', true );
    if ( $per_product !== '' && $per_product !== null ) return (int) $per_product;
    $global = get_option( 'woocommerce_notify_low_stock_amount', 2 );
    return (int) $global;
}

function rsw_get_stock_products( $limit = 200 ) {
    if ( ! function_exists('wc_get_products') ) return [];

    $ids = wc_get_products([
        'status' => 'publish',
        'limit'  => $limit,
        'return' => 'ids',
        'stock_status' => ['instock','outofstock','onbackorder'],
    ]);

    $products = [];
    foreach ( $ids as $id ) {
        $p = wc_get_product($id);
        if ( $p && $p->managing_stock() ) $products[] = $p;
    }
    return $products;
}

/**
 * Clientes (usuarios) para select
 */
function rsw_get_customers_for_select( $limit = 500 ) {
    $users = get_users([
        'number'   => $limit,
        'orderby'  => 'display_name',
        'order'    => 'ASC',
        'role__in' => ['customer','subscriber','shop_manager','administrator','editor','author','contributor'],
    ]);

    $out = [];
    foreach ( $users as $u ) {
        $label = trim($u->display_name);
        if ( $u->user_email ) $label .= ' (' . $u->user_email . ')';
        $out[$u->ID] = $label;
    }
    return $out;
}

/**
 * ============================================================
 * IMPUESTOS: Lista REAL de tasas configuradas en WooCommerce
 * ============================================================
 * Motivo: “Estándar” en backend sin contexto de cliente suele dar 0%.
 * Aquí listamos y calculamos a partir de la tabla woocommerce_tax_rates.
 */
function rsw_get_tax_rates_for_select() {
    global $wpdb;

    $table = $wpdb->prefix . 'woocommerce_tax_rates';
    $rows  = $wpdb->get_results(
        "SELECT tax_rate_id, tax_rate, tax_rate_name, tax_rate_country, tax_rate_state, tax_rate_class
         FROM {$table}
         ORDER BY tax_rate_country, tax_rate_state, tax_rate_name, tax_rate_id",
        ARRAY_A
    );

    $out = [];
    $out[''] = '— Sin impuesto —';

    if ( ! $rows ) return $out;

    foreach ( $rows as $r ) {
        $id      = (int)$r['tax_rate_id'];
        $rate    = (float)$r['tax_rate'];
        $name    = $r['tax_rate_name'] ?: 'Impuesto';
        $cty     = $r['tax_rate_country'] ?: '—';
        $st      = $r['tax_rate_state'] ?: '';
        $class   = $r['tax_rate_class'] ?: 'standard';
        $label   = $name . " ({$rate}%) • {$cty}" . ($st ? "-{$st}" : '') . " • {$class}";
        $out[(string)$id] = $label;
    }
    return $out;
}

function rsw_get_tax_rate_percent_by_id( $tax_rate_id ) {
    global $wpdb;
    $tax_rate_id = (int)$tax_rate_id;
    if ( $tax_rate_id <= 0 ) return 0.0;

    $table = $wpdb->prefix . 'woocommerce_tax_rates';
    $rate  = $wpdb->get_var( $wpdb->prepare("SELECT tax_rate FROM {$table} WHERE tax_rate_id = %d", $tax_rate_id) );
    if ( $rate === null ) return 0.0;
    return (float)$rate;
}

/**
 * Desde NETO (sin impuesto) calcula impuesto y total final.
 * Retorna: [tax_amount, rate_percent, gross_total]
 */
function rsw_calc_tax_from_net_by_rate_id( $tax_rate_id, $net_amount ) {
    $rate_percent = rsw_get_tax_rate_percent_by_id( $tax_rate_id );
    if ( $rate_percent <= 0 ) return [ 0.0, 0.0, (float)$net_amount ];

    $tax_amount  = (float)$net_amount * ($rate_percent / 100.0);
    $gross_total = (float)$net_amount + (float)$tax_amount;

    $tax_amount  = round($tax_amount, 2);
    $gross_total = round($gross_total, 2);

    return [ $tax_amount, (float)$rate_percent, $gross_total ];
}

/**
 * Tabs en Historial (Vista)
 */
function rsw_admin_tabs( $active ) {
    $tabs = [
        'rsw-historial-vista' => 'Todos (Kardex)',
        'rsw-ventas-externas' => 'Ventas externas',
        'rsw-timeline'        => 'Timeline',
        'rsw-consolidado'     => 'Consolidado',
    ];

    echo '<h2 class="nav-tab-wrapper" style="margin-top:14px;">';
    foreach ( $tabs as $slug => $label ) {
        $url = admin_url('admin.php?page=' . $slug);
        $class = ($slug === $active) ? 'nav-tab nav-tab-active' : 'nav-tab';
        echo '<a class="' . esc_attr($class) . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }
    echo '</h2>';
}

/**
 * Convierte datetime-local (YYYY-MM-DDTHH:MM) a mysql datetime
 */
function rsw_parse_datetime_local_to_mysql( $raw ) {
    $raw = (string)$raw;
    if ( ! $raw ) return '';
    $raw = str_replace('T',' ', $raw);
    $ts  = strtotime($raw);
    if ( ! $ts ) return '';
    return date('Y-m-d H:i:s', $ts);
}

/**
 * Badge del tipo
 */
function rsw_type_badge( $direction ) {
    $direction = (string)$direction;
    if ($direction==='in')   return '<b style="color:#116329;">IN</b>';
    if ($direction==='out')  return '<b style="color:#8a1c1c;">OUT</b>';
    if ($direction==='sale') return '<b style="color:#7a4b00;">SALE</b>';
    return '—';
}

/**
 * Aplica stock según dirección y cantidad
 * - in: increase
 * - out/sale: decrease
 */
function rsw_apply_stock_by_direction( $base_product, $direction, $qty ) {
    $direction = (string)$direction;
    $qty = (int)$qty;
    if ( $qty <= 0 ) return;
    if ( $direction === 'in' ) {
        wc_update_product_stock($base_product, $qty, 'increase');
    } elseif ( $direction === 'out' || $direction === 'sale' ) {
        wc_update_product_stock($base_product, $qty, 'decrease');
    }
}

/**
 * Revierte stock según dirección y cantidad (inverso)
 */
function rsw_revert_stock_by_direction( $base_product, $direction, $qty ) {
    $direction = (string)$direction;
    $qty = (int)$qty;
    if ( $qty <= 0 ) return;
    if ( $direction === 'in' ) {
        wc_update_product_stock($base_product, $qty, 'decrease');
    } elseif ( $direction === 'out' || $direction === 'sale' ) {
        wc_update_product_stock($base_product, $qty, 'increase');
    }
}

// ============================================================
// Dashboard
// ============================================================
function rsw_dashboard_page() {
    if ( ! current_user_can('manage_woocommerce') ) wp_die('Sin permisos.');

    [$base_product, $base_id] = rsw_get_base_product();
    $base_stock = $base_product ? (int) $base_product->get_stock_quantity() : null;

    $products = rsw_get_stock_products(200);

    echo '<div class="wrap">';
    echo '<h1>Registro Stock Woo – Dashboard</h1>';

    echo '<div style="display:flex; gap:12px; flex-wrap:wrap; margin: 12px 0;">';

    echo '<div style="background:#fff; border:1px solid #ddd; border-radius:10px; padding:14px; min-width:260px;">';
    echo '<div style="font-size:12px; opacity:.7;">Producto base (bodega)</div>';
    if ( $base_product ) {
        echo '<div style="font-size:18px; font-weight:700; margin-top:6px;">SKU: JET-BASE</div>';
        echo '<div style="font-size:28px; font-weight:800; margin-top:4px;">' . esc_html((string)$base_stock) . '</div>';
        echo '<div style="font-size:12px; opacity:.7;">Stock actual</div>';
        echo '<p class="description" style="margin-top:10px;">Este producto puede estar oculto del catálogo, igual funciona.</p>';
    } else {
        echo '<div style="color:#b00020; font-weight:700;">Falta configurar producto base</div>';
        echo '<div style="font-size:12px; opacity:.8;">Crea/edita el producto base y pon SKU = <code>JET-BASE</code> + “Gestionar inventario”.</div>';
    }
    echo '</div>';

    echo '<div style="background:#fff; border:1px solid #ddd; border-radius:10px; padding:14px; min-width:340px; flex:1;">';
    echo '<div style="font-size:12px; opacity:.7;">Accesos rápidos</div>';
    echo '<div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">';
    echo '<a class="button button-primary" href="' . esc_url( admin_url('admin.php?page=rsw-movimientos') ) . '">Movimientos (Kardex)</a>';
    echo '<a class="button" href="' . esc_url( admin_url('admin.php?page=rsw-historial-vista') ) . '">Historial (Vista)</a>';
    echo '<a class="button" href="' . esc_url( admin_url('edit.php?post_type=rsw_move') ) . '">Historial (modo WP)</a>';
    echo '<a class="button" href="' . esc_url( admin_url('edit.php?post_type=shop_order') ) . '">Pedidos WooCommerce</a>';
    echo '</div>';
    echo '<p class="description" style="margin-top:10px;">Registra mermas, entradas, devoluciones y ventas externas desde “Movimientos”.</p>';
    echo '</div>';

    echo '</div>';

    echo '<h2>Resumen de stock y umbrales</h2>';
    echo '<div style="background:#fff; border:1px solid #ddd; border-radius:10px; padding:12px;">';
    echo '<table class="widefat striped" style="width:100%;">';
    echo '<thead><tr>
            <th>Producto</th>
            <th>SKU</th>
            <th style="width:120px;">Stock</th>
            <th style="width:140px;">Umbral mínimo</th>
            <th style="width:140px;">Estado</th>
          </tr></thead><tbody>';

    foreach ( $products as $p ) {
        $id    = $p->get_id();
        $name  = $p->get_name();
        $sku   = $p->get_sku();
        $stock = (int) $p->get_stock_quantity();
        $thr   = rsw_get_low_stock_threshold($id);

        $state = 'OK';
        $badge = 'background:#e7f7ea; color:#116329;';
        if ( $stock <= 0 ) {
            $state = 'SIN STOCK';
            $badge = 'background:#fde7e7; color:#8a1c1c;';
        } elseif ( $stock <= $thr ) {
            $state = 'BAJO';
            $badge = 'background:#fff4d6; color:#7a4b00;';
        }

        $edit_link = get_edit_post_link($id);
        echo '<tr>';
        echo '<td><a href="' . esc_url($edit_link) . '"><b>' . esc_html($name) . '</b></a></td>';
        echo '<td>' . esc_html($sku ?: '-') . '</td>';
        echo '<td><code>' . esc_html((string)$stock) . '</code></td>';
        echo '<td><code>' . esc_html((string)$thr) . '</code></td>';
        echo '<td><span style="padding:4px 10px; border-radius:999px; ' . esc_attr($badge) . ' font-weight:700;">' . esc_html($state) . '</span></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '<p class="description" style="margin-top:10px;">Umbral: producto (Inventario → Umbral pocas existencias). Si vacío, usa el global WooCommerce.</p>';
    echo '</div>';

    echo '</div>';
}

// ============================================================
// Kardex / Movimientos (crear)
// ============================================================
function rsw_moves_page() {
    if ( ! current_user_can('manage_woocommerce') ) wp_die('Sin permisos.');

    $reasons_by_type = [
        'out'  => ['merma','evento','reunion','auspicio','marketing','ajuste'],
        'in'   => ['produccion','devolucion','ajuste'],
        'sale' => ['venta_externa'],
    ];

    $reason_labels = [
        'merma'         => 'Merma',
        'evento'        => 'Evento',
        'reunion'       => 'Reunión',
        'auspicio'      => 'Auspicio',
        'marketing'     => 'Marketing General',
        'produccion'    => 'Producción (Entrada)',
        'devolucion'    => 'Devolución externa (Entrada)',
        'venta_externa' => 'Venta por fuera',
        'ajuste'        => 'Ajuste inventario',
    ];

    $tax_rates = rsw_get_tax_rates_for_select();
    $customers = rsw_get_customers_for_select(500);

    [$base_product, $base_id] = rsw_get_base_product();
    $base_stock = $base_product ? (int) $base_product->get_stock_quantity() : null;

    // Procesar creación
    if ( isset($_POST['rsw_move_submit']) ) {
        check_admin_referer('rsw_move_action', 'rsw_move_nonce');

        [$bp, $bid] = rsw_get_base_product();
        if ( ! $bp ) {
            echo '<div class="notice notice-error"><p><b>Error:</b> No se encontró producto base con SKU JET-BASE o no gestiona inventario.</p></div>';
        } else {

            $direction = sanitize_text_field($_POST['direction'] ?? '');
            $reason    = sanitize_text_field($_POST['reason'] ?? '');
            $qty       = (int) ($_POST['qty'] ?? 0);

            $counterparty = sanitize_text_field($_POST['counterparty'] ?? '');
            $origin       = sanitize_text_field($_POST['origin'] ?? '');
            $note         = sanitize_textarea_field($_POST['note'] ?? '');

            // Fecha personalizada
            $move_datetime = rsw_parse_datetime_local_to_mysql( sanitize_text_field($_POST['move_datetime'] ?? '') );
            if ( ! $move_datetime ) $move_datetime = current_time('mysql');

            // Venta externa
            $unit_price_net = isset($_POST['unit_price']) ? (float) $_POST['unit_price'] : 0;
            $customer_id    = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;

            // NUEVO: tasa real por ID (no “standard”)
            $tax_rate_id    = isset($_POST['tax_rate_id']) ? (int) $_POST['tax_rate_id'] : 0;

            $errors = [];

            if ( ! in_array($direction, ['in','out','sale'], true) ) $errors[] = 'Tipo inválido.';
            if ( empty($reasons_by_type[$direction]) || ! in_array($reason, $reasons_by_type[$direction], true) ) {
                $errors[] = 'Motivo inválido para el tipo seleccionado.';
            }
            if ( $qty <= 0 ) $errors[] = 'La cantidad debe ser mayor a 0.';

            if ( $direction === 'sale' ) {
                if ( $unit_price_net < 0 ) $errors[] = 'El precio unitario no puede ser negativo.';
            } else {
                // suprime siempre
                $unit_price_net = 0;
                $customer_id    = 0;
                $tax_rate_id    = 0;
            }

            // Stock disponible
            $current_stock = (int) $bp->get_stock_quantity();
            if ( in_array($direction, ['out','sale'], true) && $qty > $current_stock ) {
                $errors[] = 'Stock insuficiente en bodega. Stock actual: ' . $current_stock;
            }

            if ( $errors ) {
                echo '<div class="notice notice-error"><p><b>Revisa:</b><br>' . implode('<br>', array_map('esc_html', $errors)) . '</p></div>';
            } else {

                // Ajuste de stock
                rsw_apply_stock_by_direction( $bp, $direction, $qty );

                // Totales venta externa (neto → impuesto → total)
                $net_total   = 0;
                $tax_amount  = 0;
                $tax_rate    = 0;
                $gross_total = 0;

                if ( $direction === 'sale' ) {
                    $net_total = (float)$unit_price_net * (float)$qty;
                    [ $tax_amount, $tax_rate, $gross_total ] = rsw_calc_tax_from_net_by_rate_id( $tax_rate_id, $net_total );
                }

                // Guardar historial con fecha retroactiva como fecha del post
                $title = strtoupper($direction) . ' • ' . $reason . ' • ' . $qty . ' u';
                $post_date     = $move_datetime;
                $post_date_gmt = get_gmt_from_date($post_date);

                $post_id = wp_insert_post([
                    'post_type'      => 'rsw_move',
                    'post_status'    => 'publish',
                    'post_title'     => $title,
                    'post_date'      => $post_date,
                    'post_date_gmt'  => $post_date_gmt,
                ]);

                if ( $post_id ) {
                    update_post_meta($post_id, 'move_datetime', $move_datetime);
                    update_post_meta($post_id, 'direction', $direction);
                    update_post_meta($post_id, 'reason', $reason);
                    update_post_meta($post_id, 'qty', $qty);

                    update_post_meta($post_id, 'unit_price', $unit_price_net);

                    update_post_meta($post_id, 'customer_id', $customer_id);
                    update_post_meta($post_id, 'tax_rate_id', $tax_rate_id);
                    update_post_meta($post_id, 'tax_rate', $tax_rate);
                    update_post_meta($post_id, 'tax_amount', $tax_amount);
                    update_post_meta($post_id, 'net_total', $net_total);
                    update_post_meta($post_id, 'gross_total', $gross_total);

                    update_post_meta($post_id, 'counterparty', $counterparty);
                    update_post_meta($post_id, 'origin', $origin);
                    update_post_meta($post_id, 'note', $note);
                    update_post_meta($post_id, 'base_product_id', $bid);
                    update_post_meta($post_id, 'base_sku', 'JET-BASE');
                    update_post_meta($post_id, 'stock_before', $current_stock);
                    update_post_meta($post_id, 'stock_after', (int) wc_get_product($bid)->get_stock_quantity());
                    update_post_meta($post_id, 'created_by', get_current_user_id());
                    update_post_meta($post_id, '_rsw_reverted', 0);
                }

                echo '<div class="notice notice-success"><p><b>OK:</b> Movimiento registrado y stock actualizado.</p></div>';
                $base_stock = (int) wc_get_product($bid)->get_stock_quantity();
            }
        }
    }

    $reasons_json = wp_json_encode($reasons_by_type);
    $labels_json  = wp_json_encode($reason_labels);

    echo '<div class="wrap">';
    echo '<h1>Registro Stock Woo – Movimientos (Kardex)</h1>';

    echo '<p style="margin: 8px 0 14px 0;">';
    echo '<a class="button" href="' . esc_url( admin_url('admin.php?page=rsw-dashboard') ) . '">← Volver al Dashboard</a> ';
    echo '<a class="button" href="' . esc_url( admin_url('admin.php?page=rsw-historial-vista') ) . '">Historial (Vista)</a> ';
    echo '<a class="button" href="' . esc_url( admin_url('edit.php?post_type=rsw_move') ) . '">Historial (modo WP)</a> ';
    echo '<a class="button" href="' . esc_url( admin_url('edit.php?post_type=shop_order') ) . '">Pedidos WooCommerce</a>';
    echo '</p>';

    if ( ! $base_product ) {
        echo '<div class="notice notice-error"><p><b>Falta configurar:</b> Producto base con SKU <code>JET-BASE</code> y gestionar inventario activado.</p></div>';
    } else {
        echo '<p><b>Stock bodega actual (JET-BASE):</b> <code>' . esc_html((string)$base_stock) . '</code></p>';
    }

    echo '<form method="post" id="rsw-move-form" style="max-width: 980px; background:#fff; padding:16px; border:1px solid #ddd; border-radius:10px;">';
    wp_nonce_field('rsw_move_action', 'rsw_move_nonce');

    echo '<h2 style="margin-top:0;">Crear movimiento</h2>';

    echo '<table class="form-table"><tbody>';

    echo '<tr><th><label>Fecha del movimiento</label></th><td>';
    echo '<input type="datetime-local" name="move_datetime" style="width:240px;">';
    echo '<p class="description">Opcional. Si lo dejas vacío, usa la fecha/hora actual.</p>';
    echo '</td></tr>';

    echo '<tr><th><label>Tipo</label></th><td>';
    echo '<select id="rsw-direction" name="direction" required>
            <option value="out">Salida (Merma/Evento/Marketing/etc.)</option>
            <option value="in">Entrada (Producción/Devolución externa)</option>
            <option value="sale">Venta externa</option>
          </select>';
    echo '</td></tr>';

    echo '<tr><th><label>Motivo</label></th><td>';
    echo '<select id="rsw-reason" name="reason" required></select>';
    echo '</td></tr>';

    echo '<tr><th><label>Cantidad (unidades)</label></th><td>';
    echo '<input type="number" name="qty" min="1" step="1" required style="width:140px;">';
    echo '</td></tr>';

    echo '<tr id="rsw-unit-price-row"><th><label>Precio unitario NETO (sin impuesto) (solo Venta externa)</label></th><td>';
    echo '<input id="rsw-unit-price" type="number" name="unit_price" min="0" step="0.01" placeholder="Ej: 1200 (neto)" style="width:200px;">';
    echo '<p class="description">Se multiplica por cantidad y luego se suma el impuesto seleccionado (encima del neto).</p>';
    echo '</td></tr>';

    echo '<tr id="rsw-tax-row"><th><label>Impuesto (solo Venta externa)</label></th><td>';
    echo '<select id="rsw-tax-rate" name="tax_rate_id" style="width:100%;">';
    foreach ( $tax_rates as $id => $label ) {
        echo '<option value="' . esc_attr($id) . '">' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<p class="description"><b>Ojo:</b> Esta lista viene directo de las tasas configuradas en Woo (tabla de impuestos). No depende del checkout.</p>';
    echo '</td></tr>';

    echo '<tr id="rsw-customer-row"><th><label>Cliente (usuario) (solo Venta externa)</label></th><td>';
    echo '<select id="rsw-customer" name="customer_id" style="width:100%;">';
    echo '<option value="">-- Selecciona cliente (opcional) --</option>';
    foreach ( $customers as $id => $label ) {
        echo '<option value="' . esc_attr($id) . '">' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '</td></tr>';

    echo '<tr><th><label>Para quién / Responsable</label></th><td>';
    echo '<input type="text" name="counterparty" placeholder="Ej: Juan Pérez / Empresa X / Equipo marketing" style="width:100%;">';
    echo '</td></tr>';

    echo '<tr><th><label>Origen / Dónde / Fuente</label></th><td>';
    echo '<input type="text" name="origin" placeholder="Ej: Evento / Producción lote #12 / Devolución fuera tienda" style="width:100%;">';
    echo '</td></tr>';

    echo '<tr><th><label>Nota</label></th><td>';
    echo '<textarea name="note" rows="3" style="width:100%;" placeholder="Detalle adicional (opcional)"></textarea>';
    echo '</td></tr>';

    echo '</tbody></table>';

    echo '<p><button class="button button-primary" name="rsw_move_submit" value="1">Registrar movimiento</button></p>';
    echo '<p class="description">Ajusta stock del producto base SKU <code>JET-BASE</code> y guarda historial.</p>';

    echo '</form>';

    // JS: motivos por tipo + ocultar campos de venta externa cuando no aplique
    echo "<script>
    (function(){
        const reasonsByType = {$reasons_json};
        const labels = {$labels_json};

        const directionEl  = document.getElementById('rsw-direction');
        const reasonEl     = document.getElementById('rsw-reason');

        const unitPriceEl  = document.getElementById('rsw-unit-price');
        const unitPriceRow = document.getElementById('rsw-unit-price-row');

        const customerRow  = document.getElementById('rsw-customer-row');
        const customerEl   = document.getElementById('rsw-customer');

        const taxRow       = document.getElementById('rsw-tax-row');
        const taxEl        = document.getElementById('rsw-tax-rate');

        function fillReasons(type) {
            const allowed = reasonsByType[type] || [];
            reasonEl.innerHTML = '';
            allowed.forEach(function(key){
                const opt = document.createElement('option');
                opt.value = key;
                opt.textContent = labels[key] || key;
                reasonEl.appendChild(opt);
            });
        }

        function toggleSaleOnly(type){
            const isSale = type === 'sale';

            unitPriceRow.style.display = isSale ? '' : 'none';
            unitPriceEl.disabled = !isSale;
            if(!isSale) unitPriceEl.value = '';

            customerRow.style.display = isSale ? '' : 'none';
            customerEl.disabled = !isSale;
            if(!isSale) customerEl.value = '';

            taxRow.style.display = isSale ? '' : 'none';
            taxEl.disabled = !isSale;
            if(!isSale) taxEl.value = '';
        }

        directionEl.addEventListener('change', function(){
            fillReasons(directionEl.value);
            toggleSaleOnly(directionEl.value);
        });

        fillReasons(directionEl.value);
        toggleSaleOnly(directionEl.value);
    })();
    </script>";

    echo '</div>';
}

// ============================================================
// Tabla reusable (vista interna)
// ============================================================
function rsw_render_moves_table( $args = [] ) {
    $defaults = [
        'direction' => '',
        'limit'     => 200,
    ];
    $args = wp_parse_args($args, $defaults);

    $mq = [];
    if ( $args['direction'] ) {
        $mq[] = [
            'key'   => 'direction',
            'value' => $args['direction'],
        ];
    }

    $q = new WP_Query([
        'post_type'      => 'rsw_move',
        'post_status'    => 'publish',
        'posts_per_page' => (int)$args['limit'],
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => $mq,
    ]);

    echo '<table class="widefat striped" style="width:100%;">';
    echo '<thead><tr>
        <th style="width:140px;">Fecha</th>
        <th style="width:70px;">Tipo</th>
        <th style="width:160px;">Motivo</th>
        <th style="width:90px;">Cantidad</th>
        <th style="width:220px;">Cliente (usuario)</th>
        <th>Para quién</th>
        <th>Origen</th>
        <th style="width:220px;">Venta (unit/neto/imp/total)</th>
        <th style="width:170px;">Acciones</th>
    </tr></thead><tbody>';

    if ( ! $q->have_posts() ) {
        echo '<tr><td colspan="9">Sin registros.</td></tr>';
    } else {
        while ( $q->have_posts() ) { $q->the_post();
            $id = get_the_ID();

            $move_dt = get_post_meta($id,'move_datetime',true);
            if ( ! $move_dt ) $move_dt = get_post($id)->post_date;

            $direction = (string)get_post_meta($id,'direction',true);
            $reason    = (string)get_post_meta($id,'reason',true);
            $qty       = (int)get_post_meta($id,'qty',true);

            $customer_id = (int) get_post_meta($id,'customer_id',true);
            $customer_txt = '—';
            if ( $customer_id > 0 ) {
                $u = get_user_by('id', $customer_id);
                if ( $u ) {
                    $customer_txt = $u->display_name;
                    if ( $u->user_email ) $customer_txt .= ' (' . $u->user_email . ')';
                }
            }

            $counter   = (string)get_post_meta($id,'counterparty',true);
            $origin    = (string)get_post_meta($id,'origin',true);

            $unit_net  = (float)get_post_meta($id,'unit_price',true);
            $net_total = (float)get_post_meta($id,'net_total',true);
            $tax_amt   = (float)get_post_meta($id,'tax_amount',true);
            $gross     = (float)get_post_meta($id,'gross_total',true);
            $tax_rate  = (float)get_post_meta($id,'tax_rate',true);

            $type_badge = rsw_type_badge($direction);

            $sale_box = '—';
            if ($direction==='sale') {
                $sale_box = '<small>Unit neto: $' . number_format($unit_net,0,',','.') . '</small>'
                    . '<br>$' . number_format($net_total,0,',','.')
                    . '<br><small>Imp: $' . number_format($tax_amt,0,',','.') . ' (' . $tax_rate . '%)</small>'
                    . '<br><small><b>Total: $' . number_format($gross,0,',','.') . '</b></small>';
            }

            $edit_wp   = get_edit_post_link($id);
            $edit_in   = admin_url('admin.php?page=rsw-edit&move_id=' . $id);

            echo '<tr>';
            echo '<td>' . esc_html(date_i18n('Y-m-d H:i', strtotime($move_dt))) . '</td>';
            echo '<td>' . $type_badge . '</td>';
            echo '<td>' . esc_html($reason ?: '—') . '</td>';
            echo '<td><code>' . esc_html((string)$qty) . '</code></td>';
            echo '<td>' . esc_html($customer_txt) . '</td>';
            echo '<td>' . esc_html($counter ?: '—') . '</td>';
            echo '<td>' . esc_html($origin ?: '—') . '</td>';
            echo '<td>' . $sale_box . '</td>';
            echo '<td style="white-space:nowrap;">'
                . '<a class="button button-primary" href="' . esc_url($edit_in) . '">Editar</a> '
                . '<a class="button" href="' . esc_url($edit_wp) . '">WP</a>'
                . '</td>';
            echo '</tr>';
        }
        wp_reset_postdata();
    }

    echo '</tbody></table>';
}

// ============================================================
// Páginas de historial (Vista interna)
// ============================================================
function rsw_history_view_page() {
    if ( ! current_user_can('manage_woocommerce') ) wp_die('Sin permisos.');
    echo '<div class="wrap">';
    echo '<h1>Registro Stock Woo – Historial</h1>';
    rsw_admin_tabs('rsw-historial-vista');
    echo '<p style="margin-top:10px;">';
    echo '<a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=rsw-movimientos')) . '">+ Crear movimiento</a> ';
    echo '<a class="button" href="' . esc_url(admin_url('edit.php?post_type=rsw_move')) . '">Editar/Borrar (modo WP)</a> ';
    echo '<a class="button" href="' . esc_url(admin_url('edit.php?post_type=shop_order')) . '">Pedidos Woo</a>';
    echo '</p>';
    rsw_render_moves_table(['direction' => '', 'limit' => 200]);
    echo '</div>';
}

function rsw_external_sales_page() {
    if ( ! current_user_can('manage_woocommerce') ) wp_die('Sin permisos.');
    echo '<div class="wrap">';
    echo '<h1>Registro Stock Woo – Ventas externas</h1>';
    rsw_admin_tabs('rsw-ventas-externas');
    rsw_render_moves_table(['direction' => 'sale', 'limit' => 200]);
    echo '</div>';
}

// ============================================================
// Timeline (Woo + Ventas externas)
// ============================================================
function rsw_timeline_page() {
    if ( ! current_user_can('manage_woocommerce') ) wp_die('Sin permisos.');

    $orders = get_posts([
        'post_type'      => 'shop_order',
        'post_status'    => array_keys(wc_get_order_statuses()),
        'posts_per_page' => 200,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $moves = get_posts([
        'post_type'      => 'rsw_move',
        'post_status'    => 'publish',
        'posts_per_page' => 200,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_key'       => 'direction',
        'meta_value'     => 'sale',
    ]);

    $items = [];

    foreach ($orders as $o) {
        $items[] = [
            'type' => 'woo',
            'date' => $o->post_date,
            'id'   => $o->ID,
            'label'=> 'Pedido Woo #' . $o->ID,
        ];
    }

    foreach ($moves as $m) {
        $dt = get_post_meta($m->ID,'move_datetime',true);
        if (!$dt) $dt = $m->post_date;

        $gross = (float)get_post_meta($m->ID,'gross_total',true);
        $items[] = [
            'type' => 'ext',
            'date' => $dt,
            'id'   => $m->ID,
            'label'=> 'Venta externa • Total $' . number_format($gross,0,',','.'),
        ];
    }

    usort($items, function($a,$b){
        return strtotime($b['date']) <=> strtotime($a['date']);
    });

    echo '<div class="wrap">';
    echo '<h1>Registro Stock Woo – Timeline</h1>';
    rsw_admin_tabs('rsw-timeline');

    echo '<table class="widefat striped" style="width:100%;">';
    echo '<thead><tr><th style="width:160px;">Fecha</th><th style="width:140px;">Tipo</th><th>Detalle</th><th style="width:120px;">Acción</th></tr></thead><tbody>';

    if (empty($items)) {
        echo '<tr><td colspan="4">Sin datos.</td></tr>';
    } else {
        foreach ($items as $it) {
            $date = date_i18n('Y-m-d H:i', strtotime($it['date']));
            $type = ($it['type']==='woo') ? 'WooCommerce' : 'Venta externa';
            $link = ($it['type']==='woo')
                ? admin_url('post.php?post=' . $it['id'] . '&action=edit')
                : admin_url('admin.php?page=rsw-edit&move_id=' . $it['id']);

            echo '<tr>';
            echo '<td>' . esc_html($date) . '</td>';
            echo '<td>' . esc_html($type) . '</td>';
            echo '<td>' . esc_html($it['label']) . '</td>';
            echo '<td><a class="button" href="' . esc_url($link) . '">Ver/Editar</a></td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';
    echo '</div>';
}

// ============================================================
// Consolidado
// ============================================================
function rsw_consolidated_page() {
    if ( ! current_user_can('manage_woocommerce') ) wp_die('Sin permisos.');

    $sales = get_posts([
        'post_type'      => 'rsw_move',
        'post_status'    => 'publish',
        'posts_per_page' => 200,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_key'       => 'direction',
        'meta_value'     => 'sale',
    ]);

    $sum_net = 0; $sum_tax = 0; $sum_gross = 0; $count = 0;
    foreach ($sales as $s) {
        $sum_net   += (float)get_post_meta($s->ID,'net_total',true);
        $sum_tax   += (float)get_post_meta($s->ID,'tax_amount',true);
        $sum_gross += (float)get_post_meta($s->ID,'gross_total',true);
        $count++;
    }

    [$base_product, $base_id] = rsw_get_base_product();
    $base_stock = $base_product ? (int)$base_product->get_stock_quantity() : null;

    echo '<div class="wrap">';
    echo '<h1>Registro Stock Woo – Consolidado</h1>';
    rsw_admin_tabs('rsw-consolidado');

    echo '<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:12px;">';

    echo '<div style="background:#fff;border:1px solid #ddd;border-radius:10px;padding:14px;min-width:260px;">';
    echo '<div style="opacity:.7;font-size:12px;">Stock bodega (JET-BASE)</div>';
    echo '<div style="font-size:28px;font-weight:800;">' . esc_html((string)$base_stock) . '</div>';
    echo '</div>';

    echo '<div style="background:#fff;border:1px solid #ddd;border-radius:10px;padding:14px;min-width:320px;">';
    echo '<div style="opacity:.7;font-size:12px;">Ventas externas (últimos 200)</div>';
    echo '<div style="margin-top:6px;">Neto: <b>$' . esc_html(number_format($sum_net,0,',','.')) . '</b></div>';
    echo '<div>Impuesto: <b>$' . esc_html(number_format($sum_tax,0,',','.')) . '</b></div>';
    echo '<div>Total: <b>$' . esc_html(number_format($sum_gross,0,',','.')) . '</b></div>';
    echo '<div style="opacity:.7;font-size:12px;margin-top:6px;">Registros: ' . esc_html((string)$count) . '</div>';
    echo '</div>';

    echo '<div style="background:#fff;border:1px solid #ddd;border-radius:10px;padding:14px;min-width:340px;flex:1;">';
    echo '<div style="opacity:.7;font-size:12px;">Accesos</div>';
    echo '<div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">';
    echo '<a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=rsw-movimientos')) . '">Crear movimiento</a>';
    echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=rsw-timeline')) . '">Ver timeline</a>';
    echo '<a class="button" href="' . esc_url(admin_url('edit.php?post_type=shop_order')) . '">Pedidos Woo</a>';
    echo '</div>';
    echo '</div>';

    echo '</div>';
    echo '</div>';
}

// ============================================================
// EDICIÓN: página interna para editar un movimiento
// ============================================================
function rsw_edit_move_page() {
    if ( ! current_user_can('manage_woocommerce') ) wp_die('Sin permisos.');

    $move_id = isset($_GET['move_id']) ? (int)$_GET['move_id'] : 0;
    $post    = $move_id ? get_post($move_id) : null;

    if ( ! $post || $post->post_type !== 'rsw_move' ) {
        echo '<div class="wrap"><h1>Editar movimiento</h1><div class="notice notice-error"><p>No se encontró el registro.</p></div></div>';
        return;
    }

    // Config reasons (igual que crear)
    $reasons_by_type = [
        'out'  => ['merma','evento','reunion','auspicio','marketing','ajuste'],
        'in'   => ['produccion','devolucion','ajuste'],
        'sale' => ['venta_externa'],
    ];

    $reason_labels = [
        'merma'         => 'Merma',
        'evento'        => 'Evento',
        'reunion'       => 'Reunión',
        'auspicio'      => 'Auspicio',
        'marketing'     => 'Marketing General',
        'produccion'    => 'Producción (Entrada)',
        'devolucion'    => 'Devolución externa (Entrada)',
        'venta_externa' => 'Venta por fuera',
        'ajuste'        => 'Ajuste inventario',
    ];

    $tax_rates = rsw_get_tax_rates_for_select();
    $customers = rsw_get_customers_for_select(500);

    // Valores actuales
    $current_direction = (string)get_post_meta($move_id,'direction',true);
    $current_reason    = (string)get_post_meta($move_id,'reason',true);
    $current_qty       = (int)get_post_meta($move_id,'qty',true);
    $current_counter   = (string)get_post_meta($move_id,'counterparty',true);
    $current_origin    = (string)get_post_meta($move_id,'origin',true);
    $current_note      = (string)get_post_meta($move_id,'note',true);

    $current_dt = get_post_meta($move_id,'move_datetime',true);
    if ( ! $current_dt ) $current_dt = $post->post_date;

    $current_unit_net  = (float)get_post_meta($move_id,'unit_price',true);
    $current_customer  = (int)get_post_meta($move_id,'customer_id',true);
    $current_tax_rate_id = (int)get_post_meta($move_id,'tax_rate_id',true);

    // Al guardar edición
    if ( isset($_POST['rsw_edit_submit']) ) {
        check_admin_referer('rsw_edit_action', 'rsw_edit_nonce');

        [$bp, $bid] = rsw_get_base_product();
        if ( ! $bp ) {
            echo '<div class="notice notice-error"><p><b>Error:</b> No se encontró producto base con SKU JET-BASE o no gestiona inventario.</p></div>';
        } else {
            $new_direction = sanitize_text_field($_POST['direction'] ?? '');
            $new_reason    = sanitize_text_field($_POST['reason'] ?? '');
            $new_qty       = (int)($_POST['qty'] ?? 0);

            $new_counter   = sanitize_text_field($_POST['counterparty'] ?? '');
            $new_origin    = sanitize_text_field($_POST['origin'] ?? '');
            $new_note      = sanitize_textarea_field($_POST['note'] ?? '');

            $new_dt = rsw_parse_datetime_local_to_mysql( sanitize_text_field($_POST['move_datetime'] ?? '') );
            if ( ! $new_dt ) $new_dt = $current_dt;

            // Sale fields
            $new_unit_net  = isset($_POST['unit_price']) ? (float)$_POST['unit_price'] : 0;
            $new_customer  = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
            $new_tax_rate_id = isset($_POST['tax_rate_id']) ? (int)$_POST['tax_rate_id'] : 0;

            $errors = [];

            if ( ! in_array($new_direction, ['in','out','sale'], true) ) $errors[] = 'Tipo inválido.';
            if ( empty($reasons_by_type[$new_direction]) || ! in_array($new_reason, $reasons_by_type[$new_direction], true) ) {
                $errors[] = 'Motivo inválido para el tipo seleccionado.';
            }
            if ( $new_qty <= 0 ) $errors[] = 'La cantidad debe ser mayor a 0.';

            if ( $new_direction === 'sale' ) {
                if ( $new_unit_net < 0 ) $errors[] = 'El precio unitario no puede ser negativo.';
            } else {
                $new_unit_net = 0;
                $new_customer = 0;
                $new_tax_rate_id = 0;
            }

            // Ajuste de stock por edición:
            // 1) revertir movimiento antiguo
            // 2) aplicar movimiento nuevo
            $current_stock = (int)$bp->get_stock_quantity();

            // Antes de revertir/aplicar, validamos que no se vaya a ir a negativo
            // Simulamos: stock_after_revert = current_stock + delta_revert
            $stock_after_revert = $current_stock;
            if ( $current_direction === 'in' ) {
                $stock_after_revert = $current_stock - $current_qty;
            } elseif ( $current_direction === 'out' || $current_direction === 'sale' ) {
                $stock_after_revert = $current_stock + $current_qty;
            }

            // Luego aplicamos el nuevo movimiento:
            $stock_after_new = $stock_after_revert;
            if ( $new_direction === 'in' ) {
                $stock_after_new = $stock_after_revert + $new_qty;
            } elseif ( $new_direction === 'out' || $new_direction === 'sale' ) {
                $stock_after_new = $stock_after_revert - $new_qty;
            }

            if ( $stock_after_new < 0 ) {
                $errors[] = 'Stock insuficiente para aplicar la edición. Stock quedaría negativo.';
            }

            if ( $errors ) {
                echo '<div class="notice notice-error"><p><b>Revisa:</b><br>' . implode('<br>', array_map('esc_html', $errors)) . '</p></div>';
            } else {

                // 1) Revert old
                rsw_revert_stock_by_direction( $bp, $current_direction, $current_qty );

                // 2) Apply new
                rsw_apply_stock_by_direction( $bp, $new_direction, $new_qty );

                // Recalcular totales si sale
                $net_total   = 0;
                $tax_amount  = 0;
                $tax_rate    = 0;
                $gross_total = 0;

                if ( $new_direction === 'sale' ) {
                    $net_total = (float)$new_unit_net * (float)$new_qty;
                    [ $tax_amount, $tax_rate, $gross_total ] = rsw_calc_tax_from_net_by_rate_id( $new_tax_rate_id, $net_total );
                }

                // Actualizar post_date para reflejar fecha del movimiento
                $post_date     = $new_dt;
                $post_date_gmt = get_gmt_from_date($post_date);

                // Actualizar post + metas
                $title = strtoupper($new_direction) . ' • ' . $new_reason . ' • ' . $new_qty . ' u';

                wp_update_post([
                    'ID'            => $move_id,
                    'post_title'    => $title,
                    'post_date'     => $post_date,
                    'post_date_gmt' => $post_date_gmt,
                ]);

                update_post_meta($move_id, 'move_datetime', $new_dt);
                update_post_meta($move_id, 'direction', $new_direction);
                update_post_meta($move_id, 'reason', $new_reason);
                update_post_meta($move_id, 'qty', $new_qty);

                update_post_meta($move_id, 'unit_price', $new_unit_net);
                update_post_meta($move_id, 'customer_id', $new_customer);

                update_post_meta($move_id, 'tax_rate_id', $new_tax_rate_id);
                update_post_meta($move_id, 'tax_rate', $tax_rate);
                update_post_meta($move_id, 'tax_amount', $tax_amount);
                update_post_meta($move_id, 'net_total', $net_total);
                update_post_meta($move_id, 'gross_total', $gross_total);

                update_post_meta($move_id, 'counterparty', $new_counter);
                update_post_meta($move_id, 'origin', $new_origin);
                update_post_meta($move_id, 'note', $new_note);

                // Reset flag revert (para borrado posterior normal)
                update_post_meta($move_id, '_rsw_reverted', 0);

                // Refrescar variables para pintar la pantalla
                $current_direction = $new_direction;
                $current_reason    = $new_reason;
                $current_qty       = $new_qty;
                $current_counter   = $new_counter;
                $current_origin    = $new_origin;
                $current_note      = $new_note;
                $current_dt        = $new_dt;
                $current_unit_net  = $new_unit_net;
                $current_customer  = $new_customer;
                $current_tax_rate_id = $new_tax_rate_id;

                echo '<div class="notice notice-success"><p><b>OK:</b> Registro actualizado y stock recalculado automáticamente.</p></div>';
            }
        }
    }

    // datetime-local value (YYYY-MM-DDTHH:MM)
    $dt_local = '';
    if ( $current_dt ) {
        $dt_local = date('Y-m-d\TH:i', strtotime($current_dt));
    }

    $reasons_json = wp_json_encode($reasons_by_type);
    $labels_json  = wp_json_encode($reason_labels);

    echo '<div class="wrap">';
    echo '<h1>Editar movimiento #' . esc_html((string)$move_id) . '</h1>';
    echo '<p style="margin: 8px 0 14px 0;">';
    echo '<a class="button" href="' . esc_url( admin_url('admin.php?page=rsw-historial-vista') ) . '">← Volver al Historial</a> ';
    echo '<a class="button" href="' . esc_url( get_edit_post_link($move_id) ) . '">Abrir en WP</a>';
    echo '</p>';

    echo '<form method="post" style="max-width: 980px; background:#fff; padding:16px; border:1px solid #ddd; border-radius:10px;">';
    wp_nonce_field('rsw_edit_action', 'rsw_edit_nonce');

    echo '<h2 style="margin-top:0;">Datos del movimiento</h2>';
    echo '<table class="form-table"><tbody>';

    echo '<tr><th><label>Fecha del movimiento</label></th><td>';
    echo '<input type="datetime-local" name="move_datetime" value="' . esc_attr($dt_local) . '" style="width:240px;">';
    echo '<p class="description">Puedes retroceder/adelantar la fecha. Se guarda también como fecha del post.</p>';
    echo '</td></tr>';

    echo '<tr><th><label>Tipo</label></th><td>';
    echo '<select id="rsw-direction" name="direction" required>
            <option value="out" ' . selected($current_direction,'out',false) . '>Salida</option>
            <option value="in"  ' . selected($current_direction,'in',false)  . '>Entrada</option>
            <option value="sale"' . selected($current_direction,'sale',false) . '>Venta externa</option>
          </select>';
    echo '</td></tr>';

    echo '<tr><th><label>Motivo</label></th><td>';
    echo '<select id="rsw-reason" name="reason" required></select>';
    echo '</td></tr>';

    echo '<tr><th><label>Cantidad (unidades)</label></th><td>';
    echo '<input type="number" name="qty" min="1" step="1" required style="width:140px;" value="' . esc_attr((string)$current_qty) . '">';
    echo '</td></tr>';

    echo '<tr id="rsw-unit-price-row"><th><label>Precio unitario NETO (sin impuesto) (solo Venta externa)</label></th><td>';
    echo '<input id="rsw-unit-price" type="number" name="unit_price" min="0" step="0.01" style="width:200px;" value="' . esc_attr((string)$current_unit_net) . '">';
    echo '<p class="description">Neto. Luego se suma el impuesto seleccionado.</p>';
    echo '</td></tr>';

    echo '<tr id="rsw-tax-row"><th><label>Impuesto (solo Venta externa)</label></th><td>';
    echo '<select id="rsw-tax-rate" name="tax_rate_id" style="width:100%;">';
    foreach ( $tax_rates as $id => $label ) {
        $sel = selected((string)$current_tax_rate_id, (string)$id, false);
        echo '<option value="' . esc_attr($id) . '" ' . $sel . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '</td></tr>';

    echo '<tr id="rsw-customer-row"><th><label>Cliente (usuario) (solo Venta externa)</label></th><td>';
    echo '<select id="rsw-customer" name="customer_id" style="width:100%;">';
    echo '<option value="">-- Selecciona cliente (opcional) --</option>';
    foreach ( $customers as $id => $label ) {
        $sel = selected((int)$current_customer, (int)$id, false);
        echo '<option value="' . esc_attr($id) . '" ' . $sel . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '</td></tr>';

    echo '<tr><th><label>Para quién / Responsable</label></th><td>';
    echo '<input type="text" name="counterparty" style="width:100%;" value="' . esc_attr($current_counter) . '">';
    echo '</td></tr>';

    echo '<tr><th><label>Origen / Dónde / Fuente</label></th><td>';
    echo '<input type="text" name="origin" style="width:100%;" value="' . esc_attr($current_origin) . '">';
    echo '</td></tr>';

    echo '<tr><th><label>Nota</label></th><td>';
    echo '<textarea name="note" rows="3" style="width:100%;">' . esc_textarea($current_note) . '</textarea>';
    echo '</td></tr>';

    echo '</tbody></table>';

    echo '<p><button class="button button-primary" name="rsw_edit_submit" value="1">Guardar cambios</button></p>';

    echo '</form>';

    // JS: razones por tipo + mostrar/ocultar venta externa + set motivo actual
    $current_reason_js = wp_json_encode($current_reason);

    echo "<script>
    (function(){
        const reasonsByType = {$reasons_json};
        const labels = {$labels_json};
        const currentReason = {$current_reason_js};

        const directionEl  = document.getElementById('rsw-direction');
        const reasonEl     = document.getElementById('rsw-reason');

        const unitPriceRow = document.getElementById('rsw-unit-price-row');
        const unitPriceEl  = document.getElementById('rsw-unit-price');

        const customerRow  = document.getElementById('rsw-customer-row');
        const customerEl   = document.getElementById('rsw-customer');

        const taxRow       = document.getElementById('rsw-tax-row');
        const taxEl        = document.getElementById('rsw-tax-rate');

        function fillReasons(type) {
            const allowed = reasonsByType[type] || [];
            reasonEl.innerHTML = '';
            allowed.forEach(function(key){
                const opt = document.createElement('option');
                opt.value = key;
                opt.textContent = labels[key] || key;
                reasonEl.appendChild(opt);
            });

            // intenta seleccionar el motivo actual si existe
            if (currentReason) {
                const found = Array.from(reasonEl.options).some(o => o.value === currentReason);
                if (found) reasonEl.value = currentReason;
            }
        }

        function toggleSaleOnly(type){
            const isSale = type === 'sale';

            unitPriceRow.style.display = isSale ? '' : 'none';
            unitPriceEl.disabled = !isSale;
            if(!isSale) unitPriceEl.value = '';

            customerRow.style.display = isSale ? '' : 'none';
            customerEl.disabled = !isSale;
            if(!isSale) customerEl.value = '';

            taxRow.style.display = isSale ? '' : 'none';
            taxEl.disabled = !isSale;
            if(!isSale) taxEl.value = '';
        }

        directionEl.addEventListener('change', function(){
            fillReasons(directionEl.value);
            toggleSaleOnly(directionEl.value);
        });

        fillReasons(directionEl.value);
        toggleSaleOnly(directionEl.value);
    })();
    </script>";

    echo '</div>';
}

// ============================================================
// Revertir stock al borrar un registro del historial
// ============================================================
add_action('before_delete_post', 'rsw_revert_stock_on_delete', 10, 2);

function rsw_revert_stock_on_delete( $post_id, $post ) {
    if ( ! $post || $post->post_type !== 'rsw_move' ) return;
    if ( ! current_user_can('manage_woocommerce') ) return;

    $already = (int) get_post_meta($post_id, '_rsw_reverted', true);
    if ( $already === 1 ) return;

    [$base_product, $base_id] = rsw_get_base_product();
    if ( ! $base_product ) return;

    $direction = (string) get_post_meta($post_id, 'direction', true);
    $qty       = (int) get_post_meta($post_id, 'qty', true);
    if ( $qty <= 0 ) return;

    // Reversa:
    // - Si fue Entrada (in): subió stock -> al borrar, baja
    // - Si fue Salida/Venta (out/sale): bajó stock -> al borrar, sube
    rsw_revert_stock_by_direction( $base_product, $direction, $qty );

    update_post_meta($post_id, '_rsw_reverted', 1);
}
