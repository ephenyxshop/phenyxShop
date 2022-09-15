<?php

/**
 * Class AdminCartsControllerCore
 *
 * @since 1.8.1.0
 */
class AdminCartsControllerCore extends AdminController {

	/**
	 * AdminCartsControllerCore constructor.
	 *
	 * @since 1.8.1.0
	 */
	public function __construct() {

		$this->bootstrap = true;
		$this->table = 'cart';
		$this->className = 'Cart';
		$this->publicName = $this->l('Les paniers clients');
		$this->lang = false;

		$this->shopLinkType = 'shop';

		parent::__construct();
		EmployeeConfiguration::updateValue('EXPERT_CARTS_SCRIPT', $this->generateParaGridScript(true));
		$this->paragridScript = EmployeeConfiguration::get('EXPERT_CARTS_SCRIPT');

		if (empty($this->paragridScript)) {
			EmployeeConfiguration::updateValue('EXPERT_CARTS_SCRIPT', $this->generateParaGridScript(true));
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_CARTS_SCRIPT');
		}

		EmployeeConfiguration::updateValue('EXPERT_CARTS_FIELDS', Tools::jsonEncode($this->getCartFields()));
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CARTS_FIELDS'), true);

		if (empty($this->configurationField)) {
			EmployeeConfiguration::updateValue('EXPERT_CARTS_FIELDS', Tools::jsonEncode($this->getCartFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_CARTS_FIELDS'), true);
		}

		//$this->controllerRequest = $this->getCartRequest();

		$this->extracss = $this->pushCSS([
			_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/cart.css',

		]);
	}

	public function initContent() {

		$this->displayGrid = true;
		$this->paramGridObj = 'obj' . $this->className;
		$this->paramGridVar = 'grid' . $this->className;
		$this->paramGridId = 'grid_' . $this->controller_name;

		$this->TitleBar = $this->l('Carts management');

		$lostcarts = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('*')
				->from('cart')
				->where('`id_customer` = 0')
				->where('date_add < "' . pSQL(date('Y-m-d', strtotime('-1 month'))) . '"')
		);

		$this->context->smarty->assign([
			'manageHeaderFields' => true,
			'customHeaderFields' => $this->manageFieldsVisibility($this->configurationField),
			'allowExport'        => true,
			'fieldsExport'       => $this->getExportFields(),
			'controller'         => Tools::getValue('controller'),
			'tabScript'          => $this->generateTabScript(Tools::getValue('controller')),
			'gridId'             => 'grid_AdminCarts',
			'tableName'          => $this->table,
			'className'          => $this->className,
			'linkController'     => $this->context->link->getAdminLink($this->controller_name),
			'AjaxLink'           => $this->context->link->getAdminLink($this->controller_name),
			'paragridScript'     => $this->generateParaGridScript(),
			'titleBar'           => $this->TitleBar,
			'bo_imgdir'          => __EPH_BASE_URI__ . $this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
			'idController'       => '',
			'lostcarts'          => count($lostcarts),
		]);

		parent::initContent();
	}

	public function generateParaGridScript($regenerate = false) {

		$paragrid = new ParamGrid($this->className, $this->controller_name, $this->table, $this->identifier);

		$paragrid->height = '680';
		$paragrid->showNumberCell = 0;
		$paragrid->pageModel = [
			'type'       => '\'local\'',
			'rPP'        => 40,
			'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
		];
		$paragrid->create = 'function (evt, ui) {
			buildHeadingAction(\'' . 'grid_' . $this->controller_name . '\', \'' . $this->controller_name . '\');
        }';
		$paragrid->rowInit = 'function (ui) {
			return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';

		$paragrid->toolbar = [
			'items' => [

				[
					'type'     => '\'button\'',
					'label'    => '\'' . $this->l('Nettoyer les paniers abandonnés') . '\'',
					'cls'      => '\'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'listener' => 'function () {' . PHP_EOL . '
                           cleanLooseCart();
						}',
				],

			],
		];
		$paragrid->selectionModelType = 'row';
		$paragrid->filterModel = [
			'on'          => true,
			'mode'        => '\'OR\'',
			'header'      => true,
			'menuIcon'    => 0,
			'gridOptions' => [
				'numberCell' => [
					'show' => 0,
				],
				'width'      => '\'flex\'',
				'flex'       => [
					'one' => true,
				],
			],
		];

		$paragrid->showTitle = 1;
		$paragrid->title = '\'' . $this->l('Management of') . ' ' . $this->publicName . '\'';
		$paragrid->fillHandle = '\'all\'';
		$paragrid->contextMenu = [
			'#grid_' . $this->controller_name => [
				'selector'  => '\'.pq-body-outer .pq-grid-row\'',
				'animation' => [
					'duration' => 250,
					'show'     => '\'fadeIn\'',
					'hide'     => '\'fadeOut\'',
				],
				'build'     => 'function($triggerElement, e){

                var rowIndex = $($triggerElement).attr("data-rowIndx");
                var rowData = ' . 'grid' . $this->className . '.getRowData( {rowIndx: rowIndex} );
                selected = selgridCart.getSelection().length;
                var dataLenght = gridCart.option(\'dataModel.data\').length;
                return {
                    callback: function(){},
                    items: {
                         "view": {
                            name : \'' . $this->l('View the selected cart') . '\',
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                var datalink = rowData.viewLink;
                                viewCart(rowData.id_cart);
                            }
                        },
                        "transfer": {
                            name : \'' . $this->l('Convert the selected cart into order') . '\',
                            icon: "copy",
							visible: function(key, opt){
							 	var istransfer = rowData.badge_danger;
								var totalCart = parseFloat(rowData.totalCart);
                                if(istransfer == 1 && totalCart > 0) {
                                    return true;
                                }
                                return false;
                            },
                            callback: function(itemKey, opt, e) {

                            }
                        },
                        "sep1": "---------",

                        "delete": {
                            name: \'' . $this->l('Delete the selected Cart') . '\',
                            icon: "delete",
                            visible: function(key, opt){
							 	var istransfer = rowData.badge_success;
                                if(istransfer == 1) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {

                            }
                        },

                    },
                };
            }',
			]];

		$paragrid->gridExtraFunction = $gridExtraFunction;

		$option = $paragrid->generateParaGridOption();
		$script = $paragrid->generateParagridScript();

		if ($regenerate) {
			return $script;
		}

		$this->paragridScript = $script;
		return '<script type="text/javascript">' . PHP_EOL . $this->paragridScript . PHP_EOL . '</script>';
	}

	public function generateParaGridOption() {

		return '';

	}

	public function getCartRequest() {

		$carts = Db::getInstance()->executeS(
			(new DbQuery())
				->select('a.`id_cart`, a.`date_add` AS `date_add`, a.`date_upd` AS `date_upd`, cu.`sign`, CONCAT(c.`firstname`, \' \', c.`lastname`) `customer`, c.customer_code, ca.name carrier, IF (IFNULL(o.id_order, \'<span class="badge badge-danger">' . $this->l('Non ordered') . '</span>\') = \'<span class="badge badge-danger">' . $this->l('Non ordered') . '</span>\',  IF(TIME_TO_SEC(TIMEDIFF(\'' . pSQL(date('Y-m-d H:i:00', time())) . '\', a.`date_add`)) > 86400, \'<span class="badge badge-danger">' . $this->l('Abandoned cart') . '</span>\', \'<span class="badge badge-danger">' . $this->l('Non ordered') . '</span>\'), o.id_order) AS status, a.`date_upd`, IF(o.id_order, 1, 0) badge_success, IF(o.id_order, 0, 1) badge_danger,
			case when o.id_order > 0 then CONCAT(\'<span class="badge badge-success">\', TRUNCATE(o.total_paid,2), \' \', cu.`sign`, \'</span>\') else 0 end as `total`,
			case when o.id_order > 0 then o.total_paid else 0 end as `totalCart`,
			case when co.id_guest = 1 then \'<div class="p-active"></div>\' else \'<div class="p-inactive"></div>\' end as id_guest')
				->from('cart', 'a')
				->leftJoin('customer', 'c', 'c.`id_customer` = a.`id_customer`')
				->leftJoin('currency', 'cu', 'cu.`id_currency` = a.`id_currency`')
				->leftJoin('carrier', 'ca', 'ca.`id_carrier` = a.`id_carrier`')
				->leftJoin('orders', 'o', 'o.`id_cart` = a.`id_cart`')
				->leftJoin('connections', 'co', '(a.id_guest = co.id_guest AND TIME_TO_SEC(TIMEDIFF(\'' . pSQL(date('Y-m-d H:i:00', time())) . '\', co.`date_add`)) < 1800)')
				->orderBy('a.`id_cart` DESC')
		);
		$cartLink = $this->context->link->getAdminLink($this->controller_name);

		foreach ($carts as &$cart) {

			if ($cart['badge_danger']) {
				$cart['totalCart'] = $this->getOrderTotalUsingTaxCalculationMethod($cart['id_cart']);
				$cart['total'] = '<span>' . number_format($cart['totalCart'], 2) . ' ' . $cart['sign'] . '</span>';
			}

			$cart['viewLink'] = $cartLink . '&id_cart=' . $cart['id_cart'] . '&viewcart';
			$cart['deleteLink'] = $cartLink . '&id_cart=' . $cart['id_cart'] . '&id_object=' . $cart['id_cart'] . '&deletecart&action=deleteObject&ajax=true';

		}

		return $carts;

	}

	public function ajaxProcessgetCartRequest() {

		die(Tools::jsonEncode($this->getCartRequest()));

	}

	public function getCartFields() {

		return [

			[

				'dataIndx'   => 'viewLink',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[

				'dataIndx'   => 'deleteLink',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[

				'dataIndx'   => 'totalCart',
				'dataType'   => 'float',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[
				'title'    => $this->l('ID'),
				'maxWidth' => 70,
				'dataIndx' => 'id_cart',
				'dataType' => 'integer',
				'editable' => false,
				'align'    => 'center',
				'valign'   => 'center',
			],
			[
				'dataIndx'   => 'badge_success',
				'dataType'   => 'string',
				'hidden'     => true,
				'hiddenable' => 'no',
				'filter'     => [
					'crules' => [['condition' => "equal"]],
				],
			],
			[
				'title'    => $this->l('Order ID'),
				'dataIndx' => 'status',
				'align'    => 'center',
				'valign'   => 'center',
				'editable' => false,
				'dataType' => 'html',
				'filter'   => [
					'attr'   => "id=\"badgeSelector\", placeholder=" . $this->l('--Select--') . " readonly",
					'crules' => [['condition' => "equal"]],
				],
			],
			[
				'title'    => $this->l('Code client'),
				'width'    => 50,
				'dataIndx' => 'customer_code',
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'string',
				'editable' => false,
				'filter'   => [
					'crules' => [['condition' => "contain"]],
				],

			],
			[
				'title'    => $this->l('Customer'),
				'width'    => 50,
				'dataIndx' => 'customer',
				'align'    => 'center',
				'valign'   => 'center',
				'dataType' => 'string',
				'editable' => false,

			],
			[
				'title'    => $this->l('Total'),
				'dataIndx' => 'total',
				'dataType' => 'html',
				'align'    => 'right',
				'valign'   => 'center',
				'editable' => false,
			],
			[
				'title'    => $this->l('Carrier'),
				'dataIndx' => 'carrier',
				'dataType' => 'string',
				'editable' => false,
				'align'    => 'center',
				'valign'   => 'center',
			],
			[

				'title'    => $this->l('Date created'),
				'dataIndx' => 'date_add',
				'cls'      => 'rangeDate',
				'align'    => 'center',
				'dataType' => 'date',
				'format'   => 'dd/mm/yy',
				'editable' => false,

			],
			[

				'title'    => $this->l('Date modified'),
				'dataIndx' => 'date_upd',
				'cls'      => 'rangeDate',
				'align'    => 'center',
				'dataType' => 'date',
				'format'   => 'dd/mm/yy',
				'editable' => false,

			],

		];

	}

	public function ajaxProcessgetCartFields() {

		die(Tools::jsonEncode($this->getCartFields()));
	}

	public function ajaxProcessDeleteLostCarts() {

		$lostcarts = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('*')
				->from('cart')
				->where('`id_customer` = 0')
				->where('date_add < \'' . pSQL(date('Y-m-d', strtotime('-1 month'))) . '\'')
		);

		foreach ($lostcarts as $lostcart) {

			$cart = new Cart($lostcart["id_cart"]);
			$cart->delete();
		}

		$query = '
		DELETE FROM `' . _DB_PREFIX_ . 'cart`
		WHERE id_cart NOT IN (SELECT id_cart FROM `' . _DB_PREFIX_ . 'orders`)
		AND date_add < "' . pSQL(date('Y-m-d', strtotime('-1 month'))) . '"';

		if (Db::getInstance()->Execute($query)) {

			if ($affected_rows = Db::getInstance()->Affected_Rows()) {
				$logs[$query] = $affected_rows;
			}

		}

		$result = [
			'success' => true,
			'message' => $this->l('Les panier abandonns depuis plus d‘un mois ont été supprimé'),
		];
		die(Tools::jsonEncode($result));
	}

	/**
	 * @param int $idCart
	 *
	 * @return string
	 *
	 * @since 1.8.1.0
	 */
	public static function getOrderTotalUsingTaxCalculationMethod($idCart) {

		$context = Context::getContext();
		$cart = new Cart($idCart);

		if (Validate::isLoadedObject($cart)) {
			$context->cart = $cart;
			$context->currency = new Currency((int) $cart->id_currency);
			$context->customer = new Customer((int) $cart->id_customer);

			return $context->cart->getOrderTotal(TRUE, Cart::BOTH_WITHOUT_SHIPPING);
		}

		return '0.00';
	}

	/**
	 * @param $echo
	 * @param $tr
	 *
	 * @return string
	 *
	 * @since 1.8.1.0
	 */
	public static function replaceZeroByShopName($echo, $tr) {

		return ($echo == '0' ? Carrier::getCarrierNameFromShopName() : $echo);
	}

	public function ajaxProcessViewCart() {

		$idCart = Tools::getValue('idCart');
		$this->identifier = 'id_cart';
		$_GET['id_cart'] = $idCart;
		$_GET['viewcart'] = "";

		$html = $this->renderView();
		$result = [
			'success' => true,
			'html'    => $html,
		];

		die(Tools::jsonEncode($result));
	}

	/**
	 * @return string
	 *
	 * @since 1.8.1.0
	 */
	public function renderView() {

		$this->displayGrid = false;
		/** @var Cart $cart */

		if (!($cart = $this->loadObject(true))) {
			return;
		}

		$customer = new Customer($cart->id_customer);
		$currency = new Currency($cart->id_currency);
		$this->context->cart = $cart;
		$this->context->currency = $currency;
		$this->context->customer = $customer;
		$this->toolbar_title = sprintf($this->l('Cart #%06d'), $this->context->cart->id);
		$products = $cart->getProducts();
		$customizedDatas = Product::getAllCustomizedDatas((int) $cart->id);
		Product::addCustomizationPrice($products, $customizedDatas);
		$summary = $cart->getSummaryDetails();

		/* Display order information */
		$idOrder = (int) CustomerPieces::getOrderByCartId($cart->id);
		$order = new CustomerPieces($idOrder);

		if (Validate::isLoadedObject($order)) {
			$taxCalculationMethod = $order->getTaxCalculationMethod();
			$idCompany = (int) $order->id_shop;
		} else {
			$idCompany = (int) $cart->id_shop;
			$taxCalculationMethod = Group::getPriceDisplayMethod(Group::getCurrent()->id);
		}

		if ($taxCalculationMethod == EPH_TAX_EXC) {
			$totalProducts = $summary['total_products'];
			$totalDiscounts = $summary['total_discounts_tax_exc'];
			$totalWrapping = $summary['total_wrapping_tax_exc'];
			$totalPrice = $summary['total_price_without_tax'];
			$totalShipping = $summary['total_shipping_tax_exc'];
		} else {
			$totalProducts = $summary['total_products_wt'];
			$totalDiscounts = $summary['total_discounts'];
			$totalWrapping = $summary['total_wrapping'];
			$totalPrice = $summary['total_price'];
			$totalShipping = $summary['total_shipping'];
		}

		foreach ($products as $k => &$product) {

			if ($taxCalculationMethod == EPH_TAX_EXC) {
				$product['product_price'] = $product['price'];
				$product['product_total'] = $product['total'];
			} else {
				$product['product_price'] = $product['price_wt'];
				$product['product_total'] = $product['total_wt'];
			}

			$image = [];

			if (isset($product['id_product_attribute']) && (int) $product['id_product_attribute']) {
				$image = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
					(new DbQuery())
						->select('`id_image`')
						->from('product_attribute_image')
						->where('`id_product_attribute` = ' . (int) $product['id_product_attribute'])
				);
			}

			if (!isset($image['id_image'])) {
				$image = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
					(new DbQuery())
						->select('`id_image`')
						->from('image')
						->where('`id_product` = ' . (int) $product['id_product'])
						->where('`cover` = 1')
				);
			}

			$product['qty_in_stock'] = StockAvailable::getQuantityAvailableByProduct($product['id_product'], isset($product['id_product_attribute']) ? $product['id_product_attribute'] : null);

			$imageProduct = new Image($image['id_image']);
			$product['image'] = (isset($image['id_image']) ? ImageManager::thumbnail(_EPH_IMG_DIR_ . 'p/' . $imageProduct->getExistingImgPath() . '.jpg', 'product_mini_' . (int) $product['id_product'] . (isset($product['id_product_attribute']) ? '_' . (int) $product['id_product_attribute'] : '') . '.jpg', 45, 'jpg') : '--');
		}

		$helper = new HelperKpi();
		$helper->id = 'box-kpi-cart';
		$helper->icon = 'icon-shopping-cart';
		$helper->color = 'color1';
		$helper->title = $this->l('Total Cart', null, null, false);
		$helper->subtitle = sprintf($this->l('Cart #%06d', null, null, false), $cart->id);
		$helper->value = Tools::displayPrice($totalPrice, $currency);
		$kpi = $helper->generate();

		$this->tpl_view_vars = [
			'kpi'                    => $kpi,
			'products'               => $products,
			'discounts'              => $cart->getCartRules(),
			'order'                  => $order,
			'cart'                   => $cart,
			'currency'               => $currency,
			'customer'               => $customer,
			'customer_stats'         => $customer->getStats(),
			'total_products'         => $totalProducts,
			'total_discounts'        => $totalDiscounts,
			'total_wrapping'         => $totalWrapping,
			'total_price'            => $totalPrice,
			'total_shipping'         => $totalShipping,
			'customized_datas'       => $customizedDatas,
			'tax_calculation_method' => $taxCalculationMethod,
			'AjaxBackLink'           => $this->context->link->getAdminLink($this->controller_name),
		];

		return parent::renderView();
	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxPreProcess() {

		if ($this->tabAccess['edit'] === '1') {
			$idCustomer = (int) Tools::getValue('id_customer');
			$customer = new Customer((int) $idCustomer);
			$this->context->customer = $customer;
			$idCart = (int) Tools::getValue('id_cart');

			if (!$idCart) {
				$idCart = $customer->getLastCart(false);
			}

			$this->context->cart = new Cart((int) $idCart);

			if (!$this->context->cart->id) {
				$this->context->cart->recyclable = 0;
				$this->context->cart->gift = 0;
			}

			if (!$this->context->cart->id_customer) {
				$this->context->cart->id_customer = $idCustomer;
			}

			if (Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists()) {
				return;
			}

			if (!$this->context->cart->secure_key) {
				$this->context->cart->secure_key = $this->context->customer->secure_key;
			}

			if (!$this->context->cart->id_shop) {
				$this->context->cart->id_shop = (int) $this->context->company->id;
			}

			if (!$this->context->cart->id_lang) {
				$this->context->cart->id_lang = (($idLang = (int) Tools::getValue('id_lang')) ? $idLang : Configuration::get('EPH_LANG_DEFAULT'));
			}

			if (!$this->context->cart->id_currency) {
				$this->context->cart->id_currency = (($idCurrency = (int) Tools::getValue('id_currency')) ? $idCurrency : Configuration::get('EPH_CURRENCY_DEFAULT'));
			}

			$addresses = $customer->getAddresses((int) $this->context->cart->id_lang);
			$idAddressDelivery = (int) Tools::getValue('id_address_delivery');
			$idAddressInvoice = (int) Tools::getValue('id_address_delivery');

			if (!$this->context->cart->id_address_invoice && isset($addresses[0])) {
				$this->context->cart->id_address_invoice = (int) $addresses[0]['id_address'];
			} else
			if ($idAddressInvoice) {
				$this->context->cart->id_address_invoice = (int) $idAddressInvoice;
			}

			if (!$this->context->cart->id_address_delivery && isset($addresses[0])) {
				$this->context->cart->id_address_delivery = $addresses[0]['id_address'];
			} else
			if ($idAddressDelivery) {
				$this->context->cart->id_address_delivery = (int) $idAddressDelivery;
			}

			$this->context->cart->setNoMultishipping();
			$this->context->cart->save();
			$currency = new Currency((int) $this->context->cart->id_currency);
			$this->context->currency = $currency;
		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessDeleteProduct() {

		if ($this->tabAccess['edit'] === '1') {
			$errors = [];

			if ((!$idProduct = (int) Tools::getValue('id_product')) || !Validate::isInt($idProduct)) {
				$errors[] = Tools::displayError('Invalid product');
			}

			if (($idProductAttribute = (int) Tools::getValue('id_product_attribute')) && !Validate::isInt($idProductAttribute)) {
				$errors[] = Tools::displayError('Invalid combination');
			}

			if (count($errors)) {
				$this->ajaxDie(json_encode($errors));
			}

			if ($this->context->cart->deleteProduct($idProduct, $idProductAttribute, (int) Tools::getValue('id_customization'))) {
				$this->ajaxDie(json_encode($this->ajaxReturnVars()));
			}

		}

	}

	/**
	 * @return array
	 *
	 * @since 1.8.1.0
	 */
	public function ajaxReturnVars() {

		$idCart = (int) $this->context->cart->id;
		$messageContent = '';

		if ($message = Message::getMessageByCartId((int) $this->context->cart->id)) {
			$messageContent = $message['message'];
		}

		$cartRules = $this->context->cart->getCartRules(CartRule::FILTER_ACTION_SHIPPING);

		$freeShipping = false;

		if (count($cartRules)) {

			foreach ($cartRules as $cart_rule) {

				if ($cart_rule['id_cart_rule'] == CartRule::getIdByCode(CartRule::BO_ORDER_CODE_PREFIX . (int) $this->context->cart->id)) {
					$freeShipping = true;
					break;
				}

			}

		}

		$addresses = $this->context->customer->getAddresses((int) $this->context->cart->id_lang);

		foreach ($addresses as &$data) {
			$address = new Address((int) $data['id_address']);
			$data['formated_address'] = AddressFormat::generateAddress($address, [], "<br />");
		}

		return [
			'summary'              => $this->getCartSummary(),
			'delivery_option_list' => $this->getDeliveryOptionList(),
			'cart'                 => $this->context->cart,
			'currency'             => new Currency($this->context->cart->id_currency),
			'addresses'            => $addresses,
			'id_cart'              => $idCart,
			'order_message'        => $messageContent,
			'link_order'           => $this->context->link->getPageLink(
				'order',
				false,
				(int) $this->context->cart->id_lang,
				'step=3&recover_cart=' . $idCart . '&token_cart=' . md5(_COOKIE_KEY_ . 'recover_cart_' . $idCart)
			),
			'free_shipping'        => (int) $freeShipping,
		];
	}

	/**
	 * @return array
	 *
	 * @since 1.8.1.0
	 */
	protected function getCartSummary() {

		$summary = $this->context->cart->getSummaryDetails(null, true);
		$currency = $this->context->currency;

		if (count($summary['products'])) {

			foreach ($summary['products'] as &$product) {
				$product['numeric_price'] = $product['price'];
				$product['numeric_total'] = $product['total'];
				$product['price'] = str_replace($currency->sign, '', Tools::displayPrice($product['price'], $currency));
				$product['total'] = str_replace($currency->sign, '', Tools::displayPrice($product['total'], $currency));
				$product['image_link'] = $this->context->link->getImageLink($product['link_rewrite'], $product['id_image'], 'small_default');

				if (!isset($product['attributes_small'])) {
					$product['attributes_small'] = '';
				}

				$product['customized_datas'] = Product::getAllCustomizedDatas((int) $this->context->cart->id, null, true);
			}

		}

		if (count($summary['discounts'])) {

			foreach ($summary['discounts'] as &$voucher) {
				$voucher['value_real'] = Tools::displayPrice($voucher['value_real'], $currency);
			}

		}

		if (isset($summary['gift_products']) && count($summary['gift_products'])) {

			foreach ($summary['gift_products'] as &$product) {
				$product['image_link'] = $this->context->link->getImageLink($product['link_rewrite'], $product['id_image'], 'small_default');

				if (!isset($product['attributes_small'])) {
					$product['attributes_small'] = '';
				}

			}

		}

		return $summary;
	}

	/**
	 * @return array
	 *
	 * @since 1.8.1.0
	 */
	protected function getDeliveryOptionList() {

		$deliveryOptionListFormatted = [];
		$deliveryOptionList = $this->context->cart->getDeliveryOptionList();

		if (!count($deliveryOptionList)) {
			return [];
		}

		$idDefaultCarrier = (int) Configuration::get('EPH_CARRIER_DEFAULT');

		foreach (current($deliveryOptionList) as $key => $deliveryOption) {
			$name = '';
			$first = true;
			$idDefaultCarrierDelivery = false;

			foreach ($deliveryOption['carrier_list'] as $carrier) {

				if (!$first) {
					$name .= ', ';
				} else {
					$first = false;
				}

				$name .= $carrier['instance']->name;

				if ($deliveryOption['unique_carrier']) {
					$name .= ' - ' . $carrier['instance']->delay[$this->context->employee->id_lang];
				}

				if (!$idDefaultCarrierDelivery) {
					$idDefaultCarrierDelivery = (int) $carrier['instance']->id;
				}

				if ($carrier['instance']->id == $idDefaultCarrier) {
					$idDefaultCarrierDelivery = $idDefaultCarrier;
				}

				if (!$this->context->cart->id_carrier) {
					$this->context->cart->setDeliveryOption([$this->context->cart->id_address_delivery => (int) $carrier['instance']->id . ',']);
					$this->context->cart->save();
				}

			}

			$deliveryOptionListFormatted[] = ['name' => $name, 'key' => $key];
		}

		return $deliveryOptionListFormatted;
	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateCustomizationFields() {

		$errors = [];

		if ($this->tabAccess['edit'] === '1') {
			$errors = [];

			if (Tools::getValue('only_display') != 1) {

				if (!$this->context->cart->id || (!$idProduct = (int) Tools::getValue('id_product'))) {
					return;
				}

				$product = new Product((int) $idProduct);

				if (!$customizationFields = $product->getCustomizationFieldIds()) {
					return;
				}

				foreach ($customizationFields as $customizationField) {
					$fieldId = 'customization_' . $idProduct . '_' . $customizationField['id_customization_field'];

					if ($customizationField['type'] == Product::CUSTOMIZE_TEXTFIELD) {

						if (!Tools::getValue($fieldId)) {

							if ($customizationField['required']) {
								$errors[] = Tools::displayError('Please fill in all the required fields.');
							}

							continue;
						}

						if (!Validate::isMessage(Tools::getValue($fieldId))) {
							$errors[] = Tools::displayError('Invalid message');
						}

						$this->context->cart->addTextFieldToProduct((int) $product->id, (int) $customizationField['id_customization_field'], Product::CUSTOMIZE_TEXTFIELD, Tools::getValue($fieldId));
					} else
					if ($customizationField['type'] == Product::CUSTOMIZE_FILE) {

						if (!isset($_FILES[$fieldId]) || !isset($_FILES[$fieldId]['tmp_name']) || empty($_FILES[$fieldId]['tmp_name'])) {

							if ($customizationField['required']) {
								$errors[] = Tools::displayError('Please fill in all the required fields.');
							}

							continue;
						}

						if ($error = ImageManager::validateUpload($_FILES[$fieldId], (int) Configuration::get('EPH_PRODUCT_PICTURE_MAX_SIZE'))) {
							$errors[] = $error;
						}

						if (!($tmpName = tempnam(_EPH_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES[$fieldId]['tmp_name'], $tmpName)) {
							$errors[] = Tools::displayError('An error occurred during the image upload process.');
						}

						$fileName = md5(uniqid(rand(), true));

						if (!ImageManager::resize($tmpName, _EPH_UPLOAD_DIR_ . $fileName)) {
							continue;
						} else
						if (!ImageManager::resize($tmpName, _EPH_UPLOAD_DIR_ . $fileName . '_small', (int) Configuration::get('EPH_PRODUCT_PICTURE_WIDTH'), (int) Configuration::get('EPH_PRODUCT_PICTURE_HEIGHT'))) {
							$errors[] = Tools::displayError('An error occurred during the image upload process.');
						} else
						if (!chmod(_EPH_UPLOAD_DIR_ . $fileName, 0777) || !chmod(_EPH_UPLOAD_DIR_ . $fileName . '_small', 0777)) {
							$errors[] = Tools::displayError('An error occurred during the image upload process.');
						} else {
							$this->context->cart->addPictureToProduct((int) $product->id, (int) $customizationField['id_customization_field'], Product::CUSTOMIZE_FILE, $fileName);
						}

						unlink($tmpName);
					}

				}

			}

			$this->setMedia(false);
			$this->initFooter();
			$this->context->smarty->assign(
				[
					'customization_errors' => implode('<br />', $errors),
					'css_files'            => $this->css_files,
				]
			);

			return $this->smartyOutputContent('controllers/orders/form_customization_feedback.tpl');
		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateQty() {

		if ($this->tabAccess['edit'] === '1') {
			$errors = [];

			if (!$this->context->cart->id) {
				return;
			}

			if ($this->context->cart->OrderExists()) {
				$errors[] = Tools::displayError('An order has already been placed with this cart.');
			} else
			if (!($idProduct = (int) Tools::getValue('id_product')) || !($product = new Product((int) $idProduct, true, $this->context->language->id))) {
				$errors[] = Tools::displayError('Invalid product');
			} else
			if (!($qty = Tools::getValue('qty')) || $qty == 0) {
				$errors[] = Tools::displayError('Invalid quantity');
			}

			// Don't try to use a product if not instanciated before due to errors

			if (isset($product) && $product->id) {

				if (($idProductAttribute = Tools::getValue('id_product_attribute')) != 0) {

					if (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attributes::checkAttributeQty((int) $idProductAttribute, (int) $qty)) {
						$errors[] = Tools::displayError('There is not enough product in stock.');
					}

				} else
				if (!$product->checkQty((int) $qty)) {
					$errors[] = Tools::displayError('There is not enough product in stock.');
				}

				if (!($idCustomization = (int) Tools::getValue('id_customization', 0)) && !$product->hasAllRequiredCustomizableFields()) {
					$errors[] = Tools::displayError('Please fill in all the required fields.');
				}

				$this->context->cart->save();
			} else {
				$errors[] = Tools::displayError('This product cannot be added to the cart.');
			}

			if (!count($errors)) {

				if ((int) $qty < 0) {
					$qty = str_replace('-', '', $qty);
					$operator = 'down';
				} else {
					$operator = 'up';
				}

				if (!($qtyUpd = $this->context->cart->updateQty($qty, $idProduct, (int) $idProductAttribute, (int) $idCustomization, $operator))) {
					$errors[] = Tools::displayError('You already have the maximum quantity available for this product.');
				} else
				if ($qtyUpd < 0) {
					$minimalQty = $idProductAttribute ? Attributes::getAttributeMinimalQty((int) $idProductAttribute) : $product->minimal_quantity;
					$errors[] = sprintf(Tools::displayError('You must add a minimum quantity of %d', false), $minimalQty);
				}

			}

			$this->ajaxDie(json_encode(array_merge($this->ajaxReturnVars(), ['errors' => $errors])));
		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateDeliveryOption() {

		if ($this->tabAccess['edit'] === '1') {
			$deliveryOption = Tools::getValue('delivery_option');

			if ($deliveryOption !== false) {
				$this->context->cart->setDeliveryOption([$this->context->cart->id_address_delivery => $deliveryOption]);
			}

			if (Validate::isBool(($recyclable = (int) Tools::getValue('recyclable')))) {
				$this->context->cart->recyclable = $recyclable;
			}

			if (Validate::isBool(($gift = (int) Tools::getValue('gift')))) {
				$this->context->cart->gift = $gift;
			}

			if (Validate::isMessage(($giftMessage = pSQL(Tools::getValue('gift_message'))))) {
				$this->context->cart->gift_message = $giftMessage;
			}

			$this->context->cart->save();
			$this->ajaxDie(json_encode($this->ajaxReturnVars()));
		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateOrderMessage() {

		if ($this->tabAccess['edit'] === '1') {
			$idMessage = false;

			if ($oldMessage = Message::getMessageByCartId((int) $this->context->cart->id)) {
				$idMessage = $oldMessage['id_message'];
			}

			$message = new Message((int) $idMessage);

			if ($messageContent = Tools::getValue('message')) {

				if (Validate::isMessage($messageContent)) {
					$message->message = $messageContent;
					$message->id_cart = (int) $this->context->cart->id;
					$message->id_customer = (int) $this->context->cart->id_customer;
					$message->save();
				}

			} else
			if (Validate::isLoadedObject($message)) {
				$message->delete();
			}

			$this->ajaxDie(json_encode($this->ajaxReturnVars()));
		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateCurrency() {

		if ($this->tabAccess['edit'] === '1') {
			$currency = new Currency((int) Tools::getValue('id_currency'));

			if (Validate::isLoadedObject($currency) && !$currency->deleted && $currency->active) {
				$this->context->cart->id_currency = (int) $currency->id;
				$this->context->currency = $currency;
				$this->context->cart->save();
			}

			$this->ajaxDie(json_encode($this->ajaxReturnVars()));
		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateLang() {

		if ($this->tabAccess['edit'] === '1') {
			$lang = new Language((int) Tools::getValue('id_lang'));

			if (Validate::isLoadedObject($lang) && $lang->active) {
				$this->context->cart->id_lang = (int) $lang->id;
				$this->context->cart->save();
			}

			$this->ajaxDie(json_encode($this->ajaxReturnVars()));
		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessDuplicateOrder() {

		if ($this->tabAccess['edit'] === '1') {
			$errors = [];

			if (!$idOrder = Tools::getValue('id_order')) {
				$errors[] = Tools::displayError('Invalid order');
			}

			$cart = Cart::getCartByOrderId($idOrder);
			$newCart = $cart->duplicate();

			if (!$newCart || !Validate::isLoadedObject($newCart['cart'])) {
				$errors[] = Tools::displayError('The order cannot be renewed.');
			} else
			if (!$newCart['success']) {
				$errors[] = Tools::displayError('The order cannot be renewed.');
			} else {
				$this->context->cart = $newCart['cart'];
				$this->ajaxDie(json_encode($this->ajaxReturnVars()));
			}

		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessDeleteVoucher() {

		if ($this->tabAccess['edit'] === '1') {

			if ($this->context->cart->removeCartRule((int) Tools::getValue('id_cart_rule'))) {
				$this->ajaxDie(json_encode($this->ajaxReturnVars()));
			}

		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessupdateFreeShipping() {

		if ($this->tabAccess['edit'] === '1') {

			if (!$idCartRule = CartRule::getIdByCode(CartRule::BO_ORDER_CODE_PREFIX . (int) $this->context->cart->id)) {
				$cartRule = new CartRule();
				$cartRule->code = CartRule::BO_ORDER_CODE_PREFIX . (int) $this->context->cart->id;
				$cartRule->name = [Configuration::get('EPH_LANG_DEFAULT') => $this->l('Free Shipping', 'AdminTab', false, false)];
				$cartRule->id_customer = (int) $this->context->cart->id_customer;
				$cartRule->free_shipping = true;
				$cartRule->quantity = 1;
				$cartRule->quantity_per_user = 1;
				$cartRule->minimum_amount_currency = (int) $this->context->cart->id_currency;
				$cartRule->reduction_currency = (int) $this->context->cart->id_currency;
				$cartRule->date_from = date('Y-m-d H:i:s', time());
				$cartRule->date_to = date('Y-m-d H:i:s', time() + 24 * 36000);
				$cartRule->active = 1;
				$cartRule->add();
			} else {
				$cartRule = new CartRule((int) $idCartRule);
			}

			$this->context->cart->removeCartRule((int) $cartRule->id);

			if (Tools::getValue('free_shipping')) {
				$this->context->cart->addCartRule((int) $cartRule->id);
			}

			$this->ajaxDie(json_encode($this->ajaxReturnVars()));
		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessAddVoucher() {

		if ($this->tabAccess['edit'] === '1') {
			$errors = [];

			if (!($idCartRule = Tools::getValue('id_cart_rule')) || !$cartRule = new CartRule((int) $idCartRule)) {
				$errors[] = Tools::displayError('Invalid voucher.');
			} else
			if ($err = $cartRule->checkValidity($this->context)) {
				$errors[] = $err;
			}

			if (!count($errors) && isset($cartRule)) {

				if (!$this->context->cart->addCartRule((int) $cartRule->id)) {
					$errors[] = Tools::displayError('Can\'t add the voucher.');
				}

			}

			$this->ajaxDie(json_encode(array_merge($this->ajaxReturnVars(), ['errors' => $errors])));
		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateAddress() {

		if ($this->tabAccess['edit'] === '1') {
			$this->ajaxDie(json_encode(['addresses' => $this->context->customer->getAddresses((int) $this->context->cart->id_lang)]));
		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateAddresses() {

		if ($this->tabAccess['edit'] === '1') {

			if (($idAddressDelivery = (int) Tools::getValue('id_address_delivery')) &&
				($addressDelivery = new Address((int) $idAddressDelivery)) &&
				$addressDelivery->id_customer == $this->context->cart->id_customer
			) {
				$this->context->cart->id_address_delivery = (int) $addressDelivery->id;
			}

			if (($idAddressInvoice = (int) Tools::getValue('id_address_invoice')) &&
				($addressInvoice = new Address((int) $idAddressInvoice)) &&
				$addressInvoice->id_customer = $this->context->cart->id_customer
			) {
				$this->context->cart->id_address_invoice = (int) $addressInvoice->id;
			}

			$this->context->cart->save();

			$this->ajaxDie(json_encode($this->ajaxReturnVars()));
		}

	}

	/**
	 * @since 1.8.1.0
	 */
	public function displayAjaxSearchCarts() {

		$idCustomer = (int) Tools::getValue('id_customer');
		$carts = Cart::getCustomerCarts((int) $idCustomer);
		$orders = CustomerPieces::getCustomerOrders((int) $idCustomer);
		$customer = new Customer((int) $idCustomer);

		if (count($carts)) {

			foreach ($carts as $key => &$cart) {
				$cartObj = new Cart((int) $cart['id_cart']);

				if ($cart['id_cart'] == $this->context->cart->id || !Validate::isLoadedObject($cartObj) || $cartObj->OrderExists()) {
					unset($carts[$key]);
				}

				$currency = new Currency((int) $cart['id_currency']);
				$cart['total_price'] = Tools::displayPrice($cartObj->getOrderTotal(), $currency);
			}

		}

		if (count($orders)) {

			foreach ($orders as &$order) {
				$order['total_paid_real'] = Tools::displayPrice($order['total_paid_real'], $currency);
			}

		}

		if ($orders || $carts) {
			$toReturn = array_merge(
				$this->ajaxReturnVars(),
				[
					'carts'  => $carts,
					'orders' => $orders,
					'found'  => true,
				]
			);
		} else {
			$toReturn = array_merge($this->ajaxReturnVars(), ['found' => false]);
		}

		$this->ajaxDie(json_encode($toReturn));
	}

	/**
	 * @since 1.8.1.0
	 */
	public function displayAjaxGetSummary() {

		$this->ajaxDie(json_encode($this->ajaxReturnVars()));
	}

	/**
	 * @since 1.8.1.0
	 */
	public function ajaxProcessUpdateProductPrice() {

		if ($this->tabAccess['edit'] === '1') {
			SpecificPrice::deleteByIdCart((int) $this->context->cart->id, (int) Tools::getValue('id_product'), (int) Tools::getValue('id_product_attribute'));
			$specificPrice = new SpecificPrice();
			$specificPrice->id_cart = (int) $this->context->cart->id;
			$specificPrice->id_shop = 0;
			$specificPrice->id_shop_group = 0;
			$specificPrice->id_currency = 0;
			$specificPrice->id_country = 0;
			$specificPrice->id_group = 0;
			$specificPrice->id_customer = (int) $this->context->customer->id;
			$specificPrice->id_product = (int) Tools::getValue('id_product');
			$specificPrice->id_product_attribute = (int) Tools::getValue('id_product_attribute');
			$specificPrice->price = (float) Tools::getValue('price');
			$specificPrice->from_quantity = 1;
			$specificPrice->reduction = 0;
			$specificPrice->reduction_type = 'amount';
			$specificPrice->from = '0000-00-00 00:00:00';
			$specificPrice->to = '0000-00-00 00:00:00';
			$specificPrice->add();
			$this->ajaxDie(json_encode($this->ajaxReturnVars()));
		}

	}

}
