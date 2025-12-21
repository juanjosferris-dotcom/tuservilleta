<?php
/**
 * Clase para gestionar la base de datos del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class TuServilleta_Database {

    /**
     * Nombre de la tabla de productos
     */
    public static function get_products_table() {
        global $wpdb;
        return $wpdb->prefix . 'tuservilleta_products';
    }

    /**
     * Nombre de la tabla de pedidos
     */
    public static function get_orders_table() {
        global $wpdb;
        return $wpdb->prefix . 'tuservilleta_orders';
    }

    /**
     * Crear tablas del plugin
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $products_table = self::get_products_table();
        $orders_table = self::get_orders_table();

        $sql_products = "CREATE TABLE IF NOT EXISTS $products_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            size varchar(100) NOT NULL,
            type varchar(100) NOT NULL,
            color varchar(100) NOT NULL,
            printing varchar(100) NOT NULL,
            quantity varchar(50) NOT NULL,
            price decimal(10,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_size (size),
            KEY idx_type (type),
            KEY idx_color (color),
            KEY idx_printing (printing),
            KEY idx_quantity (quantity)
        ) $charset_collate;";

        $sql_orders = "CREATE TABLE IF NOT EXISTS $orders_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            size varchar(100) NOT NULL,
            type varchar(100) NOT NULL,
            color varchar(100) NOT NULL,
            printing varchar(100) NOT NULL,
            quantity varchar(50) NOT NULL,
            price decimal(10,2) NOT NULL,
            price_with_vat decimal(10,2) NOT NULL,
            customer_name varchar(200) NOT NULL,
            customer_email varchar(200) NOT NULL,
            customer_phone varchar(50),
            customer_company varchar(200),
            order_type varchar(20) NOT NULL DEFAULT 'contact',
            payment_status varchar(20) DEFAULT 'pending',
            payment_method varchar(50),
            payment_id varchar(200),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_products);
        dbDelta($sql_orders);
    }

    /**
     * Importar productos desde CSV
     */
    public static function import_csv($file_path) {
        global $wpdb;
        $table = self::get_products_table();
        
        // Limpiar tabla existente
        $wpdb->query("TRUNCATE TABLE $table");
        
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            return array('success' => false, 'message' => 'No se pudo abrir el archivo');
        }

        $headers = fgetcsv($handle, 0, ';');
        if ($headers === false) {
            fclose($handle);
            return array('success' => false, 'message' => 'El archivo está vacío');
        }

        // Normalizar headers (quitar BOM UTF-8 y caracteres de control, mantener UTF-8 válido)
        $headers = array_map(function($h) {
            // Quitar BOM UTF-8 (0xEF 0xBB 0xBF) al inicio
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            // Quitar caracteres de control (0x00-0x1F excepto tab, newline, etc.)
            $h = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $h);
            return strtolower(trim($h));
        }, $headers);
        
        $expected = array('size', 'type', 'color', 'printing', 'quantity', 'price');
        
        foreach ($expected as $col) {
            if (!in_array($col, $headers)) {
                fclose($handle);
                return array('success' => false, 'message' => "Columna '$col' no encontrada en el CSV");
            }
        }

        $count = 0;
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 6) continue;
            
            $data = array();
            foreach ($headers as $index => $header) {
                if (isset($row[$index])) {
                    // Limpiar solo caracteres de control, mantener UTF-8 válido (acentos, etc.)
                    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $row[$index]);
                    $data[$header] = trim($value);
                }
            }

            // Saltar filas vacías
            if (empty($data['size']) && empty($data['type'])) {
                continue;
            }

            // Convertir precio a número (formato español: 1.234,56)
            $price = $data['price'];
            // Detectar si usa formato español (coma como decimal)
            // En formato español: 1.234,56 -> la coma viene después de los puntos
            // En formato inglés: 1,234.56 -> el punto viene después de las comas
            if (strpos($price, ',') !== false) {
                $lastComma = strrpos($price, ',');
                $lastDot = strrpos($price, '.');
                
                if ($lastDot === false || $lastComma > $lastDot) {
                    // Formato español: puntos son miles, coma es decimal
                    $price = str_replace('.', '', $price);
                    $price = str_replace(',', '.', $price);
                } else {
                    // Formato inglés: comas son miles, punto es decimal
                    $price = str_replace(',', '', $price);
                }
            }
            // Quitar cualquier caracter no numérico excepto punto decimal
            $price = preg_replace('/[^0-9.]/', '', $price);

            $wpdb->insert($table, array(
                'size' => sanitize_text_field($data['size']),
                'type' => sanitize_text_field($data['type']),
                'color' => sanitize_text_field($data['color']),
                'printing' => sanitize_text_field($data['printing']),
                'quantity' => sanitize_text_field($data['quantity']),
                'price' => floatval($price)
            ), array('%s', '%s', '%s', '%s', '%s', '%f'));
            
            $count++;
        }

        fclose($handle);
        return array('success' => true, 'message' => "$count productos importados correctamente");
    }

    /**
     * Obtener opciones disponibles basadas en selecciones anteriores
     */
    public static function get_available_options($step, $selections = array()) {
        global $wpdb;
        $table = self::get_products_table();

        $where = array('1=1');
        $values = array();

        // Validar que el paso sea válido (whitelist para prevenir SQL injection)
        $valid_steps = array('size', 'type', 'color', 'printing', 'quantity');
        if (!in_array($step, $valid_steps, true)) {
            return array();
        }

        // Aplicar filtros basados en selecciones anteriores
        foreach ($valid_steps as $s) {
            if ($s === $step) break;
            if (!empty($selections[$s])) {
                $where[] = "$s = %s";
                $values[] = $selections[$s];
            }
        }

        $where_clause = implode(' AND ', $where);
        
        if (empty($values)) {
            $sql = "SELECT DISTINCT $step FROM $table WHERE $where_clause ORDER BY $step";
        } else {
            $sql = $wpdb->prepare(
                "SELECT DISTINCT $step FROM $table WHERE $where_clause ORDER BY $step",
                $values
            );
        }

        $results = $wpdb->get_col($sql);
        return $results ? $results : array();
    }

    /**
     * Obtener precio para una combinación específica
     */
    public static function get_price($selections) {
        global $wpdb;
        $table = self::get_products_table();

        $sql = $wpdb->prepare(
            "SELECT price FROM $table WHERE size = %s AND type = %s AND color = %s AND printing = %s AND quantity = %s LIMIT 1",
            $selections['size'],
            $selections['type'],
            $selections['color'],
            $selections['printing'],
            $selections['quantity']
        );

        return $wpdb->get_var($sql);
    }

    /**
     * Guardar pedido
     */
    public static function save_order($data) {
        global $wpdb;
        $table = self::get_orders_table();

        $price = floatval($data['price']);
        $vat = $price * 0.21;
        $price_with_vat = $price + $vat;

        $result = $wpdb->insert($table, array(
            'size' => sanitize_text_field($data['size']),
            'type' => sanitize_text_field($data['type']),
            'color' => sanitize_text_field($data['color']),
            'printing' => sanitize_text_field($data['printing']),
            'quantity' => sanitize_text_field($data['quantity']),
            'price' => $price,
            'price_with_vat' => $price_with_vat,
            'customer_name' => sanitize_text_field($data['customer_name']),
            'customer_email' => sanitize_email($data['customer_email']),
            'customer_phone' => sanitize_text_field($data['customer_phone']),
            'customer_company' => sanitize_text_field($data['customer_company']),
            'order_type' => sanitize_text_field($data['order_type']),
            'payment_status' => 'pending',
            'payment_method' => isset($data['payment_method']) ? sanitize_text_field($data['payment_method']) : null,
        ), array('%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));

        if ($result) {
            return $wpdb->insert_id;
        }
        return false;
    }

    /**
     * Actualizar estado de pago
     */
    public static function update_payment_status($order_id, $status, $payment_id = null) {
        global $wpdb;
        $table = self::get_orders_table();

        $data = array('payment_status' => $status);
        if ($payment_id) {
            $data['payment_id'] = $payment_id;
        }

        return $wpdb->update($table, $data, array('id' => $order_id));
    }

    /**
     * Obtener todos los pedidos
     */
    public static function get_orders($limit = 50, $offset = 0) {
        global $wpdb;
        $table = self::get_orders_table();
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
    }

    /**
     * Contar productos
     */
    public static function count_products() {
        global $wpdb;
        $table = self::get_products_table();
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }
}
