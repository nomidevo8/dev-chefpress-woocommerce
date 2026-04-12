<?php
declare(strict_types=1);

namespace DevChefPress\Admin;

use DevChefPress\Hooks\Loader;

class OrderDetails {

    private Loader $loader;

    public function __construct(Loader $loader) {
        $this->loader = $loader;
        $this->register_hooks();
    }

    private function register_hooks(): void {
        $this->loader->add_action(
            'woocommerce_admin_order_data_after_order_details',
            $this,
            'render_meal_plan_button'
        );

        $this->loader->add_action(
            'admin_footer',
            $this,
            'render_modal_template'
        );
    }

    /**
     * 1. BUTTON in order page
     */
    public function render_meal_plan_button($order): void {

        if (!$order instanceof \WC_Order) {
            $order = wc_get_order($order);
        }

        if (!$order) return;

        $state   = $order->get_meta('_meal_plan_state');
        $pricing = $order->get_meta('_meal_plan_pricing');

        if (empty($state) && empty($pricing)) {
            return;
        }

        $order_id = $order->get_id();

        echo '<p style="margin-top:15px;">';
        echo '<button type="button" style="margin-top:30px;"
            class="button button-primary dev-chefpress-view-meal-plan"
            data-order-id="' . esc_attr($order_id) . '">
            View Meal Plan Details
        </button>';
        echo '</p>';
    }

    /**
     * 2. MODAL TEMPLATE (hidden)
     */
    public function render_modal_template(): void {
        global $pagenow;

        if ($pagenow !== 'admin.php') return;

        ?>
        <div id="devChefpressMealPlanModal" style="
            display:none;
            position:fixed;
            inset:0;
            background:rgba(0,0,0,0.6);
            z-index:999999;
            align-items:center;
            justify-content:center;
        ">
            <div style="
                background:#fff;
                width:90%;
                max-width:900px;
                max-height:90vh;
                overflow:auto;
                border-radius:12px;
                padding:20px;
                position:relative;
            ">

                <button onclick="document.getElementById('devChefpressMealPlanModal').style.display='none'"
                    style="position:absolute;right:15px;top:10px;font-size:18px;">
                    ✖
                </button>

                <div id="devChefpressMealPlanContent">
                    Loading...
                </div>

            </div>
        </div>

        <script>
        (function($){

            function buildHTML(data){

                if(!data) return "No data found";

                let html = '';

                html += `<h2>Meal Plan Details</h2>`;

                if(data.goal){
                    html += `<p><b>Goal:</b> ${data.goal}</p>`;
                }

                if(data.weight){
                    html += `<p><b>Weight:</b> ${data.weight}</p>`;
                }

                if(data.height){
                    html += `<p><b>Height:</b> ${data.height}</p>`;
                }

                if(data.address){
                    html += `<h3>Address</h3>`;
                    html += `<p>${data.address.building || ''}</p>`;
                }

                if(data.slots){
                    html += `<h3>Meals</h3>`;

                    data.slots.forEach(slot => {
                        if(slot.recipeSelected){
                            html += `<p><b>${slot.day} ${slot.meal}:</b> ${slot.recipeSelected.title}</p>`;
                        }
                    });
                }

                return html;
            }

            $(document).on('click', '.dev-chefpress-view-meal-plan', function(){

                const orderId = $(this).data('order-id');

                $('#devChefpressMealPlanModal').css('display','flex');
                $('#devChefpressMealPlanContent').html('Loading...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'devchefpress_get_meal_plan',
                        order_id: orderId
                    },
                    success: function(res){

                        if(res.success){
                            $('#devChefpressMealPlanContent').html(
                                buildHTML(res.data)
                            );
                        } else {
                            $('#devChefpressMealPlanContent').html('No data found');
                        }
                    }
                });
            });

        })(jQuery);
        </script>
        <?php
    }
}