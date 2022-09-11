<?php

/**
 * Class ContactControllerCore
 *
 * @since 1.8.1.0
 */
class ContactControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'contact';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    public function ajaxProcessSendContactForm() {

		$errors = [];
		
        $idContact = (int) Tools::getValue('id_contact');
		
		$message = Tools::getValue('message');
		$from = Tools::getValue('from');
		$contact = new Contact($idContact, $this->context->language->id);
		
        if (!Validate::isEmail($from)) {
            $errors[] = $this->l('Invalid email address.');
        } else
        if (!$message) {
            $errors[] = $this->l('The message cannot be blank.');
        } else
        if (!(Validate::isLoadedObject($contact))) {
            $errors[] = $this->l('Please select a subject from the list provided. ');
        }

        if (!count($errors)) {
            $customer = $this->context->customer;

            if (!$customer->id) {
                $customer->getByEmail($from);
            }

            $idOrder = (int) Tools::getValue('id_order');
            $fileAttachment = [];
			

            if (!((
                ($idCustomerThread = (int) Tools::getValue('id_customer_thread'))
                && (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                    (new DbQuery())
                    ->select('ct.`id_customer_thread`')
                    ->from('customer_thread', 'ct')
                    ->where('ct.`id_customer_thread` = ' . (int) $idCustomerThread)
                    ->where('ct.`id_shop` = ' . (int) $this->context->shop->id)
                    ->where('ct.`token` = \'' . pSQL(Tools::getValue('token')) . '\'')
                )
            ) || (
                $idCustomerThread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($from, $idOrder)
            ))
            ) {
                $fields = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('ct.`id_customer_thread`, ct.`id_contact`, ct.`id_customer`, ct.`id_order`, ct.`id_product`, ct.`email`')
                        ->from('customer_thread', 'ct')
                        ->where('ct.`email` = \'' . pSQL($from) . '\'')
                        ->where('ct.`id_shop` = ' . (int) $this->context->shop->id)
                        ->where('(' . ($customer->id ? 'id_customer = ' . (int) $customer->id . ' OR ' : '') . ' id_order = ' . (int) $idOrder . ')')
                );
                $score = 0;

                foreach ($fields as $key => $row) {
                    $tmp = 0;

                    if ((int) $row['id_customer'] && $row['id_customer'] != $customer->id && $row['email'] != $from) {
                        continue;
                    }

                    if ($row['id_order'] != 0 && $idOrder != $row['id_order']) {
                        continue;
                    }

                    if ($row['email'] == $from) {
                        $tmp += 4;
                    }

                    if ($row['id_contact'] == $idContact) {
                        $tmp++;
                    }

                    if (Tools::getValue('id_product') != 0 && $row['id_product'] == Tools::getValue('id_product')) {
                        $tmp += 2;
                    }

                    if ($tmp >= 5 && $tmp >= $score) {
                        $score = $tmp;
                        $idCustomerThread = $row['id_customer_thread'];
                    }

                }

            }

