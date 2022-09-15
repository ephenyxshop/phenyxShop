<?php

class CustomerPiecesHistoryCore extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /** @var int Order id */
    public $id_customer_piece;
    /** @var int Order status id */
    public $id_customer_piece_state;
    /** @var int Employee id for this history entry */
    public $id_employee;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'customer_piece_history',
        'primary' => 'id_customer_piece_history',
        'fields'  => [
            'id_customer_piece'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_customer_piece_state' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_employee'             => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'date_add'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * @see  PhenyxObjectModel::$webserviceParameters
     */
    protected $webserviceParameters = [
        'objectsNodeName' => 'order_histories',
        'fields'          => [
            'id_employee'             => ['xlink_resource' => 'employees'],
            'id_customer_piece_state' => ['required' => true, 'xlink_resource' => 'order_states'],
            'id_customer_piece'       => ['xlink_resource' => 'orders'],
        ],
        'objectMethods'   => [
            'add' => 'addWs',
        ],
    ];

    public function changeIdOrderState($newOrderState, $idOrder, $useExistingPayment = false) {

        if (!$newOrderState || !$idOrder) {
            return;
        }

        if (is_numeric($idOrder)) {
            $order = new CustomerPieces((int) $idOrder);
        } else if ($idOrder instanceof CustomerPieces) {
            $order = $idOrder;
        } else {
            return;
        }

       

        $newOs = new CustomerPieceState((int) $newOrderState, $order->id_lang);
        $oldOs = $order->getCurrentOrderState();

        // executes hook

        if (in_array($newOs->id, [Configuration::get('EPH_OS_PAYMENT'), Configuration::get('EPH_OS_WS_PAYMENT')])) {
            Hook::exec('actionPaymentConfirmation', ['id_customer_piece' => (int) $order->id], null, false, true, false, $order->id_shop);
        }

        // executes hook
        Hook::exec('actionOrderStatusUpdate', ['newOrderStatus' => $newOs, 'id_customer_piece' => (int) $order->id], null, false, true, false, $order->id_shop);

        if (Validate::isLoadedObject($order) && ($newOs instanceof CustomerPieceState)) {
            $context = Context::getContext();

            // An email is sent the first time a virtual item is validated
            $virtualProducts = $order->getVirtualProducts();

            if (is_array($virtualProducts) && !empty($virtualProducts) && (!$oldOs || !$oldOs->logable) && $newOs && $newOs->logable) {
                $assign = [];

                foreach ($virtualProducts as $key => $virtualProduct) {
                    $idProductDownload = ProductDownload::getIdFromIdProduct($virtualProduct['id_product']);
                    $productDownload = new ProductDownload($idProductDownload);
                    // If this virtual item has an associated file, we'll provide the link to download the file in the email

                    if ($productDownload->display_filename != '') {
                        $assign[$key]['name'] = $productDownload->display_filename;
                        $downloadLink = $productDownload->getTextLink(false, $virtualProduct['download_hash']) . '&id_customer_piece=' . (int) $order->id ;
                        $assign[$key]['link'] = $downloadLink;

                        if (isset($virtualProduct['download_deadline']) && $virtualProduct['download_deadline'] != '0000-00-00 00:00:00') {
                            $assign[$key]['deadline'] = Tools::displayDate($virtualProduct['download_deadline']);
                        }

                        if ($productDownload->nb_downloadable != 0) {
                            $assign[$key]['downloadable'] = (int) $productDownload->nb_downloadable;
                        }

                    }

                }

                $customer = new Customer((int) $order->id_customer);

                $links = '<ul>';

                foreach ($assign as $product) {
                    $links .= '<li>';
                    $links .= '<a href="' . $product['link'] . '">' . Tools::htmlentitiesUTF8($product['name']) . '</a>';

                    if (isset($product['deadline'])) {
                        $links .= '&nbsp;' . Tools::htmlentitiesUTF8(Tools::displayError('expires on', false)) . '&nbsp;' . $product['deadline'];
                    }

                    if (isset($product['downloadable'])) {
                        $links .= '&nbsp;' . Tools::htmlentitiesUTF8(sprintf(Tools::displayError('downloadable %d time(s)', false), (int) $product['downloadable']));
                    }

                    $links .= '</li>';
                }

                $links .= '</ul>';

                if (!empty($assign)) {
                    $tpl = $this->context->smarty->createTemplate(_EPH_MAIL_DIR_ . '/download_product.tpl');
                    $tpl->assign([
                        'lastname'        => $customer->lastname,
                        'firstname'       => $customer->firstname,
                        'id_order'        => (int) $order->id,
                        'order_name'      => $order->prefix . $order->piece_number,
                        'nbProducts'      => count($virtualProducts),
                        'virtualProducts' => $links,
                    ]);

                    $postfields = [
                        'sender'      => [
                            'name'  => "Sevice Commerciale " . Configuration::get('EPH_SHOP_NAME'),
                            'email' => 'no-reply@' . Configuration::get('EPH_SHOP_URL'),
                        ],
                        'to'          => [
                            [
                                'name'  => $customer->firstname . ' ' . $customer->lastname,
                                'email' => $customer->email,
                            ],
                        ],

                        'subject'     => $this->l('The virtual product that you bought is available for download'),
                        "htmlContent" => $tpl->fetch(),
                    ];

                    $result = Tools::sendEmail($postfields);
                }

            }

            // @since 1.5.0 : gets the stock manager
            $manager = null;

            if (Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT')) {
                $manager = StockManagerFactory::getManager();
            }

            $errorOrCanceledStatuses = [Configuration::get('EPH_OS_ERROR'), Configuration::get('EPH_OS_CANCELED')];

            $employee = null;

            if (!(int) $this->id_employee || !Validate::isLoadedObject(($employee = new Employee((int) $this->id_employee)))) {

                if (!Validate::isLoadedObject($oldOs) && $context != null) {
                    // First OrderHistory, there is no $old_os, so $employee is null before here
                    $employee = $context->employee; // filled if from BO and order created (because no old_os)

                    if ($employee) {
                        $this->id_employee = $employee->id;
                    }

                } else {
                    $employee = null;
                }

            }

            // foreach products of the order

            foreach ($order->getProductsDetail() as $product) {

                if (Validate::isLoadedObject($oldOs)) {
                    // if becoming logable => adds sale

                    if ($newOs->logable && !$oldOs->logable) {
                        ProductSale::addProductSale($product['id_product'], $product['product_quantity']);
                        // @since 1.5.0 - Stock Management

                        if (!Pack::isPack($product['id_product']) &&
                            in_array($oldOs->id, $errorOrCanceledStatuses) &&
                            !StockAvailable::dependsOnStock($product['id_product'])) {
                            StockAvailable::updateQuantity($product['id_product'], $product['id_product_attribute'], -(int) $product['product_quantity']);
                        }

                    } else if (!$newOs->logable && $oldOs->logable) {
                        // if becoming unlogable => removes sale
                        ProductSale::removeProductSale($product['id_product'], $product['product_quantity']);

                        // @since 1.5.0 - Stock Management

                        if (!Pack::isPack($product['id_product']) &&
                            in_array($newOs->id, $errorOrCanceledStatuses) &&
                            !StockAvailable::dependsOnStock($product['id_product'])) {
                            StockAvailable::updateQuantity($product['id_product'], $product['id_product_attribute'], (int) $product['product_quantity']);
                        }

                    } else if (!$newOs->logable && !$oldOs->logable &&
                        in_array($newOs->id, $errorOrCanceledStatuses) &&
                        !in_array($oldOs->id, $errorOrCanceledStatuses) &&
                        !StockAvailable::dependsOnStock($product['id_product'])) {
                        // if waiting for payment => payment error/canceled
                        StockAvailable::updateQuantity($product['id_product'], $product['id_product_attribute'], (int) $product['product_quantity']);
                    }

                }

                if ($newOs->shipped == 1 && (!Validate::isLoadedObject($oldOs) || $oldOs->shipped == 0) &&
                    Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT') &&
                    Warehouse::exists($product['id_warehouse']) &&
                    $manager != null &&
                    (int) $product['advanced_stock_management'] == 1) {
                    // gets the warehouse
                    $warehouse = new Warehouse($product['id_warehouse']);

                    // decrements the stock (if it's a pack, the StockManager does what is needed)
                    $manager->removeProduct(
                        $product['id_product'],
                        $product['id_product_attribute'],
                        $warehouse,
                        $product['product_quantity'],
                        Configuration::get('EPH_STOCK_CUSTOMER_ORDER_REASON'),
                        true,
                        (int) $order->id,
                        0,
                        $employee
                    );
                } else if ($newOs->shipped == 0 && Validate::isLoadedObject($oldOs) && $oldOs->shipped == 1 &&
                    Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT') &&
                    Warehouse::exists($product['id_warehouse']) &&
                    $manager != null &&
                    (int) $product['advanced_stock_management'] == 1) {

                    if (Pack::isPack($product['id_product'])) {
                        $packProducts = Pack::getItems($product['id_product'], Configuration::get('EPH_LANG_DEFAULT', null, null, $order->id_shop));

                        if (is_array($packProducts && !empty($packProducts))) {

                            foreach ($packProducts as $packProduct) {

                                if ($packProduct->advanced_stock_management == 1) {
                                    $mvts = StockMvt::getNegativeStockMvts($order->id, $packProduct->id, 0, $packProduct->pack_quantity * $product['product_quantity']);

                                    foreach ($mvts as $mvt) {
                                        $manager->addProduct(
                                            $packProduct->id,
                                            0,
                                            new Warehouse($mvt['id_warehouse']),
                                            $mvt['physical_quantity'],
                                            null,
                                            $mvt['price_te'],
                                            true,
                                            null
                                        );
                                    }

                                    if (!StockAvailable::dependsOnStock($product['id_product'])) {
                                        StockAvailable::updateQuantity($packProduct->id, 0, (int) $packProduct->pack_quantity * $product['product_quantity']);
                                    }

                                }

                            }

                        }

                    } else {
                        // else, it's not a pack, re-stock using the last negative stock mvts
                        $mvts = StockMvt::getNegativeStockMvts(
                            $order->id,
                            $product['id_product'],
                            $product['id_product_attribute'],
                            $product['product_quantity']
                        );

                        foreach ($mvts as $mvt) {
                            $manager->addProduct(
                                $product['id_product'],
                                $product['id_product_attribute'],
                                new Warehouse($mvt['id_warehouse']),
                                $mvt['physical_quantity'],
                                null,
                                $mvt['price_te'],
                                true
                            );
                        }

                    }

                }

            }

        }

        $this->id_customer_piece_state = (int) $newOrderState;

        // changes invoice number of order ?

        if (!Validate::isLoadedObject($newOs) || !Validate::isLoadedObject($order)) {
            die(Tools::displayError('Invalid new order status'));
        }

        // the order is valid if and only if the invoice is available and the order is not cancelled
        $order->current_state = $this->id_customer_piece_state;
        $order->validate = $newOs->logable;
        $order->update();

        if ($newOs->invoice && !$order->piece_number) {
            $order->setInvoice($useExistingPayment);
        } else if ($newOs->delivery && !$order->delivery_number) {
            $order->setDeliverySlip();
        }

        // set orders as paid

        if ($newOs->paid == 1) {
            $invoices = $order->getInvoicesCollection();

            if ($order->total_paid != 0) {
                $paymentMethod = Module::getInstanceByName($order->module);
            }

            foreach ($invoices as $invoice) {
                /** @var OrderInvoice $invoice */
                $restPaid = $invoice->getRestPaid();

                if ($restPaid > 0) {
                    $payment = new OrderPayment();
                    $payment->order_reference = mb_substr($order->reference, 0, 9);
                    $payment->id_currency = $order->id_currency;
                    $payment->amount = $restPaid;

                    if (isset($paymentMethod) && $order->total_paid != 0) {
                        $payment->payment_method = $paymentMethod->displayName;
                    } else {
                        $payment->payment_method = null;
                    }

                    // Update total_paid_real value for backward compatibility reasons

                    if ($payment->id_currency == $order->id_currency) {
                        $order->total_paid_real += $payment->amount;
                    } else {
                        $order->total_paid_real += Tools::ps_round(Tools::convertPrice($payment->amount, $payment->id_currency, false), 2);
                    }

                    $order->save();

                    $payment->conversion_rate = 1;
                    $payment->save();
                    Db::getInstance()->insert(
                        'order_invoice_payment',
                        [
                            'id_order_invoice' => (int) $invoice->id,
                            'id_order_payment' => (int) $payment->id,
                            'id_order'         => (int) $order->id,
                        ]
                    );
                }

            }

        }

        // updates delivery date even if it was already set by another state change

        if ($newOs->delivery) {
            $order->setDelivery();
        }

        // executes hook
        Hook::exec('actionOrderStatusPostUpdate', ['newOrderStatus' => $newOs, 'id_order' => (int) $order->id], null, false, true, false, $order->id_shop);

       
    }

    public static function getLastOrderState($idOrder) {

        Tools::displayAsDeprecated();
        $idOrderState = (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_order_state`')
                ->from('order_history')
                ->where('`id_order` = ' . (int) $idOrder)
                ->orderBy('`date_add` DESC, `id_order_history` DESC')
        );

        // returns false if there is no state

        if (!$idOrderState) {
            return false;
        }

        // else, returns an OrderState object if it can be loaded
        $orderState = new OrderState($idOrderState, Configuration::get('EPH_LANG_DEFAULT'));

        if (Validate::isLoadedObject($orderState)) {
            return $orderState;
        }

        return false;
    }

    
    public function addWithemail($autodate = true, $templateVars = false, Context $context = null) {

        $order = new CustomerPieces($this->id_customer_piece);

        if (!$this->add($autodate)) {
            return false;
        }

        if (!$this->sendEmail($order, $templateVars)) {
            return false;
        }

        return true;
    }

    
    public function sendEmail($order, $templateVars = false) {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('osl.`template`, c.`lastname`, c.`firstname`, osl.`name` AS osname, c.`email`, os.`module_name`, os.`id_customer_piece_state`, os.`pdf_invoice`, os.`pdf_delivery`')
                ->from('customer_piece_history', 'oh')
                ->leftJoin('customer_pieces', 'o', 'oh.`id_customer_piece` = o.`id_customer_piece`')
                ->leftJoin('customer', 'c', 'o.`id_customer` = c.`id_customer`')
                ->leftJoin('customer_piece_state', 'os', 'oh.`id_customer_piece_state` = os.`id_customer_piece_state`')
                ->leftJoin('customer_piece_state_lang', 'osl', 'os.`id_customer_piece_state` = osl.`id_customer_piece_state` AND osl.`id_lang` = o.`id_lang`')
                ->where('oh.`id_customer_piece_history` = ' . (int) $this->id . ' AND os.`send_email` = 1')
        );

        if (isset($result['template']) && Validate::isEmail($result['email'])) {

           
            $tpl = Context::getContext()->smarty->createTemplate(_EPH_MAIL_DIR_ . '/' . $result['template'] . '.tpl');

            $topic = $result['osname'];

            $data = [
                'lastname'   => $result['lastname'],
                'firstname'  => $result['firstname'],
                'id_order'   => (int) $this->id_customer_piece,
                'order_name' => $order->prefix . $order->piece_number,
            ];

            if ($result['module_name']) {
                $module = Module::getInstanceByName($result['module_name']);

                if (Validate::isLoadedObject($module) && isset($module->extra_mail_vars) && is_array($module->extra_mail_vars)) {
                    $data = array_merge($data, $module->extra_mail_vars);
                }

            }

            if ($templateVars) {
                $data = array_merge($data, $templateVars);
            }

            $data['total_paid'] = Tools::displayPrice((float) $order->total_paid, new Currency((int) $order->id_currency), false);

            if (Validate::isLoadedObject($order)) {
                // Attach invoice and / or delivery-slip if they exists and status is set to attach them

                if (($result['pdf_invoice'] || $result['pdf_delivery'])) {
                    $context = Context::getContext();
                    
                    $fileName = $order->printPdf();
                    $fileAttachement[] = [
                        'content' => chunk_split(base64_encode(file_get_contents(_EPH_INVOICE_DIR_ . $fileName))),
                        'name'    => $fileName,
                    ];

                } else {
                    $fileAttachement = null;
                }

                foreach ($data as $key => $value) {
                    $tpl->assign($key, $value);
                }

                $postfields = [
                    'sender'      => [
                        'name'  => "Service Commerciale " . Configuration::get('EPH_SHOP_NAME'),
                        'email' => 'no-reply@' . Configuration::get('EPH_SHOP_URL'),
                    ],
                    'to'          => [
                        [
                            'name'  => $result['firstname'] . ' ' . $result['lastname'],
                            'email' => $result['email'],
                        ],
                    ],

                    'subject'     => $topic,
                    "htmlContent" => $tpl->fetch(),
                    'attachment'  => $fileAttachement,
                ];
                Tools::sendEmail($postfields);

            }

            
        }

        return true;
    }

  
    public function add($autoDate = true, $nullValues = false) {

        if (!parent::add($autoDate)) {
            return false;
        }

        $order = new CustomerPieces((int) $this->id_customer_piece);
        $order->current_state = $this->id_customer_piece_state;
        $order->update();

        Hook::exec('actionOrderHistoryAddAfter', ['customer_piece_history' => $this], null, false, true, false, $order->id_shop);

        return true;
    }

    public function isValidated() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(oh.`id_customer_piece_history` AS `nb`')
                ->from('customer_piece_state', 'os')
                ->leftJoin('customer_piece_history', 'oh', 'os.`id_customer_piece_state` = oh.`id_customer_piece_state`')
                ->where('oh.`id_customer_piece` = ' . (int) $this->id_customer_piece)
                ->where('od.`logable` = 1')
        );
    }

    public function addWs() {

        $sendemail = (bool) Tools::getValue('sendemail', false);
        $this->changeIdOrderState($this->id_customer_piece_state, $this->id_customer_piece);

        if ($sendemail) {
            //Mail::Send requires link object on context and is not set when getting here
            $context = Context::getContext();

            if ($context->link == null) {
                $protocolLink = (Tools::usingSecureMode() && Configuration::get('EPH_SSL_ENABLED')) ? 'https://' : 'http://';
                $protocolContent = (Tools::usingSecureMode() && Configuration::get('EPH_SSL_ENABLED')) ? 'https://' : 'http://';
                $context->link = new Link($protocolLink, $protocolContent);
            }

            return $this->addWithemail();
        } else {
            return $this->add();
        }

    }

}
