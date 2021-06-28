<?php

/**
 * The file that defines the functionality of plugin
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://cubixsol.com
 * @since      1.0.0
 *
 * @package    wc-discount
 * @subpackage wc-discount/includes
 */

class Wc_Discount_Admin_Screen {

    /**
     * Constructor
     */
    public function __construct() {

        /**
         * Add Plugin Admin page
         */
        add_action( 'admin_menu', array($this, 'admin_menu') );

        /**
         * Redirect to admin page
         */
        add_action( 'admin_init', array( $this, 'wc_discount_welcome' ) );

        /**
         * Calculate Discount prices
         */
        add_action( 'woocommerce_before_add_to_cart_button', array($this, 'wc_discount_calculate_pricing') );

        /**
         * Add items Price to cart items
         */
        add_filter( 'woocommerce_add_cart_item_data', array($this, 'wc_discount_add_cart_item_data'), 99, 2 );

        /**
         * Show Discount Amount to Cart and Checkout page
         */
        add_filter('woocommerce_get_item_data', array($this, 'wc_discount_display_discount_cart'), 10, 2);

        /**
         * Set new Price to cart items
         */
        add_action( 'woocommerce_before_calculate_totals', array($this, 'wc_discount_calculate_total_price'), 99 );

        /**
         * Export CSV
         */
        add_action( 'wp_loaded', array($this, 'export_file'));

        /**
         * Variation onchange Discount Rules
         */
        add_action( 'wp_ajax_variation_ajax', array($this, 'variation_ajax') );
        add_action( 'wp_ajax_nopriv_variation_ajax', array($this, 'variation_ajax') );

        /**
         * Remove a rule
         */
        add_action( 'wp_ajax_remove_discount', array($this, 'remove_discount') );
        add_action( 'wp_ajax_nopriv_remove_discount', array($this, 'remove_discount') );

    }

    /**
     * Redirect to admin page
     */
    public function wc_discount_welcome() {

        if ( !get_transient( '_welcome_screen_activation_redirect' ) ) {
            return;
        }
        delete_transient( '_welcome_screen_activation_redirect' );

        wp_safe_redirect( add_query_arg( array( 'page' => 'wc_discount' ), admin_url( 'admin.php' ) ) );

    }

    /**
     * Admin Menu
     */
    public function admin_menu() {

        add_submenu_page(
			'woocommerce',
			__('Wc Discount', 'wc-discount'),
			__('Wc Discount', 'wc-discount'),
			apply_filters('woocommerce_csv_product_role', 'manage_woocommerce'),
			'wc_discount',
			array($this, 'output')
		);

    }

