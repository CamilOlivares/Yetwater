add_action( 'woocommerce_order_status_changed', 'jetwater_apply_pack_stock_changed', 10, 4 );

function jetwater_apply_pack_stock_changed( $order_id, $from, $to, $order ) {

    // Solo cuando pasa a processing o completed
    if ( ! in_array( $to, array( 'processing', 'completed' ), true ) ) {
        return;
    }

    if ( ! $order instanceof WC_Order ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;
    }

    // Debug visible: sabrás si corrió
    $order->add_order_note( 'JetWater DEBUG: snippet ejecutado al pasar a ' . $to );

    // Evita doble descuento
    if ( $order->get_meta( '_jetwater_pack_stock_applied' ) ) {
        $order->add_order_note( 'JetWater DEBUG: ya aplicado, no vuelvo a descontar.' );
        return;
    }

    // Producto base por SKU (tu “bodega”)
    $base_sku = 'JET-BASE';
    $base_id  = wc_get_product_id_by_sku( $base_sku );

    if ( ! $base_id ) {
        $order->add_order_note( 'JetWater ERROR: no existe producto base con SKU JET-BASE.' );
        return;
    }

    $base_product = wc_get_product( $base_id );

    if ( ! $base_product || ! $base_product->managing_stock() ) {
        $order->add_order_note( 'JetWater ERROR: el producto base no gestiona inventario.' );
        return;
    }

    $total_units_to_reduce = 0;

    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( ! $product ) continue;

        $name = strtolower( $product->get_name() );
        $qty  = (int) $item->get_quantity();

        $multiplier = 0;
        if ( strpos( $name, 'pack 12' ) !== false ) $multiplier = 12;
        elseif ( strpos( $name, 'pack 24' ) !== false ) $multiplier = 24;
        elseif ( strpos( $name, 'pack 36' ) !== false ) $multiplier = 36;
        elseif ( strpos( $name, 'pack 48' ) !== false ) $multiplier = 48;

        if ( $multiplier > 0 ) {
            $total_units_to_reduce += ( $multiplier * $qty );
        }
    }

    if ( $total_units_to_reduce <= 0 ) {
        $order->add_order_note( 'JetWater ERROR: no detecté packs (pack 12/24/36/48) en el pedido.' );
        return;
    }

    wc_update_product_stock( $base_product, $total_units_to_reduce, 'decrease' );

    $order->update_meta_data( '_jetwater_pack_stock_applied', 1 );
    $order->update_meta_data( '_jetwater_pack_units_reduced', $total_units_to_reduce );
    $order->save();

    $order->add_order_note( 'JetWater OK: descontadas ' . $total_units_to_reduce . ' unidades del SKU base JET-BASE.' );
}

