<div class="elm_loading_panel">
    <div class="elm_spinner"></div>
</div>
<div class="elm_modal_wrapper">
    <div class="elm_modal">
        <div class="elm_modal_header">
            <h2 style="margin-top: 0;">
                <?php esc_html_e('Order Details', 'express-label-maker'); ?> #
                <?php echo esc_attr($order_data['id']); ?>
            </h2>
            <button class="elm_close_button elm_cancel_action">&times;</button>
        </div>
        <div class="elm-error" style="display: none"></div>
        <form id="elm_order_details_form">
            <div class="elm_form_columns">
                <!-- Customer's Name -->
                <label class="labels">
                    <?php esc_html_e("Customer's Name:", 'express-label-maker'); ?>
                    <input type="text" name="customer_name" value="<?php echo esc_attr($shipping['first_name'] . ' ' . $shipping['last_name']); ?>">
                </label>
                <!-- Customer's Address -->
                <label class="labels">
                    <?php esc_html_e("Customer's Address:", 'express-label-maker'); ?>
                    <input type="text" name="customer_address" value="<?php echo esc_attr(trim($address_without_house_number)); ?>">
                </label>
                <!-- House Number -->
                <label class="labels">
                    <?php esc_html_e('House Number:', 'express-label-maker'); ?>
                    <input type="text" name="house_number" value="<?php echo esc_attr($house_number); ?>">
                </label>
                <!-- City -->
                <label class="labels">
                    <?php esc_html_e('City:', 'express-label-maker'); ?>
                    <input type="text" name="city" value="<?php echo esc_attr($shipping['city']); ?>">
                </label>
                <!-- ZIP Code -->
                <label class="labels">
                    <?php esc_html_e('ZIP Code:', 'express-label-maker'); ?>
                    <input type="text" name="zip_code" value="<?php echo esc_attr($shipping['postcode']); ?>">
                </label>
                <!-- Country -->
                <label class="labels">
                    <?php esc_html_e('Country:', 'express-label-maker'); ?>
                    <input type="text" name="country" value="<?php echo esc_attr($shipping['country']); ?>">
                </label>
                <!-- Contact Person -->
                <label class="labels">
                    <?php esc_html_e('Contact Person:', 'express-label-maker'); ?>
                    <input type="text" name="contact_person" value="<?php echo esc_attr($shipping['first_name'] . ' ' . $shipping['last_name']); ?>">
                </label>
                <!-- Phone -->
                <label class="labels">
                    <?php esc_html_e('Phone:', 'express-label-maker'); ?>
                    <input type="text" name="phone" value="<?php echo esc_attr($billing['phone']); ?>">
                </label>
                <!-- Email -->
                <label class="labels">
                    <?php esc_html_e('Email:', 'express-label-maker'); ?>
                    <input type="email" name="email" value="<?php echo esc_attr($billing['email']); ?>">
                </label>
            </div>
            <div class="elm_form_columns">

            <?php //DODATI KURIRE
                switch ($courier) {
                    case 'dpd': 
            ?>

                <!-- Reference -->
                <label class="labels">
                    <?php esc_html_e('Reference:', 'express-label-maker'); ?>
                    <input type="text" name="reference" value="<?php echo esc_attr($order_data['id']); ?>">
                </label>
                <!--Payment Method -->
                <label class="labels">
                    <?php esc_html_e('Payment Method:', 'express-label-maker'); ?>
                    <input type="text" name="payment_method" value="<?php echo esc_attr($payment_method); ?>">
                </label>
                <!--Payment -->
                <label class="labels">
                    <?php esc_html_e('Payment:', 'express-label-maker'); ?>
                    <div class="payment">
                        <input type="radio" name="parcel_type" value="cod" id="x-cod" <?php $payment_method === 'cod' ? print_r('checked') : '' ?>>
                        <label for="x-cod" style="padding-right:5px;">COD</label>
                        <input type="radio" name="parcel_type" value="classic" id="x-classic" <?php $payment_method != 'cod' ? print_r('checked') : '' ?>>
                        <label for="x-classic">Classic</label>
                    </div>
                </label>
                <!-- Collection Date (Order Date) -->
                <label class="labels">
                    <?php esc_html_e('Collection Date (Order Date):', 'express-label-maker'); ?>
                    <input type="date" name="collection_date" value="<?php echo esc_attr($order_date); ?>">
                </label>
                <!-- Weight -->
                <label class="labels">
                    <?php esc_html_e('Weight:', 'express-label-maker'); ?>
                    <input type="text" name="weight" value="<?php echo esc_attr($weight); ?>">
                </label>
                <!-- Package Number -->
                <label class="labels">
                    <?php esc_html_e('Package Number:', 'express-label-maker'); ?>
                    <input type="text" name="package_number" value="<?php echo esc_attr($package_number); ?>">
                </label>
                <!-- Cash on Delivery Amount -->
                <label class="labels">
                    <?php esc_html_e('Cash on Delivery Amount:', 'express-label-maker'); ?>
                    <input type="text" name="cod_amount" value="<?php echo esc_attr($order_total); ?>">
                </label>
                <!-- Note -->
                <label class="labels">
                    <?php esc_html_e('Note:', 'express-label-maker'); ?>
                    <textarea name="note"><?php echo esc_textarea($order_data['customer_note']); ?></textarea>
                </label>

                <?php 
                    break;

                    case 'overseas': 
                ?>
                <!-- Reference -->
                <label class="labels">
                    <?php esc_html_e('Reference:', 'express-label-maker'); ?>
                    <input type="text" name="reference" value="<?php echo esc_attr($order_data['id']); ?>">
                </label>
                <!--Payment Method -->
                <label class="labels">
                    <?php esc_html_e('Payment Method:', 'express-label-maker'); ?>
                    <input type="text" name="payment_method" value="<?php echo esc_attr($payment_method); ?>" disabled>
                </label>
                <!-- Collection Date (Order Date) -->
                <label class="labels">
                    <?php esc_html_e('Collection Date (Order Date):', 'express-label-maker'); ?>
                    <input type="date" name="collection_date" value="<?php echo esc_attr($order_date); ?>">
                </label>
                <!-- Package Number -->
                <label class="labels">
                    <?php esc_html_e('Package Number:', 'express-label-maker'); ?>
                    <input type="text" name="package_number" value="<?php echo esc_attr($package_number); ?>">
                </label>
                <!-- Cash on Delivery Amount -->
                <label class="labels">
                    <?php esc_html_e('Cash on Delivery Amount:', 'express-label-maker'); ?>
                    <input type="text" name="cod_amount" value="<?php echo esc_attr($order_total); ?>">
                </label>
                <!-- Note -->
                <label class="labels">
                    <?php esc_html_e('Note:', 'express-label-maker'); ?>
                    <textarea name="note"><?php echo esc_textarea($order_data['customer_note']); ?></textarea>
                </label>

                <?php 
                    break;

                    default: 
                ?>
                <!-- Error  -->
                        <p><?php esc_html_e('Invalid courier selected.', 'express-label-maker'); ?></p>
                <?php 
                        break;
                } 
                ?>

                <!-- Hidden courier for api -->
                <input type="hidden" id="hiddenCourier" value="" />
                <input type="hidden" id="hiddenOrderId" value="<?php echo esc_attr($order_data['id']); ?>" />
        </form>
        <div class="elm_modal_actions">
            <button class="button button-primary elm_confirm_action">
                <?php esc_html_e('Print', 'express-label-maker'); ?>
            </button>
            <button class="button elm_cancel_action">
                <?php esc_html_e('Cancel', 'express-label-maker'); ?>
            </button>
        </div>
    </div>
</div>