            $oldMessage = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('cm.`message`')
                    ->from('customer_message', 'cm')
                    ->leftJoin('customer_thread', 'cc', 'cm.`id_customer_thread` = cc.`id_customer_thread`')
                    ->where('cc.`id_customer_thread` = ' . (int) $idCustomerThread)
                    ->where('cc.`id_shop` = ' . (int) $this->context->shop->id)
                    ->orderBy('cm.`date_add` DESC')
            );

            
            if ($contact->customer_service) {

                if ((int) $idCustomerThread) {
                    $ct = new CustomerThread($idCustomerThread);
                    $ct->status = 'open';
                    $ct->id_lang = (int) $this->context->language->id;
                    $ct->id_contact = (int) $idContact;
                    $ct->id_order = (int) $idOrder;

                    if ($idProduct = (int) Tools::getValue('id_product')) {
                        $ct->id_product = $idProduct;
                    }

                    $ct->update();
                } else {
                    $ct = new CustomerThread();

                    if (isset($customer->id)) {
                        $ct->id_customer = (int) $customer->id;
                    }

                    $ct->id_shop = (int) $this->context->shop->id;
                    $ct->id_order = (int) $idOrder;

                    if ($idProduct = (int) Tools::getValue('id_product')) {
                        $ct->id_product = $idProduct;
                    }

                    $ct->id_contact = (int) $idContact;
                    $ct->id_lang = (int) $this->context->language->id;
                    $ct->email = $from;
                    $ct->status = 'open';
                    $ct->token = Tools::passwdGen(12);
                    $ct->add();
                }
				

                if ($ct->id) {
					$pdfUploader = new HelperUploader('fileUpload');
            		$pdfUploader->setAcceptTypes(['pdf', '.jpg', 'jpeg', 'gif', 'png', 'jpg', '.docx', '.zip']);
            		$files = $pdfUploader->process();

            		if (is_array($files) && count($files)) {

                		foreach ($files as $file) {
                    		$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    		$destinationFile = _EPH_UPLOAD_DIR_ . 'message_' . $ct->id . '.' . $ext;
                    		copy($file['save_path'], $destinationFile);
						}

                		$fileAttachement[] = [
                    		'content' => chunk_split(base64_encode(file_get_contents($destinationFile))),
                    		'name'    => 'message_' . $ct->id . '.' . $ext,
                		];
            		}
                    $cm = new CustomerMessage();
                    $cm->id_customer_thread = $ct->id;
                    $cm->message = $message;

                    if (isset($fileAttachement['name']) && !empty($fileAttachement['name'])) {
                        $cm->file_name = $fileAttachement['name'];
                    }
					foreach($fileAttachement as $attachment) {
						if (isset($attachment['name'])) {
                    		$cm->file_name = $attachment['name'];
                		}
					}

                    $cm->ip_address = (int) ip2long(Tools::getRemoteAddr());
                    $cm->user_agent = $_SERVER['HTTP_USER_AGENT'];

                    if (!$cm->add()) {
                        $errors[] = $this->l('An error occurred while sending the message.');
                    }

                } else {
                    $errors[] = $this->l('An error occurred while sending the message.');
                }

            }

            

            if (!count($errors)) {
				
                $order_name = '-';
                $id_order = '';
                $product_name = '';
                $attached_file = '';
                $idProduct = (int) Tools::getValue('id_product');

                if (isset($ct) && Validate::isLoadedObject($ct) && $ct->id_order) {
                    $order = new CustomerPieces((int) $ct->id_order);
                    $id_order = (int) $order->id;
                    $order_name = $order->getPrefix() . $order->piece_number;
                }

                if ($idProduct) {
                    $product = new Product((int) $idProduct);

                    if (Validate::isLoadedObject($product) && isset($product->name[$this->context->language->id])) {
                        $product_name = $product->name[$this->context->language->id];
                    }

                }
				foreach($fileAttachement as $attachment) {
					if (isset($attachment['name'])) {
                    	$attached_file = $attachment['name'];
                	}
				}
                

                if (empty($contact->email)) {
					
					
                    $tpl = $this->context->smarty->createTemplate(_EPH_MAIL_DIR_ . '/contact_form.tpl');
                    $tpl->assign([
                        'order_name'    => $order_name,
                        'id_order'      => $id_order,
                        'product_name'  => $product_name,
                        'message'       => Tools::nl2br(stripslashes($message)),
                        'email'         => $from,
                        'attached_file' => $attached_file,
                    ]);
                    $subject = ((isset($ct) && Validate::isLoadedObject($ct)) ? sprintf($this->l('Your message has been correctly sent #ct%1$s #tc%2$s'), $ct->id, $ct->token) : $this->l('Your message has been correctly sent'));
                    $postfields = [
                        'sender'      => [
                            'name'  => "Service Administratif " . Configuration::get('EPH_SHOP_NAME'),
                            'email' => Configuration::get('EPH_SHOP_EMAIL'),
                        ],
                        'to'          => [
                            [
                                'name'  => Tools::getValue("name"),
                                'email' => $from,
                            ],
                        ],
                        'subject'     => $subject,
                        "htmlContent" => $tpl->fetch(),
                        'attachment'  => $fileAttachement,
                    ];

                } else {
					
                    $tpl = $this->context->smarty->createTemplate(_EPH_MAIL_DIR_ . '/contact.tpl');
                    $tpl->assign([
                        'order_name'    => $order_name,
                        'id_order'      => $id_order,
                        'product_name'  => $product_name,
                        'message'       => Tools::nl2br(stripslashes($message)),
                        'email'         => $from,
                        'attached_file' => $attached_file,
                    ]);
                    $postfields = [
                        'sender'      => [
                            'name'  => "Service Administratif " . Configuration::get('EPH_SHOP_NAME'),
                            'email' => Configuration::get('EPH_SHOP_EMAIL'),
                        ],
                        'to'          => [
                            [
                                'name'  => $contact->name,
                                'email' => $contact->email,
                            ],
                        ],
                        'subject'     => $this->l('Message from contact form'),
                        "htmlContent" => $tpl->fetch(),
                        'attachment'  => $fileAttachement,
                    ];
                }
				
                $result = Tools::sendEmail($postfields);
                $return = [
                    'success' => true,
                    'message' => $this->l('Votre message a été envoyé avec succès'),
                ];

            } else {
				$return = [
            		'success' => false,
                	'message' => implode(PHP_EOL, $errors),
            	];
			}

        } else {
			fwrite($file,print_r($errors, true));
			$return = [
            	'success' => false,
                'message' => implode(PHP_EOL, $errors),
            ];
		}
		
		die(Tools::jsonEncode($return));

    }

    /**
     * Get Order ID
     *
     * @return int Order ID
     *
     * @since 1.8.1.0
     */
    protected function getOrder() {

        $idOrder = false;
        $orders = CustomerPieces::getByReference(Tools::getValue('id_order'));

        if ($orders) {

            foreach ($orders as $order) {
                $idOrder = (int) $order->id;
                break;
            }

        }

        return (int) $idOrder;
    }

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function setMedia() {

        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_ . 'contact-form.css');
    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        parent::initContent();

        $this->assignOrderList();

        $email = Tools::convertEmailToIdn(Tools::safeOutput(
            Tools::getValue(
                'from',
                ((isset($this->context->cookie) && isset($this->context->cookie->email) && Validate::isEmail($this->context->cookie->email)) ? $this->context->cookie->email : '')
            )
        ));
        $this->context->smarty->assign(
            [
                'errors'          => $this->errors,
                'email'           => $email,
                'fileupload'      => Configuration::get('EPH_CUSTOMER_SERVICE_FILE_UPLOAD'),
                'max_upload_size' => (int) Tools::getMaxUploadSize(),
            ]
        );

        if (($idCustomerThread = (int) Tools::getValue('id_customer_thread')) && $token = Tools::getValue('token')) {
            $customerThread = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('cm.*')
                    ->from('customer_thread', 'cm')
                    ->where('cm.`id_customer_thread` = ' . (int) $idCustomerThread)
                    ->where('cm.`id_shop` = ' . (int) $this->context->shop->id)
                    ->where('cm.`token` = \'' . pSQL($token) . '\'')
            );

            $order = new CustomerPieces((int) $customerThread['id_order']);

            if (Validate::isLoadedObject($order)) {
                $customerThread['reference'] = $order->getUniqReference();
            }

            $this->context->smarty->assign('customerThread', $customerThread);
        }

        $this->context->smarty->assign(
            [
                'contacts' => Contact::getContacts($this->context->language->id),
                'message'  => html_entity_decode(Tools::getValue('message')),
            ]
        );

        $this->setTemplate(_EPH_THEME_DIR_ . 'contact-form.tpl');
    }

    /**
     * Assign template vars related to order list and product list ordered by the customer
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    protected function assignOrderList() {

        if ($this->context->customer->isLogged()) {
            $this->context->smarty->assign('isLogged', 1);

            $products = [];
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('`id_customer_piece`')
                    ->from('customer_pieces')
                    ->where('`id_customer` = ' . (int) $this->context->customer->id)
                    ->orderBy('`date_add`')
            );

            $orders = [];

            foreach ($result as $row) {
                $order = new CustomerPieces($row['id_customer_piece']);
                $date = explode(' ', $order->date_add);
                $tmp = $order->getProducts();

                foreach ($tmp as $key => $val) {
                    $products[$row['id_customer_piece']][$val['id_product']] = ['value' => $val['id_product'], 'label' => $val['product_name']];
                }

                $orders[] = ['value' => $order->id, 'label' => $order->getPrefix() . $order->piece_number . ' - ' . Tools::displayDate($date[0], null), 'selected' => (int) $this->getOrder() == $order->id];
            }

            $this->context->smarty->assign('orderList', $orders);
            $this->context->smarty->assign('orderedProductList', $products);
        }

    }

}