    /**
     * Admin Screen output
     */
    public function output() {

        global $wpdb;
        $table = $wpdb->prefix."wc_discount";

        if(isset($_POST['submit']))
        {
            $customer_id = $_POST['customer_id'];
            $category_id = $_POST['category_id'];
            $price       = $_POST['price'];

            $exit = $wpdb->get_results("SELECT * FROM {$table} WHERE customer_id = {$customer_id} AND category_id = {$category_id}");

            if(empty($exit))
            {
                $wpdb->insert( $table, array(
                    'customer_id' => $customer_id,
                    'category_id' => $category_id,
                    'price'       => $price,
                ));

                $message = 'Discount Rule Successfully Inserted';
            } else {
                $message = 'Discount Rule Successfully Updated';
                $wpdb->update($table, ['price' => $price], ['id' => $exit[0]->id] );
            }
        } else {
            $message = '';
        }

        if( isset($_POST['import']) )
        {
            $handle  = fopen($_FILES['import_csv']['tmp_name'], "r");
            $headers = fgetcsv($handle, 1000, ",");
            
            while (($csv_data = fgetcsv($handle, 1000, ",")) !== FALSE) 
            {
                $exit_user = username_exists($csv_data[0]);
                $exit_cat  = term_exists($csv_data[1], 'product_cat');

                if(!empty($exit_user) && !empty($exit_cat) && !empty($csv_data[2]))
                {
                    $exit = $wpdb->get_results("SELECT * FROM {$table} WHERE customer_id = {$exit_user} AND category_id = {$exit_cat['term_id']}");
                    if(empty($exit))
                    {
                        $wpdb->insert( $table, array(
                            'customer_id' => $exit_user,
                            'category_id' => $exit_cat['term_id'],
                            'price'       => $csv_data[2],
                        ));
                    } else {
                        $wpdb->update($table, ['price' => $csv_data[2]], ['id' => $exit[0]->id] );
                    }
                }
            }

            fclose($handle);
        }

        if(isset($_POST['bulk_deleted_wc_discount']))
        {
            $wpdb->query("TRUNCATE TABLE {$table}");
        }

        $users = get_users();
        $terms = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ) );

        $wc_discount = $wpdb->get_results("SELECT * FROM {$table}");
        $home_url     = home_url('/');
        ?>

        <div class="wc-discount">
            <h2 class="wc-discount-heading"><?php _e('Wc Discount By Categories to Specific User', 'wc-discount');?> </h2>

            <p style="margin-top: 5px;float: left;">
                <form action="" method="POST" class="export_csv">
                    <div>
                        <input type="submit" name="bulk_deleted_wc_discount" value="<?php _e('Bulk Deleted Wc Discount', 'wc-discount');?>" class="bulk-delete">
                    </div>
                    <div>
                        <a href="<?php echo $home_url.'?download=export-csv&wc-discount=true'; ?>" class="export"><?php _e('Export CSV', 'wc-discount');?></a>
                    </div>
                </form>

                <form action="" method="POST" enctype='multipart/form-data' class="import_csv">
                    <div>
                        <input type="file" name="import_csv">
                    </div>
                    <div>
                        <input type="submit" name="import" value="<?php _e('Import', 'wc-discount');?> ">
                    </div>
                </form>
            </p>

            <form action="" method="POST" class="discount-rules-form">
                <?php echo '<p>'.$message.'</p>'; ?>
                <div class="form-field">
                    <select name="customer_id" class="customer_id discount-select2" required>
                        <option value=""> <?php _e('Select User', 'wc-discount');?> </option>
                        <?php
                        if(!empty($users))
                        {
                            foreach($users as $user)
                            {
                                $user_data  = get_userdata( $user->ID );
                                $user_roles = $user_data->roles;
                                $user_role  = $user_roles[0];

                                echo '<option value="'.$user->ID.'"> '.__($user->data->user_login.' ('.$user_role.')', "wc-discount").' </option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-field">
                    <select name="category_id" class="category_id discount-select2" required>
                        <option value=""> <?php _e('Select Category', 'wc-discount');?> </option>
                        <?php
                        if(!empty($terms))
                        {
                            foreach($terms as $term)
                            {
                                echo '<option value="'.$term->term_id.'"> '.__($term->name, "wc-discount").' </option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-field">
                    <input type="number" name="price" placeholder="<?php _e('Enter Price', 'wc-discount');?>" min="1" required>
                </div>

                <div class="form-field">
                    <input type="<?php _e('submit', 'wc-discount');?>" name="submit">
                </div>
            </form>

            <table class="custom-datatable display" width="100%" border="1">
                <thead>
                    <tr>
                        <th><?php _e('Sr', 'wc-discount');?></th>
                        <th><?php _e('User', 'wc-discount');?></th>
                        <th><?php _e('Category', 'wc-discount');?></th>
                        <th><?php _e('Price %', 'wc-discount');?></th>
                        <th><?php _e('Action', 'wc-discount');?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if(!empty($wc_discount))
                    {
                        foreach($wc_discount as $data)
                        {
                            $id          = $data->id;
                            $customer_id = $data->customer_id;
                            $userdata    = get_userdata($customer_id);
                            $user_link   = get_edit_user_link($customer_id);

                            $category_id = $data->category_id;
                            $category    = get_term_by('id', $category_id, 'product_cat');
                            $cat_link    = get_edit_term_link($category_id, 'product_cat');
                            
                            echo '<tr class="wc_discount_'.$id.'">';
                                echo '<td>'.$id.'</td>';
                                echo '<td><a class="a-user" href="'.$user_link.'" target="_blank">'.__($userdata->data->user_login, "wc-discount").'</a></td>';
                                echo '<td><a class="a-cats" href="'.$cat_link.'" target="_blank">'.__($category->name, "wc-discount").'</a></td>';
                                echo '<td>'.__($data->price, "wc-discount").'</td>';
                                ?>
                                <td><button class="wc_discount_deleting" onclick="wc_discount_deleting_id('<?php echo $id; ?>')"><?php _e('Delete', 'wc-discount');?></button></td>
                                <?php
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php

    }

    /**
     * Calculate discount prices
     */
    public function wc_discount_calculate_pricing() {

        if(is_user_logged_in())
        {
            global $wpdb;
            $table = $wpdb->prefix . "wc_discount";

            $product_id = get_the_ID();
            $terms      = get_the_terms($product_id, 'product_cat');
            $user_id    = get_current_user_id();
            $price      = $total_price = '';
            
            if(!empty($terms))
            {
                foreach($terms as $term)
                {
                    $exit = $wpdb->get_results("SELECT * FROM {$table} WHERE customer_id = {$user_id} AND category_id = {$term->term_id}");
                    if(!empty($exit))
                    {
                        $price = $exit[0]->price;
                        break;
                    }
                }
            }

            if(!empty($price))
            {
                $product  = wc_get_product($product_id);

                if($product->is_type('simple'))
                {
                    $single_price = $product->get_price();
                    
                    $discount_price = ( $single_price * ($price / 100) );
                    $total_price    = $single_price - $discount_price;
                    
                    echo '<ul class="wc_discount_layout">';
                        echo '<li><span>Price before discount: </span> <span>'.wc_price($single_price).'</span></li>';
                        echo '<li><span>Price after discount: </span> <span>'.wc_price($total_price).'</span></li>';
                        echo '<li><span>Discount percentage: </span> <span>'.$price.'%</span></li>';
                        echo '<li><span>Discount amount Performance: </span> <span>'.wc_price($discount_price).'</span></li>';
                    echo '</ul>';
                } else {
                    echo '<ul class="wc_discount_layout wc_discount_layout_variation">';
                        echo '<li><span>Price before discount: </span> <span class="price"></span></li>';
                        echo '<li><span>Price after discount: </span> <span class="total_price"></span></li>';
                        echo '<li><span>Discount percentage: </span> <span>'.$price.'%</span></li>';
                        echo '<li><span>Discount amount Performance: </span> <span class="discount_price"></span></li>';
                    echo '</ul>';
                }
            }
            
            echo '<input type="hidden" value="'.$price.'" class="discount_filter" name="discount_filter">';
            echo '<input type="hidden" value="'.$total_price.'" class="discount_filter_price" name="discount_filter_price">';
        }

    }

    /**
     * Add items Price to cart items
     */
    function wc_discount_add_cart_item_data($cart_item_data, $product_id) {
     
        if( isset($_POST['discount_filter_price']) && !empty($_POST['discount_filter_price']) )
        {
            $cart_item_data["discount_filter_price"] = $_POST['discount_filter_price'];
            $cart_item_data["discount_filter"]       = $_POST['discount_filter'];
        }
        return $cart_item_data;

    }

    /**
     * Set new Price to cart items
     */
    function wc_discount_calculate_total_price($cart) {

        foreach ( WC()->cart->get_cart() as $key => $value )
        {
            if(isset($value["discount_filter_price"]))
            {
                $discount_filter_price = $value["discount_filter_price"];
                if( method_exists($value['data'], "set_price") )
                {
                    $value['data']->set_price($discount_filter_price);
                } else {
                    $value['data']->price = $discount_filter_price;
                }           
            }
        }

    }

    /**
     * Export files to csv
     */
    public function export_file() {

        if(isset($_REQUEST['download']) && $_REQUEST['download'] == 'export-csv' && isset($_REQUEST['wc-discount']) && $_REQUEST['wc-discount'] == 'true')
        {
            global $wpdb;
            $table = $wpdb->prefix."wc_discount";

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="Export.csv"');

            $data    = [];
            $data[0] = [ 'username', 'categories', 'price'];

            $discount = $wpdb->get_results("SELECT * FROM {$table}");

            $i = 1;
            foreach($discount as $obj)
            {
                $userdata = get_userdata($obj->customer_id);
                $category = get_term_by('id', $obj->category_id, 'product_cat');
                $data[$i] = [
                    $userdata->data->user_login,
                    $category->name,
                    $obj->price
                ];

                $i++;
            }

            $f = fopen('php://output', 'wb');
            foreach ($data as $line)
            {
                fputcsv($f, $line, ',');
            }

            fclose($f);
            exit;
        }

    }

    /**
     * Calculate price for variations
     */ 
    public function variation_ajax() {

        $result = [];
        if(isset($_POST['id']) && !empty($_POST['id']))
        {
            if(isset($_POST['price']) && !empty($_POST['price']))
            {
                $product_id     = $_POST['id'];
                $product        = wc_get_product($product_id);
                $single_price   = $product->get_price();
                $discount_price = ( $single_price * ($_POST['price'] / 100) );
                $total_price    = $single_price - $discount_price;

                $result['price']               = wc_price($single_price);
                $result['total_price_to_show'] = wc_price($total_price);
                $result['total_price']         = $total_price;
                $result['discount_price']      = wc_price($discount_price);
            }
        }

        echo json_encode($result);
        exit;

    }

    /**
     * Remove discount rules
     */
    public function remove_discount() {

        if(isset($_POST['id']))
        {
            global $wpdb;
            $table = $wpdb->prefix."wc_discount";
            $wpdb->delete($table, array('id' => $_POST['id']));
        }
        exit;

    }

    /**
     * Display Discount % in the cart.
     *
     * @param array $item_data
     * @param array $cart_item
     *
     * @return array
     */
    function wc_discount_display_discount_cart($item_data, $cart_item) {

        if (empty($cart_item['discount_filter'])) {
            return $item_data;
        }

        $item_data[] = array(
            'key'     => __('Discount applied', 'wc-discount'),
            'value'   => wc_clean($cart_item['discount_filter']).'%',
            'display' => '',
        );
        return $item_data;

    }

}
new Wc_Discount_Admin_Screen();