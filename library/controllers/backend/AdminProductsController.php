<?php

/**
 * Class AdminProductsControllerCore
 *
 * @since 1.9.1.0
 */
class AdminProductsControllerCore extends AdminController {

	public $php_self = 'adminproducts';
	// @codingStandardsIgnoreStart
	/** @var int Max image size for upload
	 * As of 1.5 it is recommended to not set a limit to max image size
	 */
	protected $max_file_size = null;
	protected $max_image_size = null;

	protected $_category;
	/**
	 * @var string name of the tab to display
	 */
	protected $tab_display;
	protected $tab_display_module;

	/**
	 * The order in the array decides the order in the list of tab. If an element's value is a number, it will be preloaded.
	 * The tabs are preloaded from the smallest to the highest number.
	 *
	 * @var array Product tabs.
	 */
	protected $available_tabs = [];

	protected $exclude_tabs = [];

	/** @var string $default_tab */
	protected $default_tab = 'Informations';

	/** @var array $available_tabs_lang */
	protected $available_tabs_lang = [];

	/** @var string $position_identifier */
	protected $position_identifier = 'id_product';

	/** @var array $submitted_tabs */
	protected $submitted_tabs;

	/** @var int $id_current_category */
	protected $id_current_category;

	/** @var Product $object */
	public $object;

	// @codingStandardsIgnoreEnd

	protected $ajax_json = [
		'success' => false,
		'message' => null,
	];

	public $brandSelector;

	public $taxSelector;

	public $categorySelector;

	public $availableSelector;

	public $specificPriceFields;

	public $productRequest;
	public $queryRequest;

	/**
	 * AdminProductsControllerCore constructor.
	 *
	 * @since 1.9.1.0
	 */
	public function __construct() {

		$this->bootstrap = true;
		$this->table = 'product';
		$this->className = 'Product';
		$this->lang = true;
		$this->publicName = $this->l('Products');

		if (!Tools::getValue('id_product')) {
			$this->multishop_context_group = false;
		}

		$this->availableSelector = '<div class="pq-theme"><select id="availableSelect" class="selectmenu"><option value="">' . $this->l('--Select--') . '</option><option value="0">' . $this->l('Disable') . '</option><option value="1">' . $this->l('Enable') . '</option></select></div>';

		$this->brandSelector = '<div class="pq-theme"><select id="brandSelect" class="selectmenu"><option value="">' . $this->l('--Select--') . '</option>';

		foreach (Manufacturer::getManufacturers() as $manufacturer) {
			$this->brandSelector .= '<option value="' . $manufacturer['id_manufacturer'] . '">' . $manufacturer['name'] . '</option>';
		}

		$this->brandSelector .= '</select></div>';

		$this->taxSelector .= '</select></div>';

		$this->taxSelector = '<div class="pq-theme"><select id="taxSelect" class="selectmenu"><option value="">' . $this->l('--Select--') . '</option>';

		foreach (TaxRulesGroup::getTaxRulesGroups(true) as $tax) {
			$this->taxSelector .= '<option value="' . $tax['id_tax_rules_group'] . '">' . $tax['name'] . '</option>';
		}

		$this->taxSelector .= '</select></div>';

		$this->categorySelector = '<div class="pq-theme">' . $this->buildCategoriesSelector() . '</div>';

		if ($idCategory = (int) Tools::getValue('idCategory')) {
			$this->_category = $idCategory;
		} else {
			$this->_category = 0;
		}

		parent::__construct();
		$this->context = Context::getContext();
		EmployeeConfiguration::updateValue('EXPERT_PRODUCTS_SCRIPT', $this->generateParaGridScript());
		$this->paragridScript = EmployeeConfiguration::get('EXPERT_PRODUCTS_SCRIPT');

		if (empty($this->paragridScript)) {
			EmployeeConfiguration::updateValue('EXPERT_PRODUCTS_SCRIPT', $this->generateParaGridScript());
			$this->paragridScript = EmployeeConfiguration::get('EXPERT_PRODUCTS_SCRIPT');
		}

		//EmployeeConfiguration::updateValue('EXPERT_PRODUCTS_FIELDS', Tools::jsonEncode($this->getProductFields()));
		$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_PRODUCTS_FIELDS'), true);

		if (empty($this->configurationField)) {
			EmployeeConfiguration::updateValue('EXPERT_PRODUCTS_FIELDS', Tools::jsonEncode($this->getProductFields()));
			$this->configurationField = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_PRODUCTS_FIELDS'), true);
		}

		EmployeeConfiguration::updateValue('EXPERT_EDUCATION_DECLINAISON_FIELDS', Tools::jsonEncode($this->getDeclinaisonFields()));
		$this->declinaisonFields = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_EDUCATION_DECLINAISON_FIELDS'), true);

		EmployeeConfiguration::updateValue('EXPERT_EDUCATION_DECLINAISON_FIELDS', Tools::jsonEncode($this->getDeclinaisonFields()));

		if (empty($this->declinaisonFields)) {
			EmployeeConfiguration::updateValue('EXPERT_EDUCATION_DECLINAISON_FIELDS', Tools::jsonEncode($this->getDeclinaisonFields()));
			$this->declinaisonFields = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_EDUCATION_DECLINAISON_FIELDS'), true);
		}

		$this->imageType = 'jpg';
		$this->_defaultOrderBy = 'position';
		$this->max_file_size = (int) (Configuration::get('EPH_LIMIT_UPLOAD_FILE_VALUE') * 1000000);
		$this->max_image_size = (int) Configuration::get('EPH_PRODUCT_PICTURE_MAX_SIZE');
		$this->allow_export = true;

		// @since 1.5 : translations for tabs
		$this->available_tabs_lang = [
			'Informations'   => $this->l('Information'),
			'Pack'           => $this->l('Pack'),
			'VirtualProduct' => $this->l('Virtual Product'),
			'Prices'         => $this->l('Prices'),
			'Seo'            => $this->l('SEO'),
			'Images'         => $this->l('Images'),
			'Associations'   => $this->l('Associations'),
			'Shipping'       => $this->l('Shipping'),
			'Combinations'   => $this->l('Combinations'),
			'Features'       => $this->l('Features'),
			'Customization'  => $this->l('Customization'),
			'Attachments'    => $this->l('Attachments'),
			'Quantities'     => $this->l('Quantities'),
			'Accounting'     => $this->l('Comptabilité'),
			'Suppliers'      => $this->l('Suppliers'),
			'Warehouses'     => $this->l('Warehouses'),
		];

		$this->available_tabs = ['Quantities' => 6, 'Warehouses' => 14];

		if ($this->context->shop->getContext() != Shop::CONTEXT_GROUP) {
			$this->available_tabs = array_merge($this->available_tabs, [
				'Informations'   => 0,
				'Pack'           => 7,
				'VirtualProduct' => 8,
				'Prices'         => 1,
				'Seo'            => 2,
				'Associations'   => 4,
				'Images'         => 10,
				'Shipping'       => 5,
				'Combinations'   => 6,
				'Features'       => 11,
				'Customization'  => 12,
				'Attachments'    => 13,
				'Suppliers'      => 14,
				'Accounting'     => 16,
			]);
		}

		$this->exclude_tabs = [
			'Quantities', 'Combinations', 'Images', 'Features', 'Customization', 'Attachments', 'Suppliers', 'GroupsGranted',
		];

		// Sort the tabs that need to be preloaded by their priority number
		asort($this->available_tabs, SORT_NUMERIC);

		/* Adding tab if modules are hooked */
		$modulesList = Hook::getHookModuleExecList('displayAdminProductsExtra');

		if (is_array($modulesList) && count($modulesList) > 0) {

			foreach ($modulesList as $m) {
				$this->available_tabs['Module' . ucfirst($m['module'])] = 23;
				$this->available_tabs_lang['Module' . ucfirst($m['module'])] = Module::getModuleName($m['module']);
			}

		}

		if (Tools::getValue('reset_filter_category')) {
			$this->context->cookie->id_category_products_filter = false;
		}

		if (Shop::isFeatureActive() && $this->context->cookie->id_category_products_filter) {
			$category = new Category((int) $this->context->cookie->id_category_products_filter);

			if (!$category->inShop()) {
				$this->context->cookie->id_category_products_filter = false;
				Tools::redirectAdmin($this->context->link->getAdminLink('AdminProducts'));
			}

		}

	}

	public function setAjaxMedia() {

		return $this->pushJS([
			_EPH_JS_DIR_ . 'gridproduct.js',
			_EPH_JS_DIR_ . 'jquery/ui/jquery.ui.progressbar.min.js',
		]);
	}

	public function ajaxProcessOpenTargetController() {

		$this->paragridScript = $this->generateParaGridScript();

		$data = $this->createTemplate($this->table . '.tpl');

		$helper = new HelperCategoriesTree();
		$helper->category_tree = $this->getCategoryTree();
		$helper->identifier = 'selectedCategory';
		$category_tree = $helper->generate();

		$data->assign([
			'paragridScript'     => $this->generateParaGridScript(),
			'category_tree'      => $category_tree,
			'currentToken'       => $this->token,
			'categories'         => $this->gridCategoriesSelector(),
			'manageHeaderFields' => $this->manageHeaderFields,
			'customHeaderFields' => $this->manageFieldsVisibility($controller->configurationField),
			'id_lang_default'    => Configuration::get('EPH_LANG_DEFAULT'),
			'controller'         => $this->controller_name,
			'tableName'          => $this->table,
			'className'          => $this->className,
			'link'               => $this->context->link,
			'extraJs'            => $this->setAjaxMedia(),
			'selectCategories'   => $this->buildCategoriesSelector(),
		]);

		$li = '<li id="uper' . $this->controller_name . '" data-controller="AdminDashboard" data-self="' . $this->link_rewrite . '" data-name="' . $this->page_title . '"><a href="#content' . $this->controller_name . '">' . $this->publicName . '</a><button type="button" class="close tabdetail" data-id="uper' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="content' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public function generateParaGridScript() {

		$this->uppervar = 'var pqVS = {

            rpp: 100,
            init: function () {
                this.totalRecords = 0;
                this.requestPage = 1;
                this.data = [];
				this.needInstance = 1;
				this.hasFilter = 0;
				this.isSort = 0;
            }
        };' . PHP_EOL . '

		pqVS.init();' . PHP_EOL;

		$this->requestModel = '{
			beforeSend: function( jqXHR, settings ){

				if(pqVS.hasFilter == 0 && pqVS.isSort == 0) {
				var grid = this;
				var init = (pqVS.requestPage - 1) * pqVS.rpp;

				if(init > pqVS.totalRecords) {
					init = pqVS.totalRecords;
				}
				var datalen = pq_data.length;

				var totalForCache = init+datalen;

				if(init < datalen) {

					if(totalForCache == pqVS.totalRecords) {
						var end = totalForCache;
					} else {
						var end = init + pqVS.rpp;
					}

					var nextSet = [];
					for (var i = init; i < end; i++) {
						nextSet.push(pq_data[i]);
					}
					grid.hideLoading( );
					pqVS.needInstance = false;


					return { totalRecords: totalRecords, data: nextSet };
					jqXHR.abort();
				}
				}

 			},
            location: "remote",
            dataType: "json",
            method: "POST",
			recIndx: "id_product",
			url: AjaxLinkAdminProducts,
			postData: function () {
                return {
                    action: "getProductRequest",
                    ajax: 1,
					pq_data: JSON.stringify(pq_data),
					pq_curpage: pqVS.requestPage,
                    pq_rpp: pqVS.rpp,
					needInstance: pqVS.needInstance,
					totalRecords: pqVS.totalRecords

                };
            },
            getData: function (response) {

				var data = response.data;
				var len = data.length;

				var datalen = pq_data.length;
				var init = (response.curPage - 1) * pqVS.rpp;
				var totalForCache = init+len;
				pqVS.totalRecords = response.totalRecords;
				var nextSet = [];

				if(pqVS.hasFilter == 0 && pqVS.isSort == 0) {
					if(totalForCache == pqVS.totalRecords) {
						for (var i = 0; i < len; i++) {
							pq_data.push(data[i]);
						}
						console.log("case1")
						var end = totalForCache;
					} else 	if(init == datalen) {
						console.log("case2")
						var end = init + pqVS.rpp;
						for (var i = 0; i < len; i++) {
							pq_data.push(data[i]);
						}

					} else {
						console.log("case3")
						var end = init +pqVS.rpp;
					}




					var nextSet = [];
					for (var i = init; i < end; i++) {
						nextSet.push(pq_data[i]);
					}
				} else {
					nextSet = response.data;
				}





                return { totalRecords: response.totalRecords, data: nextSet }
            }
        }';

		$this->windowHeight = '350';
		$this->paramPageModel = [
			'type'       => '\'remote\'',
			'rPP'        => 100,
			'rPPOptions' => [100],
		];

		$this->paramChange = 'function(evt, ui) {
            var grid = this;
            var updateData = ui.updateList[0];
            var newRow = updateData.newRow;
            var dataField = Object.keys(newRow)[0].toString();
            var dataValue = newRow[dataField];
            var dataProduct = updateData.rowData.id_product;
            $.ajax({
                type: \'POST\',
                url: AjaxLinkAdminProducts,
                data: {
                    action: \'updateByVal\',
                    idProduct: dataProduct,
                    field: dataField,
                    fieldValue: dataValue,
                    ajax: true
                },
                async: true,
                dataType: \'json\',
                success: function(data) {
                    if (data.success) {
                        showSuccessMessage(data.message);
                     } else {
                        showErrorMessage(data.message);
                    }
                }
            })
        }';

		$this->sortModel = [
			'cancel' => true,
			'type'   => '\'remote\'',
		];
		$this->beforeSort = '
		function (evt) {
        	if (evt.originalEvent) {
            	pqVS.init();
				pqVS.isSort = true;
            }
            }
		';

		$this->filterModel = [
			'on'          => true,
			'mode'        => '\'AND\'',
			'header'      => true,
			'type'        => '\'remote\'',
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
		$this->beforeFilter = 'function(){
                pqVS.init();
				pqVS.hasFilter = true;
            }';
		$this->rowInit = 'function (ui) {
			return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $this->controller_name . '+\'" data-class="' . $this->className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $this->identifier . '+\' "\',
            };
        }';

		$this->gridAfterLoadFunction = 'gridProduct.pager().on("change", function(evt, ui){

			pqVS.requestPage = ui.curPage;
    });
';

		$this->paramToolbar = [
			'items' => [
				[
					'type'     => '\'button\'',
					'icon'     => '\'ui-icon-disk\'',
					'label'    => '\'' . $this->l('Filter by Categories') . '\'',
					'cls'      => '\'showCategory changes ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'attr'     => '\'id="showCategoryFilter"\'',
					'listener' => 'showFilter',
				],
				[
					'type'     => '\'button\'',
					'icon'     => '\'ui-icon-disk\'',
					'label'    => '\'' . $this->l('Réinitialiser le filtre par catégorie') . '\'',
					'cls'      => '\'hideCategory changes ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'attr'     => '\'id="resetCategoryFilter"\'',
					'style'    => '\'display:none\'',
					'listener' => 'resetFilter',
					'listener' => 'function(){
						resetFilter();
						this.reset({filter: true});
					}',
				],

				[
					'type'     => '\'button\'',
					'icon'     => '\'ui-icon-disk\'',
					'label'    => '\'' . $this->l('Add new product') . '\'',
					'cls'      => '\'buttonCleanProductPosition changes ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only pull-right\'',
					'listener' => 'function () {' . PHP_EOL . '
                           addAjaxObject("' . $this->controller_name . '");' . PHP_EOL . '
                          }' . PHP_EOL,
				],

				[
					'type'  => '\'button\'',
					'icon'  => '\'ui-icon-disk\'',
					'label' => '\'' . $this->l('Gérer les champs affiché') . '\'',
					'cls'   => '\'showCategory changes ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\'',
					'attr'  => '\'id="page-header-desc-product-fields_edit"\'',
				],
			],
		];

		$this->paramTitle = '\'' . $this->l('Gérer mes produits') . '\'';
		$this->dropOn = 1;
		$this->dragOn = 1;
		$this->dragdiHelper = '[\'position\']';
		$this->dragclsHandle = '\'dragHandle\'';
		$this->moveNode = 'function(event, ui) {
            var grid = this;
            var startIndex = ui.args[0][0].productPosition;
            var idProduct = parseInt(ui.args[0][0].id_product);
            var idCategory = $(\'#selectedCategory\').val();
            var stopIndex = parseInt(ui.args[1]);
            updateProductPosition(idProduct, idCategory, startIndex, stopIndex, \'productPosition_\'+idCategory);
         }';
		$this->paramContextMenu = [
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
                selected = selgridProduct.getSelection().length;
                var dataLenght = gridProduct.option(\'dataModel.data\').length;
                return {
                    callback: function(){},
                    items: {
                        "add": {
                            name: \'' . $this->l('Add new Product') . ' \',
                            icon: "add",
                            callback: function(itemKey, opt, e) {
                                addNewProduct();
                            }
                        },
                        "edit": {
                            name : \'' . $this->l('Edit the Product: ') . '\'' . '+rowData.reference,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
								editAjaxObject("' . $this->controller_name . '", rowData.id_product)
                            }
                        },
						"image": {
                            name : \'' . $this->l('Ajouter une image pour: ') . '\'' . '+rowData.reference,
                            icon: "image",
                            callback: function(itemKey, opt, e) {
                                addImage(rowData.id_product, rowData.name);
                            }
                        },

                        "duplicate": {
                            name : \'' . $this->l('Dupliquer ce produit : ') . '\'' . '+rowData.reference,
                            icon: "copy",
                            callback: function(itemKey, opt, e) {
                                var idProduct = rowData.id_product;
                                processDuplicate(idProduct);
                            }
                        },
                        "sep1": "---------",
                        "select": {
                            name: \'' . $this->l('Select all item') . '\',
                            icon: "list-ul",
                            visible: function(key, opt){
                                var selected = selgrid' . $this->className . '.getSelection().length;
                                var dataLenght = ' . 'grid' . $this->className . '.option(\'dataModel.data\').length;
                                if(dataLenght == selected) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
                                selgrid' . $this->className . '.selectAll();
                            }
                        },
                        "unselect": {
                            name: \'' . $this->l('Unselect all item') . '\',
                            icon: "list-ul",
                            visible: function(key, opt){
                                var selected = selgrid' . $this->className . '.getSelection().length;
                                if(selected > 2) {
                                    return true;
                                }
                                return false;
                            },
                            callback: function(itemKey, opt, e) {
                                ' . 'grid' . $this->className . '.setSelection( null );
                            }
                        },
                        "bulkdiscount": {
                            name : \'' . $this->l('Apply bulk discount on selected products ') . '\' ,
                            icon: "tags",
                            callback: function(itemKey, opt, e) {
                                proceedBulkDiscount(selgridProduct);
                            },
                        },
                        "sep2": "---------",
                        "delete": {
                            name: \'' . $this->l('Delete the product:') . '\'' . '+rowData.reference,
                            icon: "delete",
                            visible: function(key, opt) {
                                if (selected == 1) {
                                    return true;
                                }
                            return false;
                            },
                            callback: function(itemKey, opt, e) {
                                var idProduct = rowData.id_product;
                                deleteProduct(idProduct, rowIndex);
                            }
                        },
                        "bulkdelete": {
                            name: \'' . $this->l('Delete the selected product') . '\',
                            icon: "delete",
                            visible: function(key, opt) {
                                if (selected < 2) {
                                    return false;
                                }
                                return true;
                            },
                            callback: function(itemKey, opt, e) {
								deleteBulkProduct(selgridProduct);
                            }
                        },
                        "sep3": "---------",
                        "searchReplace": {
                            name: \'' . $this->l('Multi criteria modification') . '\',
                            icon: "search",

                            callback: function(itemKey, opt, e) {
                                proceedBulkUpdate(selgridProduct);

                            }
                        },
                    },
                };
            }',
			]];

		return parent::generateParaGridScript();
	}

	public function getProductRequest($pq_curpage, $pq_rpp, $pq_filter, $pq_sort) {

		$file = fopen("testgetProductRequest.txt", "w");
		$nbRecords = Db::getInstance()->getValue(
			(new DbQuery())
				->select('count( * )')
				->from($this->table)
		);

		$hasFilter = false;

		$query = new DbQuery();
		$query->select('a.`id_product`, b.`link_rewrite`,a.`id_tax_rules_group`, b.`description_short`, b.`description`, b.`meta_description`, image.`id_image` AS `Image`, `reference`, b.`name`, cl.`name` AS `CategoryName` , a.`price`, a.`ecotax`, tl.`name` as `Tax`, t.rate, a.`wholesale_price`, sav.`quantity`, a.`active` as enable, a.`available_for_order` as available, a.`id_category_default`, a.`id_manufacturer`, mn.`name` AS `brand`, case  when cp.`position` >= 0 then CONCAT(\'<div class="dragGroup"><div class="productPosition_\', a.`id_category_default`,\' positions" data-id="\', a.`id_product`,\'" data-position="\',cp.`position`,\'">\',cp.`position`, \'</div></div>\') end as `position`, cp.`position` as `productPosition`, a.`cache_default_attribute` as `id_product_attribute`');
		$query->from('product', 'a');
		$query->leftJoin('product_lang', 'b', 'b.`id_product` = a.`id_product` AND b.`id_lang` = ' . (int) $this->context->language->id . ' AND b.`id_shop` = ' . (int) $this->context->shop->id);
		$query->leftJoin('stock_available', 'sav', 'sav.`id_product` = a.`id_product` AND sav.`id_product_attribute` = 0 AND sav.id_shop = ' . (int) $this->context->shop->id . ' AND sav.id_shop_group = 0');
		$query->leftJoin('manufacturer', 'mn', 'a.`id_manufacturer` = mn.`id_manufacturer`');
		$query->leftJoin('category_lang', 'cl', 'a.`id_category_default` = cl.`id_category` AND b.`id_lang` = cl.`id_lang` AND cl.id_shop = a.id_shop_default');
		$query->leftJoin('image', 'image', 'image.`id_product` = a.`id_product` AND image.`cover` = 1');
		$query->leftJoin('tax_rules_group', 'tl', 'tl.`id_tax_rules_group` = a.`id_tax_rules_group`');
		$query->leftJoin('tax', 't', 't.`id_tax` = tl.`id_tax_rules_group`');
		$query->innerJoin('category_product', 'cp', 'cp.`id_product` = a.`id_product` AND cp.`id_category` = a.`id_category_default`');

		if (is_array($pq_filter) && count($pq_filter)) {

			$hasFilter = true;
			$mode = $pq_filter['mode'];
			$filter = $pq_filter['data'];

			foreach ($filter as $key => $value) {

				if ($value['condition'] == 'equal') {
					$operator = ' = ' . $value['value'];
				} else

				if ($value['condition'] == 'contain') {
					$operator = ' LIKE \'%' . $value['value'] . '%\'';
				} else

				if ($value['condition'] == 'begin') {
					$operator = ' LIKE \'' . $value['value'] . '%\'';
				}

				$query->where('a.' . $value['dataIndx'] . $operator);
			}

		} else {

			if ($pq_curpage > 1) {
				$query->limit($pq_rpp, $pq_rpp * ($pq_curpage - 1));
			} else {

				$query->limit($pq_rpp);
			}

		}

		if (is_array($pq_sort) && count($pq_sort)) {

			foreach ($pq_sort as $key => $value) {

				if ($value['dir'] == 'up') {
					$arg = 'ASC';
				} else {
					$arg = 'DESC';
				}

				if ($value['dataIndx'] == 'productPosition') {
					$query->orderBy('cp.`position` ASC');
				} else {
					$query->orderBy('a.' . $value['dataIndx'] . ' ' . $arg);
				}

			}

		} else {
			$query->orderBy('a.`date_add` DESC');
		}

		fwrite($file, $query . PHP_EOL);
		$products = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);

		if ($hasFilter && is_array($products) && count($products)) {
			$nbRecords = count($products);

		}

		foreach ($products as &$product) {

			$product['Image'] = '<img src="' . $this->context->link->getAdminImageLink($product['link_rewrite'], $product['Image'], 'cart_default') . '" class="imgm img-thumbnail">';
			$product['stockValue'] = $product['quantity'] * $product['wholesale_price'];

			if ($product['enable'] == 1) {
				$product['active'] = '<div class="toggle-on" onClick="proceedDeActiveProduct(' . $product['id_product'] . ')"></div>';
			} else {
				$product['active'] = '<div class="toggle-off" onClick="proceedActiveProduct(' . $product['id_product'] . ')"></div>';
			}

			if ($product['available'] == 1) {
				$product['available_for_order'] = '<div class="toggle-on" onClick="proceedDeAvailableProduct(' . $product['id_product'] . ')"></div>';
			} else {
				$product['available_for_order'] = '<div class="toggle-off" onClick="proceedAvailableProduct(' . $product['id_product'] . ')"></div>';
			}

			if (Combination::isFeatureActive() && $product['id_product_attribute']) {
				$product['price'] = Combination::getPrice($product['id_product_attribute']);
			}

			$product['FinalPrice'] = $product['price'] * (1 + $product['rate'] / 100);

		}

		fwrite($file, print_r($products, true));
		return [
			'data'         => $products,
			'curPage'      => $pq_curpage,
			'totalRecords' => $nbRecords,
		];

	}

	public function ajaxProcessgetProductRequest() {

		$needInstance = Tools::getValue('needInstance');
		$pq_data = Tools::getValue('pq_data');
		$pq_data = Tools::jsonDecode($pq_data, true);
		$totalRecords = Tools::getValue('totalRecords');
		$pq_curpage = Tools::getValue('pq_curpage');

		if ($needInstance) {

			$pq_rpp = Tools::getValue('pq_rpp');
			$pq_filter = Tools::getValue('pq_filter');
			$pq_filter = Tools::jsonDecode($pq_filter, true);
			$pq_sort = Tools::getValue('pq_sort');
			$pq_sort = Tools::jsonDecode($pq_sort, true);
			header("Content-type: application/json");

			$return = Tools::jsonEncode($this->getProductRequest($pq_curpage, $pq_rpp, $pq_filter, $pq_sort));
			die($return);
		} else {
			return [
				'data'         => $pq_data,
				'curPage'      => $pq_curpage,
				'totalRecords' => $totalRecords,
			];
			die(Tools::jsonEncode($return));
		}

	}

	public function getProductFields() {

		$select = $this->l('--Select--');
		return [

			[
				'title'     => $this->l('ID'),
				'maxWidth'  => 70,
				'exWidth'   => 15,
				'dataIndx'  => 'id_product',
				'dataType'  => 'integer',
				'editable'  => false,
				'updatable' => false,
				'halign'    => 'HORIZONTAL_CENTER',
				'align'     => 'center',
				'valign'    => 'center',
				'filter'    => [
					'crules' => [['condition' => "begin"]],
				],
			],
			[
				'title'      => $this->l('Image'),
				'width'      => 50,
				'exWidth'    => 30,
				'dataIndx'   => 'Image',
				'align'      => 'center',
				'valign'     => 'center',
				'cls'        => 'thumb_product',
				'dataType'   => 'html',
				'exportType' => 'Image',
				'editable'   => false,
				'updatable'  => false,
				'sortable'   => false,

			],
			[
				'title'     => $this->l('Reference'),
				'minWidth'  => 100,
				'exWidth'   => 30,
				'dataIndx'  => 'reference',
				'editable'  => false,
				'updatable' => false,
				'align'     => 'left',
				'halign'    => 'HORIZONTAL_LEFT',
				'valign'    => 'center',
				'dataType'  => 'string',
				'filter'    => [
					'crules' => [['condition' => "contain"]],
				],
			],
			[
				'title'     => $this->l('Name'),
				'minWidth'  => 200,
				'exWidth'   => 65,
				'dataIndx'  => 'name',
				'halign'    => 'HORIZONTAL_LEFT',
				'dataType'  => 'string',
				'dataForm'  => 'string',
				'align'     => 'left',
				'valign'    => 'center',
				'editable'  => true,
				'updatable' => false,

				'filter'    => [
					'crules' => [['condition' => "contain"]],
				],
			],
			[
				'title'     => $this->l('Short Description'),
				'width'     => 200,
				'exWidth'   => 65,
				'dataIndx'  => 'description_short',
				'dataType'  => 'html',
				'dataForm'  => 'html',
				'editable'  => true,
				'updatable' => false,
				'align'     => 'left',
				'halign'    => 'HORIZONTAL_LEFT',
				'valign'    => 'center',
				'hidden'    => true,
			],
			[
				'title'     => $this->l('Description'),
				'width'     => 200,
				'exWidth'   => 65,
				'dataIndx'  => 'description',
				'dataType'  => 'html',
				'dataForm'  => 'html',
				'editable'  => false,
				'updatable' => false,
				'align'     => 'left',
				'halign'    => 'HORIZONTAL_LEFT',
				'valign'    => 'center',
				'hidden'    => true,
			],
			[
				'title'     => $this->l('Meta Description'),
				'width'     => 200,
				'exWidth'   => 65,
				'dataIndx'  => 'meta_description',
				'dataType'  => 'string',
				'dataForm'  => 'string',
				'align'     => 'left',
				'valign'    => 'center',
				'halign'    => 'HORIZONTAL_LEFT',
				'editable'  => true,
				'updatable' => false,
				'filter'    => [
					'crules' => [['condition' => "contain"]],
				],
				'hidden'    => true,
			],
			[
				'title'      => $this->l('Id manufacturer'),
				'minWidth'   => 10,
				'dataIndx'   => 'id_manufacturer',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

				'sortable'   => false,

			],
			[
				'title'     => $this->l('Brand'),
				'titleForm' => $this->l('Select brand for selected products'),
				'width'     => 150,
				'exWidth'   => 30,
				'dataIndx'  => 'brand',
				'labelIndx' => 'id_manufacturer',
				'align'     => 'left',
				'halign'    => 'HORIZONTAL_LEFT',
				'editable'  => false,
				'valign'    => 'center',
				'editable'  => false,
				'updatable' => true,
				'dataType'  => 'string',
				'dataForm'  => 'select',
				'sortable'  => false,

			],

			[

				'dataIndx'   => 'id_category_default',
				'dataType'   => 'integer',
				'align'      => 'center',
				'valign'     => 'center',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'     => $this->l('Category'),
				'titleForm' => $this->l('Select category for selected products'),
				'minWidth'  => 150,
				'exWidth'   => 40,
				'dataIndx'  => 'CategoryName',
				'valign'    => 'center',
				'dataType'  => 'string',
				'updatable' => false,
				'sortable'  => false,

			],
			[
				'title'        => $this->l('WholeSale price'),
				'titleForm'    => $this->l('Change wholesale price for selected products'),
				'maxWidth'     => 150,
				'exWidth'      => 20,
				'dataIndx'     => 'wholesale_price',

				'align'        => 'right',
				'halign'       => 'HORIZONTAL_RIGHT',
				'numberFormat' => '#,##0.00_-"€ ' . $this->l('Tax excl.') . '"',
				'valign'       => 'center',
				'dataType'     => 'float',
				'dataForm'     => 'float',
				'format'       => '# ##0,00 €' . $this->l('Tax excl.'),
				'hidden'       => true,
				'updatable'    => true,
			],
			[
				'title'        => $this->l('Base price'),
				'titleForm'    => $this->l('Change resell price Tax excl. for selected products'),
				'exWidth'      => 20,
				'dataIndx'     => 'price',
				'align'        => 'right',
				'valign'       => 'center',
				'halign'       => 'HORIZONTAL_RIGHT',
				'numberFormat' => '#,##0.00_-"€ ' . $this->l('Tax excl.') . '"',
				'dataType'     => 'float',
				'dataForm'     => 'float',
				'updatable'    => true,
				'format'       => "#.###,00 € " . $this->l('Tax excl.'),
			],
			[
				'title'        => $this->l('Eco Tax'),
				'titleForm'    => $this->l('Change ecotax amount for selected products'),
				'maxWidth'     => 100,
				'exWidth'      => 20,
				'dataIndx'     => 'ecotax',
				'align'        => 'right',
				'halign'       => 'HORIZONTAL_RIGHT',
				'numberFormat' => '#,##0.00_-"€"',
				'valign'       => 'center',
				'dataType'     => 'float',
				'dataForm'     => 'float',
				'updatable'    => true,
				'format'       => "#.###,00 €",
				'hidden'       => true,
			],
			[

				'dataIndx'   => 'id_tax_rules_group',
				'dataType'   => 'integer',
				'editable'   => false,
				'hidden'     => true,
				'hiddenable' => 'no',
				'filter'     => [

					'crules' => [['condition' => "equal"]],

				],

			],
			[
				'title'     => $this->l('Taux de TVA'),
				'titleForm' => $this->l('Select category for selected products'),
				'minWidth'  => 100,
				'exWidth'   => 25,
				'dataIndx'  => 'Tax',
				'labelIndx' => 'id_tax_rules_group',
				'align'     => 'right',
				'halign'    => 'HORIZONTAL_RIGHT',
				'valign'    => 'center',
				'dataType'  => 'string',
				'dataForm'  => 'select',
				'align'     => 'center',
				'editable'  => false,
				'updatable' => true,
				'sortable'  => false,

			],
			[
				'title'        => $this->l('Tarif TTC'),

				'exWidth'      => 20,
				'dataIndx'     => 'FinalPrice',
				'align'        => 'right',
				'valign'       => 'center',
				'dataType'     => 'float',
				'halign'       => 'HORIZONTAL_RIGHT',
				'numberFormat' => '#,##0.00_-"€"',
				'editable'     => false,
				'format'       => "#.###,00 € " . $this->l('Tax incl.'),
				'updatable'    => false,
				'sortable'     => false,
			],
			[
				'title'     => $this->l('Quantity'),
				'maxWidth'  => 100,
				'exWidth'   => 20,
				'dataIndx'  => 'quantity',
				'align'     => 'center',
				'halign'    => 'HORIZONTAL_CENTER',
				'valign'    => 'center',
				'editable'  => true,
				'hidden'    => true,
				'dataType'  => 'integer',
				'dataForm'  => 'integer',
				'updatable' => false,
			],
			[
				'title'        => $this->l('Stock Value'),
				'maxWidth'     => 100,
				'exWidth'      => 20,
				'dataIndx'     => 'stockValue',
				'align'        => 'right',
				'valign'       => 'center',
				'halign'       => 'HORIZONTAL_RIGHT',
				'hidden'       => true,
				'numberFormat' => '#,##0.00_-"€ ' . $this->l('Tax excl.') . '"',
				'dataType'     => 'float',
				'format'       => "#.###,00 € " . $this->l('Tax excl.'),
				'editable'     => false,
				'updatable'    => false,
				'sortable'     => false,
			],
			[

				'dataIndx'   => 'productPosition',
				'cls'        => 'positionSorter',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',

			],
			[
				'title'     => $this->l('Position'),
				'minWidth'  => 100,
				'exWidth'   => 20,
				'dataIndx'  => 'position',
				'cls'       => 'pointer dragHandle',
				'dataType'  => 'html',
				'align'     => 'center',
				'valign'    => 'center',
				'hidden'    => 'true',
				'editable'  => false,
				'updatable' => false,

			],
			[

				'dataIndx'   => 'enable',
				'dataType'   => 'integer',
				'align'      => 'center',
				'valign'     => 'center',
				'hidden'     => true,
				'hiddenable' => 'no',
				'filter'     => [

					'attr'   => "id=\"enableSelecor\", placeholder=" . $this->l('--Select--') . " readonly",
					'crules' => [['condition' => "equal"]],

				],

			],
			[
				'title'     => $this->l('Status'),
				'titleForm' => $this->l('Select status for selected products'),
				'minWidth'  => 100,
				'dataIndx'  => 'active',
				'dataForm'  => 'select',
				'align'     => 'center',
				'valign'    => 'center',
				'halign'    => 'HORIZONTAL_CENTER',
				'dataType'  => 'html',
				'editable'  => false,
				'sortable'  => false,

			],
			[

				'dataIndx'   => 'available',
				'dataType'   => 'integer',
				'align'      => 'center',
				'valign'     => 'center',
				'hidden'     => true,
				'hiddenable' => 'no',
				'filter'     => [

					'crules' => [['condition' => "equal"]],

				],

			],
			[
				'title'    => $this->l('Available for order'),
				'minWidth' => 100,
				'dataIndx' => 'available_for_order',
				'dataForm' => 'select',
				'align'    => 'center',
				'valign'   => 'center',
				'halign'   => 'HORIZONTAL_CENTER',
				'dataType' => 'html',
				'editable' => false,
				'hidden'   => true,
				'filter'   => [
					'attr'     => "id=\"availableSelector\", placeholder=" . $this->l('--Select--') . " readonly",
					'crules'   => [['condition' => "equal"]],
					"listener" => 'function( evt, ui ){console.log(ui) }',
				],
				'sortable' => false,

			],

		];

	}

	public function ajaxProcessgetProductFields() {

		die(Tools::jsonEncode($this->getProductFields()));

	}

	public function manageFieldsVisibility($fields) {

		return parent::manageFieldsVisibility($fields);
	}

	public function ajaxProcessupdateVisibility() {

		$headerFields = Tools::jsonDecode(EmployeeConfiguration::get('EXPERT_PRODUCTS_FIELDS'), true);
		$visibility = Tools::getValue('visibilities');

		foreach ($headerFields as $key => $headerField) {
			$hidden = '';

			foreach ($headerField as $field => $value) {

				if ($field == 'dataIndx') {

					if ($visibility[$value] == 1) {
						$hidden = false;
					} else

					if ($visibility[$value] == 0) {
						$hidden = true;
					}

				}

			}

			$headerField['hidden'] = $hidden;

			$headerFields[$key] = $headerField;
		}

		$headerFields = Tools::jsonEncode($headerFields);
		EmployeeConfiguration::updateValue('EXPERT_PRODUCTS_FIELDS', $headerFields);
		die($headerFields);
	}

	public function getDeclinaisonFields() {

		return [
			[
				'title'      => '',
				'dataIndx'   => 'id_product',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],
			[
				'title'      => '',
				'dataIndx'   => 'id_product_attribute',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],

			[
				'title'    => $this->l('Reference'),
				'width'    => 100,
				'dataIndx' => 'reference',
				'align'    => 'left',
				'editable' => true,
				'dataType' => 'string',
			],
			[
				'title'    => $this->l('Name'),
				'width'    => 100,
				'dataIndx' => 'attribute_name',
				'align'    => 'left',
				'editable' => true,
				'dataType' => 'string',
			],
			[
				'title'    => $this->l('Attribute - value pair'),
				'width'    => 200,
				'dataIndx' => 'attributes',
				'dataType' => 'html',
				'editable' => false,
			],

			[
				'title'    => $this->l('Price'),
				'width'    => 150,
				'dataIndx' => 'price',
				'align'    => 'right',
				'dataType' => 'float',
				'format'   => '€ #,###.00',
			],

			[
				'title'    => $this->l('Default'),
				'width'    => 50,
				'dataIndx' => 'default_on',
				'align'    => 'center',
				'dataType' => 'html',
			],

		];
	}

	public function ajaxProcessGetDeclinaisonFields() {

		die(EmployeeConfiguration::get('EXPERT_EDUCATION_DECLINAISON_FIELDS'));
	}

	public function getDeclinaisonRequest($idProduct) {

		if ($idProduct > 0) {

			$product = new Product($idProduct);

			$combinations = $product->getAttributeCombinations($this->context->language->id);

			$groups = [];

			if (is_array($combinations)) {
				$educationLink = $this->context->link->getAdminLink($this->controller_name);

				foreach ($combinations as &$combination) {

					$combination['attributes'] = $product->name[$this->context->language->id] . '<br>' . $combination['group_name'] . ' :' . $combination['attribute_name'];
					$combination['price'] = $combination['price'] + $product->price;

				}

			}

		}

		return $combinations;

	}

	public function ajaxProcessGetDeclinaisonRequest() {

		$object = Tools::getValue('id_product');

		die(Tools::jsonEncode($this->getDeclinaisonRequest($object)));
	}

	public function getAssociationRequest($idProduct) {

		$file = fopen("testgetAssociationRequest.txt", "w");
		fwrite($file, $idProduct . PHP_EOL);

		if ($idProduct > 0) {

			$product = new Product($idProduct);
			$selectedCat = Product::getProductCategoriesFull($product->id, $this->default_form_language);
			$category_tree = $this->getCategoryTree(array_keys($selectedCat));

		} else {
			$category_tree = $this->getCategoryTree([]);
		}

		fwrite($file, print_r($category_tree, true) . PHP_EOL);
		return $category_tree;

	}

	public function ajaxProcessGetAssociationRequest() {

		$object = Tools::getValue('id_product');

		die($this->getAssociationRequest($object));
	}

	public function ajaxProcessSaveProduct() {

		$file = fopen("testProcessSaveProduct.txt", "w");
		$id_product = Tools::getValue('id_product');
		fwrite($file, $id_product . PHP_EOL);

		if ($id_product > 0) {
			$product = new Product($id_product);
			$productTypeBefore = $product->getType();

			if (Validate::isLoadedObject($product)) {

				foreach ($_POST as $key => $value) {

					if (property_exists($product, $key) && $key != 'id_product') {
						$product->{$key}

						= $value;
					}

				}

				$classVars = get_class_vars(get_class($product));
				$fields = [];

				if (isset($classVars['definition']['fields'])) {
					$fields = $classVars['definition']['fields'];
				}

				foreach ($fields as $field => $params) {

					if (array_key_exists('lang', $params) && $params['lang']) {

						foreach (Language::getIDs(false) as $idLang) {

							if (Tools::isSubmit($field . '_' . (int) $idLang)) {

								if (!isset($product->{$field}) || !is_array($product->{$field})) {
									$product->{$field}

									= [];
								}

								$product->{$field}

								[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
							}

						}

					}

				}

				foreach (Language::getIDs(false) as $idLang) {

					if (isset($_POST['tags_' . $idLang])) {
						$_POST['tags_' . $idLang] = $this->_cleanMetaKeywords(mb_strtolower($_POST['tags_' . $idLang]));
						$product->meta_keywords[$idLang] = $_POST['tags_' . $idLang];
					}

				}

				try {
					$result = $product->update();
				} catch (Exception $e) {
					//$file = fopen("testProcessSaveProduct.txt", "w");
					fwrite($file, $e->getMessage());

				}

				if ($result) {
					fwrite($file, 'Result ok' . PHP_EOL);
					$languages = Language::getLanguages(false);
					$this->updateTags($languages, $product);
					fwrite($file, 'updateTags' . PHP_EOL);

					if ($this->isTabSubmitted('Shipping')) {
						fwrite($file, 'addCarriers' . PHP_EOL);
						$this->addCarriers();

					}

					if ($this->isTabSubmitted('Associations')) {
						fwrite($file, 'updateAccessories' . PHP_EOL);
						$this->updateAccessories($product);

					}

					if ($this->isTabSubmitted('Suppliers')) {
						$this->processSuppliers();
						fwrite($file, 'processSuppliers' . PHP_EOL);
					}

					if ($this->isTabSubmitted('Features')) {
						$this->processFeatures();
						fwrite($file, 'processFeatures' . PHP_EOL);
					}

					if ($this->isTabSubmitted('Combinations')) {
						$this->processProductAttribute();
						fwrite($file, 'processProductAttribute' . PHP_EOL);
					}

					if ($this->isTabSubmitted('Prices')) {
						fwrite($file, 'Prices' . PHP_EOL);
						$this->processPriceAddition();
						$this->processSpecificPricePriorities();
					}

					if ($this->isTabSubmitted('Customization')) {
						fwrite($file, 'Customization' . PHP_EOL);
						$this->processCustomizationConfiguration();
					}

					if ($this->isTabSubmitted('Attachments')) {
						fwrite($file, 'Attachments' . PHP_EOL);
						$this->processAttachments();
					}

					$this->updatePackItems($product);
					// Disallow avanced stock management if the product become a pack
					fwrite($file, 'Pack Item Ok' . PHP_EOL);

					if ($productTypeBefore == Product::PTYPE_SIMPLE && $product->getType() == Product::PTYPE_PACK) {
						StockAvailable::setProductDependsOnStock((int) $product->id, false);
					}

					$this->updateDownloadProduct($object);
					fwrite($file, 'updateDownloadProduct' . PHP_EOL);
					fwrite($file, Tools::getValue('categoryBox') . PHP_EOL);

					if ($this->isProductFieldUpdated('category_box') && !$product->updateCategories(Tools::getValue('categoryBox'))) {
						$this->errors[] = Tools::displayError('An error occurred while linking the object.') . ' <b>' . $this->table . '</b> ' . Tools::displayError('To categories');
					}

					fwrite($file, 'categoryBox ok' . PHP_EOL);

					if ($this->isTabSubmitted('Warehouses')) {
						$this->processWarehouses();
					}

					$return = [
						'success' => true,
						'message' => $this->l('Ce produit a été mise à jour avec succès'),
					];
				} else {
					$return = [
						'success' => false,
						'message' => $this->l('Une erreur s’est produite en essayant de mettre à jour ce produit'),
					];
				}

			} else {
				$return = [
					'success' => false,
					'message' => $this->l('Une erreur s’est produite en essayant de de charger ce produit'),
				];
			}

		} else {

			$product = new Product();

			foreach ($_POST as $key => $value) {

				if (property_exists($product, $key) && $key != 'id_product') {
					$product->{$key}

					= $value;
				}

			}

			$classVars = get_class_vars(get_class($product));
			$fields = [];

			if (isset($classVars['definition']['fields'])) {
				$fields = $classVars['definition']['fields'];
			}

			foreach ($fields as $field => $params) {

				if (array_key_exists('lang', $params) && $params['lang']) {

					foreach (Language::getIDs(false) as $idLang) {

						if (Tools::isSubmit($field . '_' . (int) $idLang)) {

							if (!isset($product->{$field}) || !is_array($product->{$field})) {
								$product->{$field}

								= [];
							}

							$product->{$field}

							[(int) $idLang] = Tools::getValue($field . '_' . (int) $idLang);
						}

					}

				}

			}

			foreach (Language::getIDs(false) as $idLang) {

				if (isset($_POST['tags_' . $idLang])) {
					$_POST['tags_' . $idLang] = $this->_cleanMetaKeywords(mb_strtolower($_POST['tags_' . $idLang]));
					$product->meta_keywords[$idLang] = $_POST['tags_' . $idLang];
				}

			}

			$result = $product->add();

			if ($result) {
				$languages = Language::getLanguages(false);
				$this->updateTags($languages, $product);
				Logger::addLog(sprintf($this->l('%s addition', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int) $product->id, true, (int) $this->context->employee->id);
				$this->addCarriers($product);
				$this->updateAccessories($product);
				$this->updatePackItems($product);
				$this->updateDownloadProduct($product);

				if (Configuration::get('EPH_FORCE_ASM_NEW_PRODUCT') && Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT') && $product->getType() != Product::PTYPE_VIRTUAL) {
					$product->advanced_stock_management = 1;
					$product->save();
					$idShops = Shop::getContextListShopID();

					foreach ($idShops as $idShop) {
						StockAvailable::setProductDependsOnStock($product->id, true, (int) $idShop, 0);
					}

				}

				if (empty($this->errors)) {

					if ($this->isProductFieldUpdated('category_box') && !$product->updateCategories(Tools::getValue('categoryBox'))) {
						$this->errors[] = Tools::displayError('An error occurred while linking the object.') . ' <b>' . $this->table . '</b> ' . Tools::displayError('To categories');
					} else {
						Hook::exec('actionProductAdd', ['id_product' => (int) $product->id, 'product' => $product]);

						if (in_array($product->visibility, ['both', 'search']) && Configuration::get('EPH_SEARCH_INDEXATION')) {
							Search::indexation(false, $product->id);
						}

					}

					if (Configuration::get('EPH_DEFAULT_WAREHOUSE_NEW_PRODUCT') != 0 && Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT')) {
						$warehouseLocationEntity = new WarehouseProductLocation();
						$warehouseLocationEntity->id_product = $product->id;
						$warehouseLocationEntity->id_product_attribute = 0;
						$warehouseLocationEntity->id_warehouse = Configuration::get('EPH_DEFAULT_WAREHOUSE_NEW_PRODUCT');
						$warehouseLocationEntity->location = pSQL('');
						$warehouseLocationEntity->save();
					}

					// Apply groups reductions
					$product->setGroupReduction();
					$return = [
						'success'    => true,
						'message'    => $this->l('The product has been created successfully'),
						'id_product' => $product->id,
					];

				} else {
					$product->delete();
					$return = [
						'success' => false,
						'message' => $this->l('We encounter an error trying to create this Product'),
					];

				}

			} else {
				$return = [
					'success' => false,
					'message' => $this->l('We encounter an error trying to create this Product'),
				];
			}

		}

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessAddnewCombination() {

		$id_product = Tools::getValue('id_product');
		$product = new Product($id_product);
		$id_attribute = Tools::getValue('attribute');

		if (!Tools::getIsset('attribute_declinaison_list') || Tools::isEmpty(Tools::getValue('attribute_combination_list'))) {
			$this->errors[] = Tools::displayError('You must add at least one attribute.');
		}

		if (Validate::isLoadedObject($product)) {

			if (($idProductAttribute = (int) Tools::getValue('id_product_attribute')) || ($idProductAttribute = $product->productAttributeExists(Tools::getValue('attribute_declinaison_list'), false, null, true, true))) {

			} else {

				if ($product->productAttributeExists(Tools::getValue('attribute_declinaison_list'))) {

					$this->errors[] = Tools::displayError('This combination already exists.');
				} else {

					$declinaison = new Combination();
					$declinaison->id_education = (int) $education->id;
					$declinaison->reference = pSQL(Tools::getValue('attribute_reference'));
					$declinaison->price = (float) Tools::getValue('attribute_price') * Tools::getValue('attribute_price_impact');
					$declinaison->default_on = (int) Tools::getValue('attribute_default');
					$declinaison->ean13 = (int) Tools::getValue('attribute_ean13');
					$declinaison->upc = (int) Tools::getValue('attribute_upc');
					$declinaison->wholesale_price = (float) Tools::getValue('attribute_wholesale_price');
					$declinaison->price = Tools::getValue('attribute_price');
					$declinaison->quantity = Tools::getValue('attribute_quantity');
					$declinaison->weight = Tools::getValue('attribute_weight');
					$declinaison->unit_price_impact = Tools::getValue('attribute_unit_price_impact');
					$declinaison->minimal_quantity = Tools::getValue('attribute_minimal_quantity');
					$declinaison->available_date = Tools::getValue('attribute_available_date');

					foreach (Language::getIDs(false) as $idLang) {
						$declinaison->name[$idLang] = Tools::getValue('attribute_name_' . $idLang);
						$declinaison->description[$idLang] = Tools::getValue('attribute_description_' . $idLang);
						$declinaison->description_short[$idLang] = Tools::getValue('attribute_description_short_' . $idLang);
					}

					$result = $declinaison->add();

					if (!isset($result) || !$result) {
						$this->errors[] = Tools::displayError('An error occurred while adding declinaison.');
					} else {

						$idImages = Tools::getValue('id_image_attr');

						if (is_array($idImages) && count($idImages)) {
							$declinaison->setImages($idImages);
						}

						$result = Db::getInstance()->execute('
                                INSERT INTO `' . _DB_PREFIX_ . 'product_attribute_combination` (`id_attribute`, `id_product_attribute`) VALUES (' . $id_attribute . ', ' . $declinaison->id . ')');
					}

				}

			}

		}

		$this->errors = array_unique($this->errors);

		if (!empty($this->errors)) {
			$result = [
				'success' => false,
				'message' => $this->error,
			];
		} else {
			$result = [
				'success'     => true,
				'idEducation' => $education->id,
				'message'     => $this->l('Declinaisons has been added with success'),
			];
		}

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessUpdateCombination() {

		$id_product = Tools::getValue('id_product');
		$id_product_attribute = Tools::getValue('id_product_attribute');
		$product = new Product($id_product);
		$declinaison = new Combination($id_product_attribute);

		if (Validate::isLoadedObject($product) && Validate::isLoadedObject($declinaison)) {

			if (!Tools::getIsset('attribute_declinaison_list') || Tools::isEmpty(Tools::getValue('attribute_combination_list'))) {
				$result = [
					'success' => false,
					'message' => Tools::displayError('You must add at least one attribute.'),
				];
				die(Tools::jsonEncode($result));
			}

			if ($declinaison->id_product != $id_product) {
				$result = [
					'success' => false,
					'message' => Tools::displayError('a essential parameter is going wrong.'),
				];
				die(Tools::jsonEncode($result));

			} else {
				$default_status = $declinaison->default_on;

				$new_status = (int) Tools::getValue('attribute_default');

				$defaultAttribute = $product->getDefaultIdProductAttribute();

				if ($declinaison->id == $defaultAttribute) {

					if ($new_status == 0) {
						$new_status = 1;
					}

				} else

				if ($new_status == 1) {
					Db::getInstance()->execute(
						'UPDATE `' . _DB_PREFIX_ . 'product_attribute`
                        SET `default_on` = 0
                        WHERE `id_product_attribute` = ' . (int) $defaultAttribute
					);
				}

				$declinaison->reference = pSQL(Tools::getValue('attribute_reference'));
				$declinaison->price = (float) Tools::getValue('attribute_price') * Tools::getValue('attribute_price_impact');
				$declinaison->default_on = $new_status;
				$declinaison->ean13 = (int) Tools::getValue('attribute_ean13');
				$declinaison->upc = (int) Tools::getValue('attribute_upc');
				$declinaison->wholesale_price = (float) Tools::getValue('attribute_wholesale_price');
				$declinaison->price = Tools::getValue('attribute_price');
				$declinaison->quantity = Tools::getValue('attribute_quantity');
				$declinaison->weight = Tools::getValue('attribute_weight');
				$declinaison->unit_price_impact = Tools::getValue('attribute_unit_price_impact');
				$declinaison->minimal_quantity = Tools::getValue('attribute_minimal_quantity');
				$declinaison->available_date = Tools::getValue('attribute_available_date');

				foreach (Language::getIDs(false) as $idLang) {
					$declinaison->name[$idLang] = Tools::getValue('attribute_name_' . $idLang);
					$declinaison->description[$idLang] = Tools::getValue('attribute_description_' . $idLang);
					$declinaison->description_short[$idLang] = Tools::getValue('attribute_description_short_' . $idLang);
				}

				$result = $declinaison->update();

				if (!isset($result) || !$result) {
					$result = [
						'success' => false,
						'message' => Tools::displayError('An error occurred while updating declinaison.'),
					];
					die(Tools::jsonEncode($result));
				} else {

					$idImages = Tools::getValue('id_image_attr');

					if (is_array($idImages) && count($idImages)) {
						$declinaison->setImages($idImages);
					}

					Product::updateDefaultAttribute((int) $product->id);
					$result = [
						'success'   => true,
						'idProduct' => $product->id,
						'message'   => $this->l('Declinaisons has been updated with success'),
					];
					die(Tools::jsonEncode($result));
				}

			}

		} else {
			$result = [
				'success' => false,
				'message' => Tools::displayError('An error occurred loading the object.'),
			];
			die(Tools::jsonEncode($result));
		}

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessEditObject() {

		if ($this->tabAccess['edit'] == 1) {
			$idProduct = Tools::getValue('idObject');
			$this->object = new Product($idProduct, true);

			$iso = (file_exists(_EPH_ROOT_ADMIN_DIR_ . '/js/tinymce/langs/' . $this->context->language->iso_code . '.js') ? $iso : 'en');

			$data = $this->createTemplate('controllers/products/editProduct.tpl');

			$extracss = $this->pushCSS([
				_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/fancy_fileupload.css',
				_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/imageuploadify.min.css',
				_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/multiaccessories/accessory_admin_tab.css',
				_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/multiaccessories/jquery-confirm.min.css',
			]);

			$_GET['id_product'] = $idProduct;
			$_GET['updateproduct'] = 1;

			$pusjJs = $this->pushJS([
				_EPH_JS_DIR_ . 'products.js',
				_EPH_JS_DIR_ . 'attributes.js',
				_EPH_JS_DIR_ . 'price.js',
				_EPH_JS_DIR_ . 'tinymce/tinymce.min.js',
				_EPH_JS_DIR_ . 'tinymce.inc.js',
				_EPH_JS_DIR_ . 'imageuploadify.min.js',
				_EPH_JS_DIR_ . 'colorpicker/colorpicker.js',
				_EPH_JS_DIR_ . 'jquery/plugins/jquery.typewatch.js',
				_EPH_JS_DIR_ . 'pdfuploadify.min.js',
				_EPH_JS_DIR_ . 'multiaccessories/admin_product_setting.js',
				_EPH_JS_DIR_ . 'multiaccessories/admin_multi_accessories.js',
				_EPH_JS_DIR_ . 'multiaccessories/jquery-confirm.min.js',
				_EPH_JS_DIR_ . 'multiaccessories/ma_sidebar_closed.js',
				_EPH_JS_DIR_ . 'jquery/plugins/jquery.tablednd.js',
			]);

			$scripHeader = Hook::exec('displayBackOfficeHeader', []);
			$scriptFooter = Hook::exec('displayBackOfficeFooter', []);

			$productTabs = [];
			$advancedStockManagementActive = Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT');

			foreach ($this->available_tabs as $productTab => $value) {

				if ($advancedStockManagementActive == 0 && $productTab == 'Warehouses') {
					continue;
				}

				$this->tab_display = $productTab;

				if (substr($this->tab_display, 0, 6) == 'Module') {
					$this->tab_display_module = strtolower(substr($this->tab_display, 6, mb_strlen($this->tab_display) - 6));
					$this->tab_display = 'Modules';
				}

				$this->tpl_form = 'controllers/products/' . strtolower($this->tab_display) . '.tpl';

				$content = $this->renderForm();

				$productTabs[$productTab] = [
					'id'       => $productTab,
					'selected' => (strtolower($productTab) == strtolower($this->default_tab)),
					'name'     => $this->available_tabs_lang[$productTab],
					'content'  => $content,
				];
			}

			$data->assign([
				'pusjJs'                    => $pusjJs,
				'extracss'                  => $extracss,
				'product_tabs'              => $productTabs,
				'product'                   => $this->object,
				'languages'                 => $this->_languages,
				'id_lang'                   => $this->context->language->id,
				'allowEmployeeFormLang'     => $this->allow_employee_form_lang,
				'id_product'                => $this->object->id,
				'id_lang_default'           => Configuration::get('EPH_LANG_DEFAULT'),
				'has_declinaisons'          => $this->object->hasAttributes(),
				'post_data'                 => json_encode($_POST),
				'save_error'                => !empty($this->errors),
				'mod_evasive'               => Tools::apacheModExists('evasive'),
				'mod_security'              => Tools::apacheModExists('security'),
				'EPH_force_friendly_product' => Configuration::get('EPH_FORCE_FRIENDLY_PRODUCT'),
				'tinymce'                   => true,
				'iso'                       => $iso,
				'path_css'                  => _THEME_CSS_DIR_,
				'ad'                        => __EPH_BASE_URI__ . basename(_EPH_ADMIN_DIR_),
				'product_type'              => (int) Tools::getValue('type_product', $this->object->getType()),
			]);

			$html = $data->fetch() . PHP_EOL;

			$li = '<li id="uperEdit' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentEdit' . $this->controller_name . '">Visualiser ou modifier ce produit</a><button type="button" class="close tabdetail" data-id="uperEdit' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
			$html = '<div id="contentEdit' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $scripHeader . $html . $scriptFooter . '</div>';

			$result = [
				'success' => true,
				'li'      => $li,
				'html'    => $html,
			];
		} else {
			$result = [
				'success' => false,
				'message' => 'Votre profile administratif ne vous permet pas d‘éditer les Produits',
			];
		}

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessAddObject() {

		$this->object = new Product();
		$data = $this->createTemplate('controllers/products/addProduct.tpl');

		$extracss = $this->pushCSS([
			_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/fancy_fileupload.css',
			_EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/css/imageuploadify.min.css',
		]);

		$_GET['addproduct'] = '';

		$pusjJs = $this->pushJS([
			_EPH_JS_DIR_ . 'jquery.tablednd.js',
			_EPH_JS_DIR_ . 'products.js',
			_EPH_JS_DIR_ . 'attributes.js',
			_EPH_JS_DIR_ . 'price.js',
			_EPH_JS_DIR_ . 'tinymce/tinymce.min.js',
			_EPH_JS_DIR_ . 'tinymce.inc.js',
			_EPH_JS_DIR_ . 'imageuploadify.min.js',
			_EPH_JS_DIR_ . 'colorpicker/colorpicker.js',
			_EPH_JS_DIR_ . 'jquery/plugins/jquery.typewatch.js',

		]);
		$scripHeader = Hook::exec('displayBackOfficeHeader', []);
		$scriptFooter = Hook::exec('displayBackOfficeFooter', []);

		$productTabs = [];
		$advancedStockManagementActive = Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT');

		foreach ($this->available_tabs as $productTab => $value) {

			if ($advancedStockManagementActive == 0 && $productTab == 'Warehouses') {
				continue;
			}

			if (in_array($productTab, $this->exclude_tabs)) {
				continue;
			}

			if (strpos($productTab, 'Module') !== false) {
				continue;
			}

			$this->tab_display = $productTab;

			$this->tpl_form = 'controllers/products/' . strtolower($this->tab_display) . '.tpl';
			$content = $this->renderForm();
			$productTabs[$productTab] = [
				'id'       => $productTab,
				'selected' => (strtolower($productTab) == strtolower($this->default_tab)),
				'name'     => $this->available_tabs_lang[$productTab],
				'content'  => $content,
			];
		}

		$iso = $this->context->language->iso_code;
		$data->assign([
			'pusjJs'                    => $pusjJs,
			'extracss'                  => $extracss,
			'product_tabs'              => $productTabs,
			'product'                   => $this->object,
			'languages'                 => $this->_languages,
			'allowEmployeeFormLang'     => $this->allow_employee_form_lang,
			'id_product'                => $this->object->id,
			'id_lang_default'           => Configuration::get('EPH_LANG_DEFAULT'),
			'has_declinaisons'          => $this->object->hasAttributes(),
			'post_data'                 => json_encode($_POST),
			'save_error'                => !empty($this->errors),
			'mod_evasive'               => Tools::apacheModExists('evasive'),
			'mod_security'              => Tools::apacheModExists('security'),
			'EPH_force_friendly_product' => Configuration::get('EPH_FORCE_FRIENDLY_PRODUCT'),
			'tinymce'                   => true,
			'iso'                       => file_exists('/js/tinymce/langs/' . $iso . '.js') ? $iso : 'en',
			'path_css'                  => _THEME_CSS_DIR_,
			'ad'                        => __EPH_BASE_URI__ . basename(_EPH_ADMIN_DIR_),
			'product_type'              => (int) Tools::getValue('type_product', $this->object->getType()),
		]);

		$li = '<li id="uperAdd' . $this->controller_name . '" data-controller="AdminDashboard"><a href="#contentAdd' . $this->controller_name . '">Ajouter un Produit</a><button type="button" class="close tabdetail" data-id="uperAdd' . $this->controller_name . '"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="contentAdd' . $this->controller_name . '" class="panel col-lg-12" style="display; flow-root;">' . $scripHeader . $data->fetch() . $scriptFooter . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];

		die(Tools::jsonEncode($result));
	}

	public static function getQuantities($echo, $tr) {

		if ((int) $tr['is_virtual'] == 1 && $tr['nb_downloadable'] == 0) {
			return '&infin;';
		} else {
			return $echo;
		}

	}

	public function ajaxProcessUpdateProductPositions() {

		if ($this->tabAccess['edit'] === '1') {

			$positions = Tools::getValue('positions');
			$idObject = Tools::getvalue('idObject');
			$idCategory = Tools::getValue('idParent');
			$stopIndex = Tools::getValue('stopIndex');
			$this->movePosition($idCategory, $idObject, $stopIndex);

			foreach ($positions as $product => $position) {

				if (!$this->movePosition($idCategory, $product, $position)) {
					$this->errors[] = Tools::displayError('A problem occur with moving Product positions ');
				}

			}

			if (empty($this->errors)) {
				$this->productRequest = $this->getProductRequest();
				$result = [
					'success' => true,
					'message' => $this->l('Product position has been successfully updated.'),
				];
			} else {
				$this->errors = array_unique($this->errors);
				$result = [
					'success' => false,
					'message' => implode(PHP_EOL, $this->errors),
				];

			}

			die(Tools::jsonEncode($result));

		}

	}

	public function ajaxProcessCleanPositions() {

		$idCategory = Tools::getValue('idCategory');
		$products = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('`id_product`, `position`')
				->from('category_product')
				->where('`id_category` = ' . (int) $idCategory)
				->orderBy('`position` ASC'));

		foreach ($products as $product) {

			if (Product::existInDatabase($product['id_product'])) {
				continue;
			} else {
				Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'category_product` WHERE `id_product` =' . (int) $product['id_product']);
			}

		}

		$products = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
			(new DbQuery())
				->select('`id_product`, `position`')
				->from('category_product')
				->where('`id_category` = ' . (int) $idCategory)
				->orderBy('`position` ASC'));
		$k = 1;

		foreach ($products as $product) {

			if (!$this->movePosition($idCategory, $product['id_product'], $k)) {
				$this->errors[] = Tools::displayError('A problem occur with cleaning Product positions ');
			} else {
				$k++;
			}

		}

		if (empty($this->errors)) {

			$result = [
				'success' => true,
				'message' => $this->l('Product position has been successfully updated.'),
			];
		} else {
			$this->errors = array_unique($this->errors);
			$result = [
				'success' => false,
				'message' => implode(PHP_EOL, $this->errors),
			];

		}

		die(Tools::jsonEncode($result));

	}

	public function movePosition($idCategory, $idProduct, $k) {

		$result = Db::getInstance()->execute(
			'UPDATE `' . _DB_PREFIX_ . 'category_product`
            SET `position`= ' . $k . '
            WHERE `id_category` =' . (int) $idCategory . ' AND `id_product` = ' . $idProduct);

		if (!$result) {
			return false;
		}

		return true;

	}

	public function ajaxProcessGetCountriesOptions() {

		if (!$res = Country::getCountriesByIdShop((int) Tools::getValue('id_shop'), (int) $this->context->language->id)) {
			return;
		}

		$tpl = $this->createTemplate('specific_prices_shop_update.tpl');
		$tpl->assign(
			[
				'option_list' => $res,
				'key_id'      => 'id_country',
				'key_value'   => 'name',
			]
		);

		$this->content = $tpl->fetch();
	}

	public function ajaxProcessGetCurrenciesOptions() {

		if (!$res = Currency::getCurrenciesByIdShop((int) Tools::getValue('id_shop'))) {
			return;
		}

		$tpl = $this->createTemplate('specific_prices_shop_update.tpl');
		$tpl->assign(
			[
				'option_list' => $res,
				'key_id'      => 'id_currency',
				'key_value'   => 'name',
			]
		);

		$this->content = $tpl->fetch();
	}

	public function ajaxProcessGetGroupsOptions() {

		if (!$res = Group::getGroups((int) $this->context->language->id, (int) Tools::getValue('id_shop'))) {
			return;
		}

		$tpl = $this->createTemplate('specific_prices_shop_update.tpl');
		$tpl->assign(
			[
				'option_list' => $res,
				'key_id'      => 'id_group',
				'key_value'   => 'name',
			]
		);

		$this->content = $tpl->fetch();
	}

	public function processDeleteVirtualProduct() {

		$idProduct = (int) Tools::getValue('id_product');
		$idProductDownload = ProductDownload::getIdFromIdProduct($idProduct);

		if ($idProductDownload) {
			$productDownload = new ProductDownload($idProductDownload);

			if (!$productDownload->deleteFile()) {
				$this->errors[] = Tools::displayError('Cannot delete file.');
			} else {
				$productDownload->active = false;
				$productDownload->update();
				$this->redirect_after = static::$currentIndex . '&id_product=' . $idProduct . '&updateproduct&key_tab=VirtualProduct&conf=1&token=' . $this->token;
			}

		}

		$this->display = 'edit';
		$this->tab_display = 'VirtualProduct';
	}

	public function ajaxProcessAddAttachment() {

		if ($this->tabAccess['edit'] === '0') {
			$this->ajaxDie(json_encode(['error' => $this->l('You do not have the right permission')]));
		}

		$attachmentNames = Tools::getValue('attachment_name');
		$attachmentDescriptions = Tools::getValue('attachment_description');
		$pdfUploader = new HelperUploader('attachementUrl');
		$id_product = Tools::getValue('id_product');
		$files = $pdfUploader->process();

		if (is_array($files) && count($files)) {

			foreach ($files as $image) {

				$attachment = new Attachment();
				$ext = pathinfo($image['name'], PATHINFO_EXTENSION);
				$uniqid = sha1(microtime());
				$destinationFile = _EPH_DOWNLOAD_DIR_ . $uniqid;
				copy($image['save_path'], $destinationFile);
				$attachment->file = $uniqid;
				$attachment->mime = $image['type'];
				$attachment->file_name = $image['name'];

				foreach ($attachmentNames as $lang => $name) {
					$attachment->name[(int) $lang] = $name;
				}

				foreach ($attachmentDescriptions as $lang => $description) {
					$attachment->description[(int) $lang] = $description;
				}

				$res = $attachment->add();

				if ($res) {
					$res = $attachment->attachProduct($id_product);
					$result = [
						'success'    => true,
						'attachment' => '<option value="' . $attachment->id . '">' . $attachment->name[$this->context->language->id] . '</option>',
						'message'    => $this->l('File has been attached successfully'),
					];
				}

			}

		} else {
			$result = [
				'success' => false,
				'message' => Tools::displayError('An error occurred loading the file.'),
			];
		}

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessProcessDuplicate() {

		if (Validate::isLoadedObject($product = new Product((int) Tools::getValue('id_product')))) {
			$idProductOld = $product->id;

			if (empty($product->price) && Shop::getContext() == Shop::CONTEXT_GROUP) {
				$shops = ShopGroup::getShopsFromGroup(Shop::getContextShopGroupID());

				foreach ($shops as $shop) {

					if ($product->isAssociatedToShop($shop['id_shop'])) {
						$product_price = new Product($idProductOld, false, null, $shop['id_shop']);
						$product->price = $product_price->price;
					}

				}

			}

			unset($product->id);
			unset($product->id_product);
			$product->indexed = 0;
			$product->active = 0;

			if ($product->add()
				&& Category::duplicateProductCategories($idProductOld, $product->id)
				&& Product::duplicateSuppliers($idProductOld, $product->id)
				&& ($combinationImages = Product::duplicateAttributes($idProductOld, $product->id)) !== false
				&& GroupReduction::duplicateReduction($idProductOld, $product->id)
				&& Product::duplicateAccessories($idProductOld, $product->id)
				&& Product::duplicateFeatures($idProductOld, $product->id)
				&& Product::duplicateSpecificPrices($idProductOld, $product->id)
				&& Pack::duplicate($idProductOld, $product->id)
				&& Product::duplicateCustomizationFields($idProductOld, $product->id)
				&& Product::duplicateTags($idProductOld, $product->id)
				&& Product::duplicateDownload($idProductOld, $product->id)
				&& Product::duplicateAttachments($idProductOld, $product->id)
			) {

				if ($product->hasAttributes()) {
					Product::updateDefaultAttribute($product->id);
				} else {
					// Set stock quantity
					$quantityAttributeOld = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
						(new DbQuery())
							->select('`quantity`')
							->from('stock_available')
							->where('`id_product` = ' . (int) $idProductOld)
							->where('`id_product_attribute` = 0')
					);
					StockAvailable::setQuantity((int) $product->id, 0, (int) $quantityAttributeOld);
				}

				Hook::exec('actionProductAdd', ['id_product' => (int) $product->id, 'product' => $product]);

				if (in_array($product->visibility, ['both', 'search']) && Configuration::get('EPH_SEARCH_INDEXATION')) {
					Search::indexation(false, $product->id);
				}

				$result = [
					'success' => true,
					'message' => $this->l('Product successfuly duplicated'),
				];
			} else {

				$result = [
					'success' => false,
					'message' => $this->l('An error occurred while creating an object.'),
				];
			}

		}

		die(Tools::jsonEncode($result));

	}

	public function processDuplicate() {

		$file = fopen("testprocessDuplicate.txt", "w");

		if (Validate::isLoadedObject($product = new Product((int) Tools::getValue('id_product')))) {
			$idProductOld = $product->id;
			fwrite($file, $product->id . PHP_EOL);

			if (empty($product->price) && Shop::getContext() == Shop::CONTEXT_GROUP) {
				$shops = ShopGroup::getShopsFromGroup(Shop::getContextShopGroupID());

				foreach ($shops as $shop) {

					if ($product->isAssociatedToShop($shop['id_shop'])) {
						$product_price = new Product($idProductOld, false, null, $shop['id_shop']);
						$product->price = $product_price->price;
					}

				}

			}

			unset($product->id);
			unset($product->id_product);
			$product->indexed = 0;
			$product->active = 0;
			fwrite($file, print_r($product, true));

			if ($product->add()
				&& Category::duplicateProductCategories($idProductOld, $product->id)
				&& Product::duplicateSuppliers($idProductOld, $product->id)
				&& ($combinationImages = Product::duplicateAttributes($idProductOld, $product->id)) !== false
				&& GroupReduction::duplicateReduction($idProductOld, $product->id)
				&& Product::duplicateAccessories($idProductOld, $product->id)
				&& Product::duplicateFeatures($idProductOld, $product->id)
				&& Product::duplicateSpecificPrices($idProductOld, $product->id)
				&& Pack::duplicate($idProductOld, $product->id)
				&& Product::duplicateCustomizationFields($idProductOld, $product->id)
				&& Product::duplicateTags($idProductOld, $product->id)
				&& Product::duplicateDownload($idProductOld, $product->id)
				&& Product::duplicateAttachments($idProductOld, $product->id)
			) {
				fwrite($file, "Product Add");

				if ($product->hasAttributes()) {
					Product::updateDefaultAttribute($product->id);
				} else {
					// Set stock quantity
					$quantityAttributeOld = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
						(new DbQuery())
							->select('`quantity`')
							->from('stock_available')
							->where('`id_product` = ' . (int) $idProductOld)
							->where('`id_product_attribute` = 0')
					);
					StockAvailable::setQuantity((int) $product->id, 0, (int) $quantityAttributeOld);
				}

				if (!Tools::getValue('noimage') && !Image::duplicateProductImages($idProductOld, $product->id, $combinationImages)) {
					$this->errors[] = Tools::displayError('An error occurred while copying images.');
				} else {
					Hook::exec('actionProductAdd', ['id_product' => (int) $product->id, 'product' => $product]);

					if (in_array($product->visibility, ['both', 'search']) && Configuration::get('EPH_SEARCH_INDEXATION')) {
						Search::indexation(false, $product->id);
					}

					$this->redirect_after = static::$currentIndex . (Tools::getIsset('id_category') ? '&id_category=' . (int) Tools::getValue('id_category') : '') . '&conf=19&token=' . $this->token;
				}

			} else {
				$this->errors[] = Tools::displayError('An error occurred while creating an object.');
			}

		}

	}

	public function processDelete() {

		if (Validate::isLoadedObject($object = $this->loadObject()) && isset($this->fieldImageSettings)) {
			/** @var Product $object */
			// check if request at least one object with noZeroObject

			if (isset($object->noZeroObject) && count($taxes = call_user_func([$this->className, $object->noZeroObject])) <= 1) {
				$this->errors[] = Tools::displayError('You need at least one object.') . ' <b>' . $this->table . '</b><br />' . Tools::displayError('You cannot delete all of the items.');
			} else {
				/*
					                                     * @since 1.5.0
					                                     * It is NOT possible to delete a product if there is/are currently:
					                                     * - a physical stock for this product
					                                     * - supply order(s) for this product
				*/

				if (Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT') && $object->advanced_stock_management) {
					$stockManager = StockManagerFactory::getManager();
					$physicalQuantity = $stockManager->getProductPhysicalQuantities($object->id, 0);
					$realQuantity = $stockManager->getProductRealQuantities($object->id, 0);

					if ($physicalQuantity > 0 || $realQuantity > $physicalQuantity) {
						$this->errors[] = Tools::displayError('You cannot delete this product because there is physical stock left.');
					}

				}

				if (!count($this->errors)) {

					if ($object->delete()) {
						$idCategory = (int) Tools::getValue('id_category');
						$categoryUrl = empty($idCategory) ? '' : '&id_category=' . (int) $idCategory;
						Logger::addLog(sprintf($this->l('%s deletion', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int) $object->id, true, (int) $this->context->employee->id);
						$this->redirect_after = static::$currentIndex . '&conf=1&token=' . $this->token . $categoryUrl;
					} else {
						$this->errors[] = Tools::displayError('An error occurred during deletion.');
					}

				}

			}

		} else {
			$this->errors[] = Tools::displayError('An error occurred while deleting the object.') . ' <b>' . $this->table . '</b> ' . Tools::displayError('(cannot load object)');
		}

	}

	protected function loadObject($opt = false) {

		$result = parent::loadObject($opt);

		if ($result && Validate::isLoadedObject($this->object)) {

			if (Shop::getContext() == Shop::CONTEXT_SHOP && Shop::isFeatureActive() && !$this->object->isAssociatedToShop()) {
				$defaultProduct = new Product((int) $this->object->id, false, null, (int) $this->object->id_shop_default);
				$def = ObjectModel::getDefinition($this->object);

				foreach ($def['fields'] as $field_name => $row) {

					if (is_array($defaultProduct->$field_name)) {

						foreach ($defaultProduct->$field_name as $key => $value) {
							$this->object->{$field_name}

							[$key] = $value;
						}

					} else {
						$this->object->$field_name = $defaultProduct->$field_name;
					}

				}

			}

			$this->object->loadStockData();
		}

		return $result;
	}

	public function processPricesModification() {

		$idSpecificPrices = Tools::getValue('spm_id_specific_price');
		$idCombinations = Tools::getValue('spm_id_product_attribute');
		$idShops = Tools::getValue('spm_id_shop');
		$idCurrencies = Tools::getValue('spm_id_currency');
		$idCountries = Tools::getValue('spm_id_country');
		$idGroups = Tools::getValue('spm_id_group');
		$idCustomers = Tools::getValue('spm_id_customer');
		$prices = Tools::getValue('spm_price');
		$fromQuantities = Tools::getValue('spm_from_quantity');
		$reductions = Tools::getValue('spm_reduction');
		$reductionTypes = Tools::getValue('spm_reduction_type');
		$froms = Tools::getValue('spm_from');
		$tos = Tools::getValue('spm_to');

		foreach ($idSpecificPrices as $key => $idSpecificPrice) {

			if ($reductionTypes[$key] == 'percentage' && ((float) $reductions[$key] <= 0 || (float) $reductions[$key] > 100)) {
				$this->errors[] = Tools::displayError('Submitted reduction value (0-100) is out-of-range');
			} else

			if ($this->_validateSpecificPrice($idShops[$key], $idCurrencies[$key], $idCountries[$key], $idGroups[$key], $idCustomers[$key], $prices[$key], $fromQuantities[$key], $reductions[$key], $reductionTypes[$key], $froms[$key], $tos[$key], $idCombinations[$key])) {
				$specificPrice = new SpecificPrice((int) ($idSpecificPrice));
				$specificPrice->id_shop = (int) $idShops[$key];
				$specificPrice->id_product_attribute = (int) $idCombinations[$key];
				$specificPrice->id_currency = (int) ($idCurrencies[$key]);
				$specificPrice->id_country = (int) ($idCountries[$key]);
				$specificPrice->id_group = (int) ($idGroups[$key]);
				$specificPrice->id_customer = (int) $idCustomers[$key];
				$specificPrice->price = (float) ($prices[$key]);
				$specificPrice->from_quantity = (int) ($fromQuantities[$key]);
				$specificPrice->reduction = (float) ($reductionTypes[$key] == 'percentage' ? ($reductions[$key] / 100) : $reductions[$key]);
				$specificPrice->reduction_type = !$reductions[$key] ? 'amount' : $reductionTypes[$key];
				$specificPrice->from = !$froms[$key] ? '0000-00-00 00:00:00' : $froms[$key];
				$specificPrice->to = !$tos[$key] ? '0000-00-00 00:00:00' : $tos[$key];

				if (!$specificPrice->update()) {
					$this->errors[] = Tools::displayError('An error occurred while updating the specific price.');
				}

			}

		}

		if (!count($this->errors)) {
			$this->redirect_after = static::$currentIndex . '&id_product=' . (int) (Tools::getValue('id_product')) . (Tools::getIsset('id_category') ? '&id_category=' . (int) Tools::getValue('id_category') : '') . '&update' . $this->table . '&action=Prices&token=' . $this->token;
		}

	}

	protected function _validateSpecificPrice($idShop, $idCurrency, $idCountry, $idGroup, $idCustomer, $price, $fromQuantity, $reduction, $reductionType, $from, $to, $idCombination = 0) {

		if (!Validate::isUnsignedId($idShop) || !Validate::isUnsignedId($idCurrency) || !Validate::isUnsignedId($idCountry) || !Validate::isUnsignedId($idGroup) || !Validate::isUnsignedId($idCustomer)) {
			$this->errors[] = Tools::displayError('Wrong IDs');
		} else

		if ((!isset($price) && !isset($reduction)) || (isset($price) && !Validate::isNegativePrice($price)) || (isset($reduction) && !Validate::isPrice($reduction))) {
			$this->errors[] = Tools::displayError('Invalid price/discount amount');
		} else

		if (!Validate::isUnsignedInt($fromQuantity)) {
			$this->errors[] = Tools::displayError('Invalid quantity');
		} else

		if ($reduction && !Validate::isReductionType($reductionType)) {
			$this->errors[] = Tools::displayError('Please select a discount type (amount or percentage).');
		} else

		if ($from && $to && (!Validate::isDateFormat($from) || !Validate::isDateFormat($to))) {
			$this->errors[] = Tools::displayError('The from/to date is invalid.');
		} else

		if (SpecificPrice::exists((int) $this->object->id, $idCombination, $idShop, $idGroup, $idCountry, $idCurrency, $idCustomer, $fromQuantity, $from, $to, false)) {
			$this->errors[] = Tools::displayError('A specific price already exists for these parameters.');
		} else {
			return true;
		}

		return false;
	}

	public function processProductCustomization() {

		if (Validate::isLoadedObject($product = new Product((int) Tools::getValue('id_product')))) {

			foreach ($_POST as $field => $value) {

				if (strncmp($field, 'label_', 6) == 0 && !Validate::isLabel($value)) {
					$this->errors[] = Tools::displayError('The label fields defined are invalid.');
				}

			}

			if (empty($this->errors) && !$product->updateLabels()) {
				$this->errors[] = Tools::displayError('An error occurred while updating customization fields.');
			}

			if (empty($this->errors)) {
				$this->confirmations[] = $this->l('Update successful');
			}

		} else {
			$this->errors[] = Tools::displayError('A product must be created before adding customization.');
		}

	}

	public function processPosition() {

		/** @var Product $object */

		if (!Validate::isLoadedObject($object = $this->loadObject())) {
			$this->errors[] = Tools::displayError('An error occurred while updating the status for an object.') . ' <b>' . $this->table . '</b> ' . Tools::displayError('(cannot load object)');
		} else

		if (!$object->updatePosition((int) Tools::getValue('way'), (int) Tools::getValue('position'))) {
			$this->errors[] = Tools::displayError('Failed to update the position.');
		} else {
			$category = new Category((int) Tools::getValue('id_category'));

			if (Validate::isLoadedObject($category)) {
				Hook::exec('actionCategoryUpdate', ['category' => $category]);
			}

			$this->redirect_after = static::$currentIndex . '&' . $this->table . 'Orderby=position&' . $this->table . 'Orderway=asc&action=Customization&conf=5' . (($idCategory = (Tools::getIsset('id_category') ? (int) Tools::getValue('id_category') : '')) ? ('&id_category=' . $idCategory) : '') . '&token=' . Tools::getAdminTokenLite('AdminProducts');
		}

	}

	protected function isTabSubmitted($tabName) {

		if (!is_array($this->submitted_tabs)) {
			$this->submitted_tabs = Tools::getValue('submitted_tabs');
		}

		if (is_array($this->submitted_tabs) && in_array($tabName, $this->submitted_tabs)) {
			return true;
		}

		return false;
	}

	public function ajaxProcessDeleteProduct() {

		$product = new Product(Tools::getValue('id_product'));

		if ($product->delete()) {
			$result = [
				'success' => true,
				'message' => $this->l('Product successfuly deleted'),
			];
		} else {
			$result = [
				'success' => false,
				'message' => $this->l('A problem occur deteleting the product'),
			];
		}

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessDeleteProductAttribute() {

		if (!Combination::isFeatureActive()) {
			return;
		}

		if ($this->tabAccess['delete'] === '1') {
			$idProduct = (int) Tools::getValue('id_product');
			$idProductAttribute = (int) Tools::getValue('id_product_attribute');

			if ($idProduct && Validate::isUnsignedId($idProduct) && Validate::isLoadedObject($product = new Product($idProduct))) {

				if (($dependsOnStock = StockAvailable::dependsOnStock($idProduct)) && StockAvailable::getQuantityAvailableByProduct($idProduct, $idProductAttribute)) {
					$json = [
						'status'  => 'error',
						'message' => $this->l('It is not possible to delete a combination while it still has some quantities in the Advanced Stock Management. You must delete its stock first.'),
					];
				} else {
					$product->deleteAttributeCombination((int) $idProductAttribute);
					$product->checkDefaultAttributes();
					Tools::clearColorListCache((int) $product->id);

					if (!$product->hasAttributes()) {
						$product->cache_default_attribute = 0;
						$product->update();
					} else {
						Product::updateDefaultAttribute($idProduct);
					}

					if ($dependsOnStock && !Stock::deleteStockByIds($idProduct, $idProductAttribute)) {
						$json = [
							'status'  => 'error',
							'message' => $this->l('Error while deleting the stock'),
						];
					} else {
						$json = [
							'status'               => 'ok',
							'message'              => $this->_conf[1],
							'id_product_attribute' => (int) $idProductAttribute,
						];
					}

				}

			} else {
				$json = [
					'status'  => 'error',
					'message' => $this->l('You cannot delete this attribute.'),
				];
			}

		} else {
			$json = [
				'status'  => 'error',
				'message' => $this->l('You do not have permission to delete this.'),
			];
		}

		$this->ajaxDie(json_encode($json));
	}

	public function ajaxProcessDefaultProductAttribute() {

		if ($this->tabAccess['edit'] === '1') {

			if (!Combination::isFeatureActive()) {
				return;
			}

			if (Validate::isLoadedObject($product = new Product((int) Tools::getValue('id_product')))) {
				$product->deleteDefaultAttributes();
				$product->setDefaultAttribute((int) Tools::getValue('id_product_attribute'));
				$json = [
					'status'  => 'ok',
					'message' => $this->_conf[4],
				];
			} else {
				$json = [
					'status'  => 'error',
					'message' => $this->l('You cannot make this the default attribute.'),
				];
			}

			$this->ajaxDie(json_encode($json));
		}

	}

	public function ajaxProcessEditProductAttribute() {

		$idProduct = (int) Tools::getValue('id_product');
		$idProductAttribute = (int) Tools::getValue('id_product_attribute');

		if ($idProduct && Validate::isUnsignedId($idProduct) && Validate::isLoadedObject($product = new Product((int) $idProduct))) {

			if (!$this->default_form_language) {
				$this->getLanguages();
			}

			$data = $this->createTemplate('controllers/products/editcombination.tpl');
			$combinations = $product->getAttributeCombinationsById($idProductAttribute, $this->context->language->id);

			$declinaison = new Combination($idProductAttribute);
			$data->assign('declinaison', $declinaison);
			$data->assign('declinaisonIdAttributeGroup', $combinations['id_attribute_group']);
			$listattributes = [];
			$combinationReturn = [];
			$combinationName = '';

			foreach ($combinations as $key => $combination) {

				if ($key == 0) {

					foreach ($combination as $k => $value) {
						$combinationReturn[$k] = $value;
					}

				}

			}

			foreach ($combinations as $key => $combination) {
				$combinations[$key]['attributes'][] = [$combination['group_name'], $combination['attribute_name'], $combination['id_attribute']];
				$combinationName .= $combination['group_name'] . ':' . $combination['attribute_name'] . ' - ';
				array_push($listattributes, [
					'group_name'     => $combination['group_name'],
					'attribute_name' => $combination['attribute_name'],
					'id_attribute'   => $combination['id_attribute'],
				]);
			}

			$combinationReturn['attributes'] = $listattributes;

			$combinationName = substr($combinationName, 0, -3);
			$attributeJs = [];
			$attributes = Attributes::getAttributes($this->context->language->id, true);

			foreach ($attributes as $k => $attribute) {
				$attributeJs[$attribute['id_attribute_group']][$attribute['id_attribute']] = $attribute['name'];
			}

			foreach ($attributeJs as $k => $ajs) {
				natsort($attributeJs[$k]);
			}

			$currency = $this->context->currency;

			$data->assign('attributeJs', $attributeJs);
			$data->assign('attributes_groups', AttributeGroup::getAttributesGroups($this->context->language->id));

			$data->assign('currency', $currency);

			$images = Image::getImages($this->context->language->id, $product->id);
			$data->assign('tax_exclude_option', Tax::excludeTaxeOption());

			$i = 0;
			$type = ImageType::getByNameNType('%', 'products', 'height');

			if (isset($type['name'])) {
				$data->assign('imageType', $type['name']);
			} else {
				$data->assign('imageType', ImageType::getFormatedName('small'));
			}

			$data->assign('imageWidth', (isset($imageType['width']) ? (int) ($imageType['width']) : 64) + 25);

			foreach ($images as $k => $image) {
				$images[$k]['obj'] = new Image($image['id_image']);
				++$i;
			}

			$data->assign('images', $images);

			$data->assign($this->tpl_form_vars);
			$data->assign(
				[
					'combinations'          => $combinationReturn,
					'product'               => $product,
					'_THEME_PROD_DIR_'      => _THEME_PROD_DIR_,
					'languages'             => $this->_languages,
					'default_form_language' => $this->default_form_language,
					'id_lang'               => $this->context->language->id,
					'bo_imgdir'             => __EPH_BASE_URI__ . $this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
				]
			);

			$li = '<li id="uperEditdeclinaison" data-controller="AdminDashboard"><a href="#declinaisonEditAdminEducations">Editer la déclinaison : ' . $declinaison->name[$this->context->language->id] . '</a><button type="button" class="close tabdetail" data-id="declinaisonEditAdminEducations"><i class="icon icon-times-circle"></i></button></li>';
			$html = '<div id="declinaisonEditAdminEducations" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

			$result = [
				'li'          => $li,
				'html'        => $html,
				'combination' => $combinationReturn,
				'product'     => $product,
			];
			die(Tools::jsonEncode($result));

		}

	}

	public function ajaxPreProcess() {

		if (Tools::getIsset('update' . $this->table) && Tools::getIsset('id_' . $this->table)) {
			$this->display = 'edit';
			$this->action = Tools::getValue('action');
		}

	}

	public function buildCategoriesSelector() {

		$context = Context::getContext();
		$rootCategoryId = Category::getRootCategory()->id;
		$categoryList = Category::outputCategoriesSelect($context->language->id);

		$html = '<select id="categorySelect" class="selectmenu">';
		$html .= '<option value="">' . $this->l('--Select--') . '</option>';
		$html .= '<option value="' . $rootCategoryId . '">' . Category::getRootCategory()->name . '</option>';

		foreach ($categoryList as $categoryInformation) {
			$depth = ($categoryInformation['level_depth'] - 1);
			$html .= '<option value="' . $categoryInformation['id_category'] . '">';

			for ($i = 0; $i < $depth; $i++) {
				$html .= '-';
			}

			$html .= $categoryInformation['name'];
			$html .= '</option>';

		}

		$html .= '</select>';

		return str_replace("'", "\'", $html);
	}

	public function gridCategoriesSelector() {

		$context = Context::getContext();
		$rootCategoryId = Category::getRootCategory()->id;
		$categoryList = Category::outputCategoriesSelect($context->language->id);
		$selector = [];
		$selector[] = [
			'id_category_default' => $rootCategoryId,
			'CategoryName'        => str_replace("'", "\'", Category::getRootCategory()->name),
		];

		foreach ($categoryList as $categoryInformation) {
			$depth = ($categoryInformation['level_depth'] - 1);
			$name = '';

			for ($i = 0; $i < $depth; $i++) {
				$name .= '-';
			}

			$name .= $categoryInformation['name'];
			$selector[] = [
				'id_category_default' => (int) $categoryInformation['id_category'],
				'CategoryName'        => str_replace("'", "\'", $name),
			];

		}

		return $selector;
	}

	public function ajaxProcessgridCategoriesSelector() {

		$context = Context::getContext();
		$rootCategoryId = Category::getRootCategory()->id;
		$categoryList = Category::outputCategoriesSelect($context->language->id);
		$selector = [];
		$selector[] = [
			'id_category_default' => '\'' . $rootCategoryId . '\'',
			'CategoryName'        => str_replace("'", "\'", Category::getRootCategory()->name),
		];

		foreach ($categoryList as $categoryInformation) {
			$depth = ($categoryInformation['level_depth'] - 1);
			$name = '';

			for ($i = 0; $i < $depth; $i++) {
				$name .= '-';
			}

			$name .= $categoryInformation['name'];
			$selector[] = [
				'id_category_default' => $categoryInformation['id_category'],
				'CategoryName'        => str_replace("'", "\'", $name),
			];

		}

		die($selector);
	}

	public function ajaxProcessGetUpdateForm() {

		$dataIndx = Tools::getValue('dataIndx');

		switch ($dataIndx) {
		case 'brand':
			$select = $this->brandSelector;
			break;

		case 'Tax':
			$select = $this->taxSelector;
			break;
		case 'CategoryName':
			$select = $this->buildCategoriesSelector();
			break;
		case 'active':
			$select = $this->activeSelector;
			break;
		default:
			$select = '';
		}

		$field = $this->getUpdatableFieldType($dataIndx);

		$fieldType = $field['dataForm'];
		$data = $this->createTemplate('fieldUpdate.tpl');
		$currency = new Currency($this->context->currency->id);
		$this->context->smarty->assign([
			'selector'  => $select,
			'fieldType' => $field['dataForm'],
			'title'     => $field['titleForm'],
			'field'     => $field['title'],
			'dataIndx'  => $dataIndx,
			'currency'  => $currency,
		]);

		$return = [
			'tpl' => $data->fetch(),
		];

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessBulkUpdateProduct() {

		$fieldType = Tools::getValue('fieldType');
		$dataIndx = Tools::getValue('dataIndx');
		$category = Tools::getValue('category');
		$allCategory = Tools::getValue('allCategory');

		if ($allCategory == 1) {
			$request = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS('SELECT `id_product` FROM `' . _DB_PREFIX_ . 'category_product` WHERE `id_category` = ' . (int) $category);
			$products = [];

			foreach ($request as $result) {
				$products[] = $result['id_product'];
			}

		} else {
			$products = explode(",", Tools::getValue('products')[0]);
		}

		if ($fieldType == 'select') {
			$selectValue = Tools::getValue('selectValue');

			switch ($dataIndx) {
			case 'brand':
				$dataIndx = 'id_manufacturer';
				break;
			case 'familyName':
				$dataIndx = 'id_product_family';
				break;
			case 'Tax':
				$dataIndx = 'id_tax_rules_group';
				break;
			case 'CategoryName':
				$dataIndx = 'id_category_default';
				break;
			case 'active':
				break;

			}

			foreach ($products as $key => $product) {
				$updateProduct = new Product($product);

				if (Validate::isLoadedObject($updateProduct)) {
					$updateProduct->$dataIndx = $selectValue;

					if (!$updateProduct->update()) {
						$this->errors[] = Tools::displayError('An error happen updating product.');
					}

				} else {
					$this->errors[] = Tools::displayError('An error happen to load the product.');
				}

			}

		} else {
			$updateBehavior = Tools::getValue('updateBehavior');
			$updateType = Tools::getValue('updateType');
			$amount = Tools::getValue('amount');

			if ($updateType == 'percent') {

				if ($updateBehavior == 'increase') {
					$coef = 1 + $amount / 100;
				} else

				if ($updateBehavior == 'decrease') {
					$coef = 1 - $amount / 100;
				}

				foreach ($products as $key => $product) {
					$updateProduct = new Product($product);

					if (Validate::isLoadedObject($updateProduct)) {
						$updateProduct->$dataIndx = $updateProduct->$dataIndx * $coef;

						if (!$updateProduct->update()) {
							$this->errors[] = Tools::displayError('An error happen updating product.');
						}

					} else {
						$this->errors[] = Tools::displayError('An error happen to load the product.');
					}

				}

			} else {

				if ($updateBehavior == 'decrease') {
					$amount = -$amount;
				}

				foreach ($products as $key => $product) {
					$updateProduct = new Product($product);

					if (Validate::isLoadedObject($updateProduct)) {
						$updateProduct->$dataIndx = $updateProduct->$dataIndx + $amount;

						if (!$updateProduct->update()) {
							$this->errors[] = Tools::displayError('An error happen updating product.');
						}

					} else {
						$this->errors[] = Tools::displayError('An error happen to load the product.');

					}

				}

			}

		}

		if (count($this->errors)) {
			$result = [
				'success' => false,
				'message' => implode(PHP_EOL, $this->errors),
			];
		} else {
			$result = [
				'success' => true,
				'message' => $this->l('The selection has been successfully updated'),
			];
		}

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessAddProductImage() {

		$id_product = Tools::getValue('id_product');

		$product = new Product($id_product);

		$legends = $product->name[$this->context->language->id];

		if (!Validate::isLoadedObject($product)) {

			$files = [];
			$files[0]['error'] = Tools::displayError('Cannot add image because product creation failed.');
		}

		$imageUploader = new HelperImageUploader('newImageUrl');
		$imageUploader->setAcceptTypes(['jpeg', 'gif', 'png', 'jpg'])->setMaxSize($this->max_image_size);
		$files = $imageUploader->process();

		foreach ($files as &$file) {

			$image = new Image();
			$image->id_product = (int) ($product->id);
			$image->position = Image::getHighestPosition($product->id) + 1;

			foreach (Language::getIDs(false) as $idLang) {

				$image->legend[(int) $idLang] = $legends;
			}

			if (!Image::getCover($image->id_product)) {
				$image->cover = 1;
			} else {
				$image->cover = 0;
			}

			if (isset($file['error']) && (!is_numeric($file['error']) || $file['error'] != 0)) {
				continue;
			}

			if (!$image->add()) {
				$file['error'] = Tools::displayError('Error while creating additional image');
			} else {

				if (!$newPath = $image->getPathForCreation()) {

					$file['error'] = Tools::displayError('An error occurred during new folder creation');
					continue;
				}

				$error = 0;

				if (!ImageManager::resize($file['save_path'], $newPath . '.' . $image->image_format, null, null, 'jpg', false, $error)) {

					switch ($error) {
					case ImageManager::ERROR_FILE_NOT_EXIST:
						$file['error'] = Tools::displayError('An error occurred while copying image, the file does not exist anymore.');
						break;

					case ImageManager::ERROR_FILE_WIDTH:
						$file['error'] = Tools::displayError('An error occurred while copying image, the file width is 0px.');
						break;

					case ImageManager::ERROR_MEMORY_LIMIT:
						$file['error'] = Tools::displayError('An error occurred while copying image, check your memory limit.');
						break;

					default:
						$file['error'] = Tools::displayError('An error occurred while copying image.');
						break;
					}

					continue;
				} else {
					$imagesTypes = ImageType::getImagesTypes('products');
					$generateHighDpiImages = (bool) Configuration::get('EPH_HIGHT_DPI');

					foreach ($imagesTypes as $imageType) {

						if (!ImageManager::resize($file['save_path'], $newPath . '-' . stripslashes($imageType['name']) . '.' . $image->image_format, $imageType['width'], $imageType['height'], $image->image_format)) {
							$file['error'] = Tools::displayError('An error occurred while copying image:') . ' ' . stripslashes($imageType['name']);
							continue;
						}

						if ($generateHighDpiImages) {

							if (!ImageManager::resize($file['save_path'], $newPath . '-' . stripslashes($imageType['name']) . '2x.' . $image->image_format, (int) $imageType['width'] * 2, (int) $imageType['height'] * 2, $image->image_format)) {
								$file['error'] = Tools::displayError('An error occurred while copying image:') . ' ' . stripslashes($imageType['name']);
								continue;
							}

						}

					}

				}

				unlink($file['save_path']);
				unset($file['save_path']);

				if (!$image->update()) {
					$file['error'] = Tools::displayError('Error while updating status');
					continue;
				}

			}

		}

		$result = [
			'success' => true,
		];
		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessDeleteProductImage() {

		$this->display = 'content';
		$res = true;
		/* Delete product image */
		$image = new Image((int) Tools::getValue('id_image'));
		$this->content['id'] = $image->id;
		$res &= $image->delete();
		// if deleted image was the cover, change it to the first one

		if (!Image::getCover($image->id_product)) {
			$res &= Db::getInstance()->execute(
				'
            UPDATE `' . _DB_PREFIX_ . 'image` image
            SET image.`cover` = 1
            WHERE image.`id_product` = ' . (int) $image->id_product . '
            LIMIT 1'
			);
		}

		if (!Image::getGlobalCover($image->id_product)) {
			$res &= Db::getInstance()->execute(
				'
            UPDATE `' . _DB_PREFIX_ . 'image` i
            SET i.`cover` = 1
            WHERE i.`id_product` = ' . (int) $image->id_product . ' LIMIT 1'
			);
		}

		if (file_exists(_EPH_TMP_IMG_DIR_ . 'product_' . $image->id_product . '.jpg')) {
			$res &= @unlink(_EPH_TMP_IMG_DIR_ . 'product_' . $image->id_product . '.jpg');
		}

		if (file_exists(_EPH_TMP_IMG_DIR_ . 'product_mini_' . $image->id_product . '_' . $this->context->shop->id . '.jpg')) {
			$res &= @unlink(_EPH_TMP_IMG_DIR_ . 'product_mini_' . $image->id_product . '_' . $this->context->shop->id . '.jpg');
		}

		if ($res) {
			$this->jsonConfirmation($this->_conf[7]);
		} else {
			$this->jsonError(Tools::displayError('An error occurred while attempting to delete the product image.'));
		}

	}

	protected function copyFromPost(&$object, $table) {

		parent::copyFromPost($object, $table);

		if (get_class($object) != 'Product') {
			return;
		}

		/* Additional fields */

		foreach (Language::getIDs(false) as $idLang) {

			if (isset($_POST['meta_keywords_' . $idLang])) {
				$_POST['meta_keywords_' . $idLang] = $this->_cleanMetaKeywords(mb_strtolower($_POST['meta_keywords_' . $idLang]));
				// preg_replace('/ *,? +,* /', ',', strtolower($_POST['meta_keywords_'.$id_lang]));
				$object->meta_keywords[$idLang] = $_POST['meta_keywords_' . $idLang];
			}

		}

		$_POST['width'] = empty($_POST['width']) ? '0' : str_replace(',', '.', $_POST['width']);
		$_POST['height'] = empty($_POST['height']) ? '0' : str_replace(',', '.', $_POST['height']);
		$_POST['depth'] = empty($_POST['depth']) ? '0' : str_replace(',', '.', $_POST['depth']);
		$_POST['weight'] = empty($_POST['weight']) ? '0' : str_replace(',', '.', $_POST['weight']);

		if (Tools::getIsset('unit_price') != null) {
			$object->unit_price = str_replace(',', '.', Tools::getValue('unit_price'));
		}

		if (Tools::getIsset('ecotax') != null) {
			$object->ecotax = str_replace(',', '.', Tools::getValue('ecotax'));
		}

		if ($this->isTabSubmitted('Informations')) {

			if ($this->checkMultishopBox('available_for_order', $this->context)) {
				$object->available_for_order = (int) Tools::getValue('available_for_order');
			}

			if ($this->checkMultishopBox('show_price', $this->context)) {
				$object->show_price = $object->available_for_order ? 1 : (int) Tools::getValue('show_price');
			}

			if ($this->checkMultishopBox('online_only', $this->context)) {
				$object->online_only = (int) Tools::getValue('online_only');
			}

		}

		if ($this->isTabSubmitted('Prices')) {
			$object->on_sale = (int) Tools::getValue('on_sale');
		}

	}

	protected function _cleanMetaKeywords($keywords) {

		if (!empty($keywords) && $keywords != '') {
			$out = [];
			$words = explode(',', $keywords);

			foreach ($words as $wordItem) {
				$wordItem = trim($wordItem);

				if (!empty($wordItem) && $wordItem != '') {
					$out[] = $wordItem;
				}

			}

			return ((count($out) > 0) ? implode(',', $out) : '');
		} else {
			return '';
		}

	}

	public function checkMultishopBox($field, $context = null) {

		static $checkbox = null;
		static $shopContext = null;

		if ($context == null && $shopContext == null) {
			$context = $this->context;
		}

		if ($shopContext == null) {
			$shopContext = $context->shop->getContext();
		}

		if ($checkbox == null) {
			$checkbox = Tools::getValue('multishop_check', []);
		}

		if ($shopContext == Shop::CONTEXT_SHOP) {
			return true;
		}

		if (isset($checkbox[$field]) && $checkbox[$field] == 1) {
			return true;
		}

		return false;
	}

	/**
	 * Copy a product image
	 *
	 * @param int    $idProduct Product Id for product image filename
	 * @param int    $idImage   Image Id for product image filename
	 * @param string $method
	 *
	 * @return void|false
	 * @throws PhenyxShopException
	 */
	public function copyImage($idProduct, $idImage, $method = 'auto') {

		if (!isset($_FILES['image_product']['tmp_name'])) {
			return false;
		}

		if ($error = ImageManager::validateUpload($_FILES['image_product'])) {
			$this->errors[] = $error;
		} else {
			$highDpi = (bool) Configuration::get('EPH_HIGHT_DPI');

			$image = new Image($idImage);

			if (!$newPath = $image->getPathForCreation()) {
				$this->errors[] = Tools::displayError('An error occurred while attempting to create a new folder.');
			}

			if (!($tmpName = tempnam(_EPH_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES['image_product']['tmp_name'], $tmpName)) {
				$this->errors[] = Tools::displayError('An error occurred during the image upload process.');
			} else

			if (!ImageManager::resize($tmpName, $newPath . '.' . $image->image_format)) {
				$this->errors[] = Tools::displayError('An error occurred while copying the image.');
			} else

			if ($method == 'auto') {
				$imagesTypes = ImageType::getImagesTypes('products');

				foreach ($imagesTypes as $k => $imageType) {

					if (!ImageManager::resize(
						$tmpName,
						$newPath . '-' . stripslashes($imageType['name']) . '.' . $image->image_format,
						(int) $imageType['width'],
						(int) $imageType['height'],
						$image->image_format
					)) {
						$this->errors[] = Tools::displayError('An error occurred while copying this image:') . ' ' . stripslashes($imageType['name']);
					} else {

						if ($highDpi) {
							ImageManager::resize(
								$tmpName,
								$newPath . '-' . stripslashes($imageType['name']) . '2x.' . $image->image_format,
								(int) $imageType['width'] * 2,
								(int) $imageType['height'] * 2,
								$image->image_format
							);
						}

						if (ImageManager::webpSupport()) {
							ImageManager::resize(
								$tmpName,
								$newPath . '-' . stripslashes($imageType['name']) . '.webp',
								(int) $imageType['width'],
								(int) $imageType['height'],
								'webp'
							);

							if ($highDpi) {
								ImageManager::resize(
									$tmpName,
									$newPath . '-' . stripslashes($imageType['name']) . '2x.webp',
									(int) $imageType['width'] * 2,
									(int) $imageType['height'] * 2,
									'webp'
								);
							}

						}

						if ((int) Configuration::get('EPH_IMAGES_LAST_UPD_PRODUCTS') < $idProduct) {
							Configuration::updateValue('EPH_IMAGES_LAST_UPD_PRODUCTS', $idProduct);
						}

					}

				}

			}

			@unlink($tmpName);
			Hook::exec('actionWatermark', ['id_image' => $idImage, 'id_product' => $idProduct]);
		}

	}

	/**
	 * Check that a saved product is valid
	 */
	public function checkProduct() {

		$className = 'Product';
		// @todo : the call_user_func seems to contains only statics values (className = 'Product')
		$rules = call_user_func([$this->className, 'getValidationRules'], $this->className);
		$defaultLanguage = new Language((int) Configuration::get('EPH_LANG_DEFAULT'));
		$languages = Language::getLanguages(false);

		// Check required fields

		foreach ($rules['required'] as $field) {

			if (!$this->isProductFieldUpdated($field)) {
				continue;
			}

			if (($value = Tools::getValue($field)) == false && $value != '0') {

				if (Tools::getValue('id_' . $this->table) && $field == 'passwd') {
					continue;
				}

				$this->errors[] = sprintf(
					Tools::displayError('The %s field is required.'),
					call_user_func([$className, 'displayFieldName'], $field, $className)
				);
			}

		}

		// Check multilingual required fields

		foreach ($rules['requiredLang'] as $fieldLang) {

			if ($this->isProductFieldUpdated($fieldLang, $defaultLanguage->id) && !Tools::getValue($fieldLang . '_' . $defaultLanguage->id)) {
				$this->errors[] = sprintf(
					Tools::displayError('This %1$s field is required at least in %2$s'),
					call_user_func([$className, 'displayFieldName'], $fieldLang, $className),
					$defaultLanguage->name
				);
			}

		}

		// Check fields sizes

		foreach ($rules['size'] as $field => $maxLength) {

			if ($this->isProductFieldUpdated($field) && ($value = Tools::getValue($field)) && mb_strlen($value) > $maxLength) {
				$this->errors[] = sprintf(
					Tools::displayError('The %1$s field is too long (%2$d chars max).'),
					call_user_func([$className, 'displayFieldName'], $field, $className),
					$maxLength
				);
			}

		}

		if (Tools::getIsset('description_short') && $this->isProductFieldUpdated('description_short')) {
			$saveShort = Tools::getValue('description_short');
			$_POST['description_short'] = strip_tags(Tools::getValue('description_short'));
		}

		// Check description short size without html
		$limit = (int) Configuration::get('EPH_PRODUCT_SHORT_DESC_LIMIT');

		if ($limit <= 0) {
			$limit = 400;
		}

		foreach ($languages as $language) {

			if ($this->isProductFieldUpdated('description_short', $language['id_lang']) && ($value = Tools::getValue('description_short_' . $language['id_lang']))) {

				if (mb_strlen(strip_tags($value)) > $limit) {
					$this->errors[] = sprintf(
						Tools::displayError('This %1$s field (%2$s) is too long: %3$d chars max (current count %4$d).'),
						call_user_func([$className, 'displayFieldName'], 'description_short'),
						$language['name'],
						$limit,
						mb_strlen(strip_tags($value))
					);
				}

			}

		}

		// Check multilingual fields sizes

		foreach ($rules['sizeLang'] as $fieldLang => $maxLength) {

			foreach ($languages as $language) {
				$value = Tools::getValue($fieldLang . '_' . $language['id_lang']);

				if ($value && mb_strlen($value) > $maxLength) {
					$this->errors[] = sprintf(
						Tools::displayError('The %1$s field is too long (%2$d chars max).'),
						call_user_func([$className, 'displayFieldName'], $fieldLang, $className),
						$maxLength
					);
				}

			}

		}

		if ($this->isProductFieldUpdated('description_short') && isset($_POST['description_short'])) {
			$_POST['description_short'] = $saveShort;
		}

		// Check fields validity

		foreach ($rules['validate'] as $field => $function) {

			if ($this->isProductFieldUpdated($field) && ($value = Tools::getValue($field))) {
				$res = true;

				if (mb_strtolower($function) == 'iscleanhtml') {

					if (!Validate::$function($value, (int) Configuration::get('EPH_ALLOW_HTML_IFRAME'))) {
						$res = false;
					}

				} else

				if (!Validate::$function($value)) {
					$res = false;
				}

				if (!$res) {
					$this->errors[] = sprintf(
						Tools::displayError('The %s field is invalid.'),
						call_user_func([$className, 'displayFieldName'], $field, $className)
					);
				}

			}

		}

		// Check multilingual fields validity

		foreach ($rules['validateLang'] as $fieldLang => $function) {

			foreach ($languages as $language) {

				if ($this->isProductFieldUpdated($fieldLang, $language['id_lang']) && ($value = Tools::getValue($fieldLang . '_' . $language['id_lang']))) {

					if (!Validate::$function($value, (int) Configuration::get('EPH_ALLOW_HTML_IFRAME'))) {
						$this->errors[] = sprintf(
							Tools::displayError('The %1$s field (%2$s) is invalid.'),
							call_user_func([$className, 'displayFieldName'], $fieldLang, $className),
							$language['name']
						);
					}

				}

			}

		}

		// Categories

		if ($this->isProductFieldUpdated('id_category_default') && (!Tools::isSubmit('categoryBox') || !count(Tools::getValue('categoryBox')))) {
			$this->errors[] = $this->l('Products must be in at least one category.');
		}

		if ($this->isProductFieldUpdated('id_category_default') && (!is_array(Tools::getValue('categoryBox')) || !in_array(Tools::getValue('id_category_default'), Tools::getValue('categoryBox')))) {
			$this->errors[] = $this->l('This product must be in the default category.');
		}

		// Tags

		foreach ($languages as $language) {

			if ($value = Tools::getValue('tags_' . $language['id_lang'])) {

				if (!Validate::isTagsList($value)) {
					$this->errors[] = sprintf(
						Tools::displayError('The tags list (%s) is invalid.'),
						$language['name']
					);
				}

			}

		}

	}

	/**
	 * Check if a field is edited (if the checkbox is checked)
	 * This method will do something only for multishop with a context all / group
	 *
	 * @param string $field Name of field
	 * @param int    $idLang
	 *
	 * @return bool
	 */
	protected function isProductFieldUpdated($field, $idLang = null) {

		// Cache this condition to improve performances
		static $isActivated = null;

		if (is_null($isActivated)) {
			$isActivated = Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP && $this->id_object;
		}

		if (!$isActivated) {
			return true;
		}

		$def = ObjectModel::getDefinition($this->object);

		if (!$this->object->isMultiShopField($field) && is_null($idLang) && isset($def['fields'][$field])) {
			return true;
		}

		if (is_null($idLang)) {
			return !empty($_POST['multishop_check'][$field]);
		} else {
			return !empty($_POST['multishop_check'][$field][$idLang]);
		}

	}

	/**
	 * Checking customs feature
	 *
	 * @since 1.9.1.0
	 */
	protected function _removeTaxFromEcotax() {

		if ($ecotax = Tools::getValue('ecotax')) {
			$_POST['ecotax'] = Tools::EPH_round($ecotax / (1 + Tax::getProductEcotaxRate() / 100), 6);
		}

	}

	protected function addCarriers($product = null) {

		if (!isset($product)) {
			$product = new Product((int) Tools::getValue('id_product'));
		}

		if (Validate::isLoadedObject($product)) {
			$carriers = [];

			if (Tools::getValue('selectedCarriers')) {
				$carriers = Tools::getValue('selectedCarriers');
			}

			$product->setCarriers($carriers);
		}

	}

	/**
	 * Update product accessories
	 *
	 * @param object $product Product
	 *
	 * @since 1.9.1.0
	 */
	public function updateAccessories($product) {

		$product->deleteAccessories();

		if ($accessories = Tools::getValue('inputAccessories')) {
			$accessoriesId = array_unique(explode('-', $accessories));

			if (count($accessoriesId)) {
				array_pop($accessoriesId);
				$product->changeAccessories($accessoriesId);
			}

		}

	}

	/**
	 * delete all items in pack, then check if type_product value is PTYPE_PACK.
	 * if yes, add the pack items from input "inputPackItems"
	 *
	 * @param Product $product
	 *
	 * @return bool
	 */
	public function updatePackItems($product) {

		Pack::deleteItems($product->id);
		// lines format: QTY x ID-QTY x ID

		if (Tools::getValue('type_product') == Product::PTYPE_PACK) {
			$product->setDefaultAttribute(0); //reset cache_default_attribute
			$items = Tools::getValue('inputPackItems');
			$lines = array_unique(explode('-', $items));

			// lines is an array of string with format : QTYxIDxID_PRODUCT_ATTRIBUTE

			if (count($lines)) {

				foreach ($lines as $line) {

					if (!empty($line)) {
						$itemIdAttribute = 0;
						count($array = explode('x', $line)) == 3 ? list($qty, $itemId, $itemIdAttribute) = $array : list($qty, $itemId) = $array;

						if ($qty > 0 && isset($itemId)) {

							if (Pack::isPack((int) $itemId || $product->id == (int) $itemId)) {
								$this->errors[] = Tools::displayError('You can\'t add product packs into a pack');
							} else

							if (!Pack::addItem((int) $product->id, (int) $itemId, (int) $qty, (int) $itemIdAttribute)) {
								$this->errors[] = Tools::displayError('An error occurred while attempting to add products to the pack.');
							}

						}

					}

				}

			}

		}

	}

	/**
	 * Update product download
	 *
	 * @param Product $product
	 * @param int     $edit     Deprecated in favor of autodetection.
	 *
	 * @return bool
	 *
	 * @since 1.0.3 Deprecate $edit in favor of autodetection.
	 * @since 1.9.1.0
	 */
	public function updateDownloadProduct($product, $edit = 999) {

		if ($edit !== 999) {
			Tools::displayParameterAsDeprecated('edit');
		}

		$idProductDownload = ProductDownload::getIdFromIdProduct($product->id, false);

		if (!$idProductDownload) {
			$idProductDownload = (int) Tools::getValue('virtual_product_id');
		}

		if (Tools::getValue('type_product') == Product::PTYPE_VIRTUAL
			&& Tools::getValue('is_virtual_file') == 1) {

			if (isset($_FILES['virtual_product_file_uploader']) && $_FILES['virtual_product_file_uploader']['size'] > 0) {
				$filename = ProductDownload::getNewFilename();
				$helper = new HelperUploader('virtual_product_file_uploader');
				$helper->setPostMaxSize(Tools::getOctets(ini_get('upload_max_filesize')))
					->setSavePath(_EPH_DOWNLOAD_DIR_)->upload($_FILES['virtual_product_file_uploader'], $filename);
			} else {
				$filename = Tools::getValue('virtual_product_filename', ProductDownload::getNewFilename());
			}

			$product->setDefaultAttribute(0); //reset cache_default_attribute

			$active = Tools::getValue('virtual_product_active');
			$isShareable = Tools::getValue('virtual_product_is_shareable');
			$name = Tools::getValue('virtual_product_name');
			$nbDays = Tools::getValue('virtual_product_nb_days');
			$nbDownloable = Tools::getValue('virtual_product_nb_downloable');
			$expirationDate = Tools::getValue('virtual_product_expiration_date');
			// This whould allow precision up to the second, not supported by
			// the datepicker in the GUI, yet.
			//if ($expirationDate
			//    && !preg_match('/\d{1,2}\:\d{1,2}/', $expirationDate)) {
			//    // No time given should mean the end of the day.
			//    $dateExpiration .= ' 23:59:59';
			//}

			if ($expirationDate) {
				// We want the end of the given day.
				$expirationDate .= ' 23:59:59';
			}

			$download = new ProductDownload($idProductDownload);
			$download->id_product = (int) $product->id;
			$download->display_filename = $name;
			$download->filename = $filename;
			$download->date_expiration = $expirationDate;
			$download->nb_days_accessible = (int) $nbDays;
			$download->nb_downloadable = (int) $nbDownloable;
			$download->active = (int) $active;
			$download->is_shareable = (int) $isShareable;

			if ($download->save()) {
				return true;
			}

		} else {
			// Delete the download and its file.

			if ($idProductDownload) {
				$productDownload = new ProductDownload($idProductDownload);

				return $productDownload->delete();
			}

		}

		return false;
	}

	/**
	 * Update product tags
	 *
	 * @param array  $languages Array languages
	 * @param object $product   Product
	 *
	 * @return bool Update result
	 *
	 * @since 1.9.1.0
	 */
	public function updateTags($languages, $product) {

		$tagSuccess = true;
		/* Reset all tags for THIS product */

		if (!Tag::deleteTagsForProduct((int) $product->id)) {
			$this->errors[] = Tools::displayError('An error occurred while attempting to delete previous tags.');
		}

		/* Assign tags to this product */

		foreach ($languages as $language) {

			if ($value = Tools::getValue('tags_' . $language['id_lang'])) {
				$tagSuccess &= Tag::addTags($language['id_lang'], (int) $product->id, $value);
			}

		}

		if (!$tagSuccess) {
			$this->errors[] = Tools::displayError('An error occurred while adding tags.');
		}

		return $tagSuccess;
	}

	/**
	 * @param Product $product
	 *
	 * @return bool|string
	 *
	 * @since 1.9.1.0
	 */
	public function getPreviewUrl(Product $product) {

		$idLang = Configuration::get('EPH_LANG_DEFAULT', null, null, $this->context->shop->id);

		if (!Validate::isLoadedObject($product) || !$product->id_category_default) {
			return $this->l('Unable to determine the preview URL. This product has not been linked with a category, yet.');
		}

		if (!ShopUrl::getMainShopDomain()) {
			return false;
		}

		$isRewriteActive = (bool) Configuration::get('EPH_REWRITING_SETTINGS');
		$previewUrl = $this->context->link->getProductLink(
			$product,
			$this->getFieldValue($product, 'link_rewrite', $this->context->language->id),
			Category::getLinkRewrite($this->getFieldValue($product, 'id_category_default'), $this->context->language->id),
			null,
			$idLang,
			(int) $this->context->shop->id,
			0,
			$isRewriteActive
		);

		if (!$product->active) {
			$adminDir = dirname($_SERVER['PHP_SELF']);
			$adminDir = substr($adminDir, strrpos($adminDir, '/') + 1);
			$previewUrl .= ((strpos($previewUrl, '?') === false) ? '?' : '&') . 'adtoken=' . $this->token . '&ad=' . $adminDir . '&id_employee=' . (int) $this->context->employee->id;
		}

		return $previewUrl;
	}

	/**
	 * @return bool|false|ObjectModel
	 *
	 * @since 1.9.1.0
	 */
	public function processStatus() {

		$this->loadObject(true);

		if (!Validate::isLoadedObject($this->object)) {
			return false;
		}

		if (($error = $this->object->validateFields(false, true)) !== true) {
			$this->errors[] = $error;
		}

		if (($error = $this->object->validateFieldsLang(false, true)) !== true) {
			$this->errors[] = $error;
		}

		if (count($this->errors)) {
			return false;
		}

		$res = parent::processStatus();

		$query = trim(Tools::getValue('bo_query'));
		$searchType = (int) Tools::getValue('bo_search_type');

		if ($query) {
			$this->redirect_after = preg_replace('/[\?|&](bo_query|bo_search_type)=([^&]*)/i', '', $this->redirect_after);
			$this->redirect_after .= '&bo_query=' . $query . '&bo_search_type=' . $searchType;
		}

		return $res;
	}

	/**
	 * Post treatment for suppliers
	 *
	 * @since 1.9.1.0
	 */
	public function processSuppliers() {

		if ((int) Tools::getValue('supplier_loaded') === 1 && Validate::isLoadedObject($product = new Product((int) Tools::getValue('id_product')))) {
			// Get all id_product_attribute
			$attributes = $product->getAttributesResume($this->context->language->id);

			if (empty($attributes)) {
				$attributes[] = [
					'id_product_attribute'  => 0,
					'attribute_designation' => '',
				];
			}

			// Get all available suppliers
			$suppliers = Supplier::getSuppliers();

			// Get already associated suppliers
			$associatedSuppliers = ProductSupplier::getSupplierCollection($product->id);

			$suppliersToAssociate = [];
			$newDefaultSupplier = 0;

			if (Tools::isSubmit('default_supplier')) {
				$newDefaultSupplier = (int) Tools::getValue('default_supplier');
			}

			// Get new associations

			foreach ($suppliers as $supplier) {

				if (Tools::isSubmit('check_supplier_' . $supplier['id_supplier'])) {
					$suppliersToAssociate[] = $supplier['id_supplier'];
				}

			}

			// Delete already associated suppliers if needed

			foreach ($associatedSuppliers as $key => $associatedSupplier) {
				/** @var ProductSupplier $associatedSupplier */

				if (!in_array($associatedSupplier->id_supplier, $suppliersToAssociate)) {
					$associatedSupplier->delete();
					unset($associatedSuppliers[$key]);
				}

			}

			// Associate suppliers

			foreach ($suppliersToAssociate as $id) {
				$toAdd = true;

				foreach ($associatedSuppliers as $as) {
					/** @var ProductSupplier $as */

					if ($id == $as->id_supplier) {
						$toAdd = false;
					}

				}

				if ($toAdd) {
					$productSupplier = new ProductSupplier();
					$productSupplier->id_product = $product->id;
					$productSupplier->id_product_attribute = 0;
					$productSupplier->id_supplier = $id;

					if ($this->context->currency->id) {
						$productSupplier->id_currency = (int) $this->context->currency->id;
					} else {
						$productSupplier->id_currency = (int) Configuration::get('EPH_CURRENCY_DEFAULT');
					}

					$productSupplier->save();

					$associatedSuppliers[] = $productSupplier;

					foreach ($attributes as $attribute) {

						if ((int) $attribute['id_product_attribute'] > 0) {
							$productSupplier = new ProductSupplier();
							$productSupplier->id_product = $product->id;
							$productSupplier->id_product_attribute = (int) $attribute['id_product_attribute'];
							$productSupplier->id_supplier = $id;
							$productSupplier->save();
						}

					}

				}

			}

			// Manage references and prices

			foreach ($attributes as $attribute) {

				foreach ($associatedSuppliers as $supplier) {
					/** @var ProductSupplier $supplier */

					if (Tools::isSubmit('supplier_reference_' . $product->id . '_' . $attribute['id_product_attribute'] . '_' . $supplier->id_supplier) ||
						(Tools::isSubmit('product_price_' . $product->id . '_' . $attribute['id_product_attribute'] . '_' . $supplier->id_supplier) &&
							Tools::isSubmit('product_price_currency_' . $product->id . '_' . $attribute['id_product_attribute'] . '_' . $supplier->id_supplier))
					) {
						$reference = pSQL(
							Tools::getValue(
								'supplier_reference_' . $product->id . '_' . $attribute['id_product_attribute'] . '_' . $supplier->id_supplier,
								''
							)
						);

						$price = (float) str_replace(
							[' ', ','],
							['', '.'],
							Tools::getValue(
								'product_price_' . $product->id . '_' . $attribute['id_product_attribute'] . '_' . $supplier->id_supplier,
								0
							)
						);

						$price = Tools::EPH_round($price, 6);

						$idCurrency = (int) Tools::getValue(
							'product_price_currency_' . $product->id . '_' . $attribute['id_product_attribute'] . '_' . $supplier->id_supplier,
							0
						);

						if ($idCurrency <= 0 || (!($result = Currency::getCurrency($idCurrency)) || empty($result))) {
							$this->errors[] = Tools::displayError('The selected currency is not valid');
						}

						// Save product-supplier data
						$productSupplierId = (int) ProductSupplier::getIdByProductAndSupplier($product->id, $attribute['id_product_attribute'], $supplier->id_supplier);

						if (!$productSupplierId) {
							$product->addSupplierReference($supplier->id_supplier, (int) $attribute['id_product_attribute'], $reference, (float) $price, (int) $idCurrency);

							if ($product->id_supplier == $supplier->id_supplier) {

								if ((int) $attribute['id_product_attribute'] > 0) {
									$data = [
										'supplier_reference' => pSQL($reference),
										'wholesale_price'    => (float) Tools::convertPrice($price, $idCurrency),
									];
									$where = '
                                        a.id_product = ' . (int) $product->id . '
                                        AND a.id_product_attribute = ' . (int) $attribute['id_product_attribute'];
									ObjectModel::updateMultishopTable('Combination', $data, $where);
								} else {
									$product->wholesale_price = (float) Tools::convertPrice($price, $idCurrency); //converted in the default currency
									$product->supplier_reference = pSQL($reference);
									$product->update();
								}

							}

						} else {
							$productSupplier = new ProductSupplier($productSupplierId);
							$productSupplier->id_currency = (int) $idCurrency;
							$productSupplier->product_supplier_price_te = (float) $price;
							$productSupplier->product_supplier_reference = pSQL($reference);
							$productSupplier->update();
						}

					} else

					if (Tools::isSubmit('supplier_reference_' . $product->id . '_' . $attribute['id_product_attribute'] . '_' . $supplier->id_supplier)) {
						//int attribute with default values if possible

						if ((int) $attribute['id_product_attribute'] > 0) {
							$productSupplier = new ProductSupplier();
							$productSupplier->id_product = $product->id;
							$productSupplier->id_product_attribute = (int) $attribute['id_product_attribute'];
							$productSupplier->id_supplier = $supplier->id_supplier;
							$productSupplier->save();
						}

					}

				}

			}

			// Manage defaut supplier for product

			if ($newDefaultSupplier != $product->id_supplier) {
				$this->object->id_supplier = $newDefaultSupplier;
				$this->object->update();
			}

		}

	}

	/**
	 * Process features
	 *
	 * @since 1.9.1.0
	 */
	public function processFeatures() {

		if (!Feature::isFeatureActive()) {
			return;
		}

		if (Validate::isLoadedObject($product = new Product((int) Tools::getValue('id_product')))) {
			// delete all objects
			$product->deleteFeatures();

			// add new objects
			$languages = Language::getLanguages(false);

			foreach ($_POST as $key => $val) {

				if (preg_match('/^feature_([0-9]+)_value/i', $key, $match)) {

					if ($val) {
						$product->addFeaturesToDB($match[1], $val);
					} else {

						if ($defaultValue = $this->checkFeatures($languages, $match[1])) {
							$idValue = $product->addFeaturesToDB($match[1], 0, 1);

							foreach ($languages as $language) {

								if ($cust = Tools::getValue('custom_' . $match[1] . '_' . (int) $language['id_lang'])) {
									$product->addFeaturesCustomToDB($idValue, (int) $language['id_lang'], $cust);
								} else {
									$product->addFeaturesCustomToDB($idValue, (int) $language['id_lang'], $defaultValue);
								}

							}

						}

					}

				}

			}

		} else {
			$this->errors[] = Tools::displayError('A product must be created before adding features.');
		}

	}

	/**
	 * Check features
	 *
	 * @param $languages
	 * @param $featureId
	 *
	 * @return int|mixed
	 *
	 * @since 1.9.1.0
	 */
	protected function checkFeatures($languages, $featureId) {

		$rules = call_user_func(['FeatureValue', 'getValidationRules'], 'FeatureValue');
		$feature = Feature::getFeature((int) Configuration::get('EPH_LANG_DEFAULT'), $featureId);

		foreach ($languages as $language) {

			if ($val = Tools::getValue('custom_' . $featureId . '_' . $language['id_lang'])) {
				$currentLanguage = new Language($language['id_lang']);

				if (mb_strlen($val) > $rules['sizeLang']['value']) {
					$this->errors[] = sprintf(
						Tools::displayError('The name for feature %1$s is too long in %2$s.'),
						' <b>' . $feature['name'] . '</b>',
						$currentLanguage->name
					);
				} else

				if (!call_user_func(['Validate', $rules['validateLang']['value']], $val)) {
					$this->errors[] = sprintf(
						Tools::displayError('A valid name required for feature. %1$s in %2$s.'),
						' <b>' . $feature['name'] . '</b>',
						$currentLanguage->name
					);
				}

				if (count($this->errors)) {
					return 0;
				}

				// Getting default language

				if ($language['id_lang'] == Configuration::get('EPH_LANG_DEFAULT')) {
					return $val;
				}

			}

		}

		return 0;
	}

	/**
	 * Process product attribute
	 *
	 * @since 1.9.1.0
	 */
	public function processProductAttribute() {

		// Don't process if the combination fields have not been submitted

		if (!Combination::isFeatureActive() || !Tools::getValue('attribute_combination_list')) {
			return;
		}

		if (Validate::isLoadedObject($product = $this->object)) {

			if ($this->isProductFieldUpdated('attribute_price') && (!Tools::getIsset('attribute_price') || Tools::getIsset('attribute_price') == null)) {
				$this->errors[] = Tools::displayError('The price attribute is required.');
			}

			if (!Tools::getIsset('attribute_combination_list') || Tools::isEmpty(Tools::getValue('attribute_combination_list'))) {
				$this->errors[] = Tools::displayError('You must add at least one attribute.');
			}

			$arrayChecks = [
				'reference'          => 'isReference',
				'supplier_reference' => 'isReference',
				'location'           => 'isReference',
				'ean13'              => 'isEan13',
				'upc'                => 'isUpc',
				'wholesale_price'    => 'isPrice',
				'price'              => 'isPrice',
				'ecotax'             => 'isPrice',
				'quantity'           => 'isInt',
				'weight'             => 'isUnsignedFloat',
				'unit_price_impact'  => 'isPrice',
				'default_on'         => 'isBool',
				'minimal_quantity'   => 'isUnsignedInt',
				'available_date'     => 'isDateFormat',
			];

			foreach ($arrayChecks as $property => $check) {

				if (Tools::getValue('attribute_' . $property) !== false && !call_user_func(['Validate', $check], Tools::getValue('attribute_' . $property))) {
					$this->errors[] = sprintf(Tools::displayError('Field %s is not valid'), $property);
				}

			}

			if (!count($this->errors)) {

				if (!isset($_POST['attribute_wholesale_price'])) {
					$_POST['attribute_wholesale_price'] = 0;
				}

				if (!isset($_POST['attribute_price_impact'])) {
					$_POST['attribute_price_impact'] = 0;
				}

				if (!isset($_POST['attribute_weight_impact'])) {
					$_POST['attribute_weight_impact'] = 0;
				}

				if (!isset($_POST['attribute_ecotax'])) {
					$_POST['attribute_ecotax'] = 0;
				}

				if (Tools::getValue('attribute_default')) {
					$product->deleteDefaultAttributes();
				}

				// Change existing one

				if (($idProductAttribute = (int) Tools::getValue('id_product_attribute')) || ($idProductAttribute = $product->productAttributeExists(Tools::getValue('attribute_combination_list'), false, null, true, true))) {

					if ($this->tabAccess['edit'] === '1') {

						if ($this->isProductFieldUpdated('available_date_attribute') && (Tools::getValue('available_date_attribute') != '' && !Validate::isDateFormat(Tools::getValue('available_date_attribute')))) {
							$this->errors[] = Tools::displayError('Invalid date format.');
						} else {
							$product->updateAttribute(
								(int) $idProductAttribute,
								$this->isProductFieldUpdated('attribute_wholesale_price') ? Tools::getValue('attribute_wholesale_price') : null,
								$this->isProductFieldUpdated('attribute_price_impact') ? Tools::getValue('attribute_price') * Tools::getValue('attribute_price_impact') : null,
								$this->isProductFieldUpdated('attribute_weight_impact') ? Tools::getValue('attribute_weight') * Tools::getValue('attribute_weight_impact') : null,
								$this->isProductFieldUpdated('attribute_unit_impact') ? Tools::getValue('attribute_unity') * Tools::getValue('attribute_unit_impact') : null,
								$this->isProductFieldUpdated('attribute_ecotax') ? Tools::getValue('attribute_ecotax') : null,
								Tools::getValue('id_image_attr'),
								Tools::getValue('attribute_reference'),
								Tools::getValue('attribute_ean13'),
								$this->isProductFieldUpdated('attribute_default') ? Tools::getValue('attribute_default') : null,
								Tools::getValue('attribute_location'),
								Tools::getValue('attribute_upc'),
								$this->isProductFieldUpdated('attribute_minimal_quantity') ? Tools::getValue('attribute_minimal_quantity') : null,
								$this->isProductFieldUpdated('available_date_attribute') ? Tools::getValue('available_date_attribute') : null,
								false
							);
							StockAvailable::setProductDependsOnStock((int) $product->id, $product->depends_on_stock, null, (int) $idProductAttribute);
							StockAvailable::setProductOutOfStock((int) $product->id, $product->out_of_stock, null, (int) $idProductAttribute);
						}

					} else {
						$this->errors[] = Tools::displayError('You do not have permission to add this.');
					}

				}

				// Add new
				else {

					if ($this->tabAccess['add'] === '1') {
						/** @var Product $product */

						if ($product->productAttributeExists(Tools::getValue('attribute_combination_list'))) {
							$this->errors[] = Tools::displayError('This combination already exists.');
						} else {
							$idProductAttribute = $product->addCombinationEntity(
								Tools::getValue('attribute_wholesale_price'),
								Tools::getValue('attribute_price') * Tools::getValue('attribute_price_impact'),
								Tools::getValue('attribute_weight') * Tools::getValue('attribute_weight_impact'),
								Tools::getValue('attribute_unity') * Tools::getValue('attribute_unit_impact'),
								Tools::getValue('attribute_ecotax'),
								0,
								Tools::getValue('id_image_attr'),
								Tools::getValue('attribute_reference'),
								null,
								Tools::getValue('attribute_ean13'),
								Tools::getValue('attribute_default'),
								Tools::getValue('attribute_location'),
								Tools::getValue('attribute_upc'),
								Tools::getValue('attribute_minimal_quantity'),
								[],
								Tools::getValue('available_date_attribute')
							);
							StockAvailable::setProductDependsOnStock((int) $product->id, $product->depends_on_stock, null, (int) $idProductAttribute);
							StockAvailable::setProductOutOfStock((int) $product->id, $product->out_of_stock, null, (int) $idProductAttribute);
						}

					} else {
						$this->errors[] = Tools::displayError('You do not have permission to') . '<hr>' . Tools::displayError('edit here.');
					}

				}

				if (!count($this->errors)) {
					$combination = new Combination((int) $idProductAttribute);
					$combination->setAttributes(Tools::getValue('attribute_combination_list'));

					// images could be deleted before
					$idImages = Tools::getValue('id_image_attr');

					if (!empty($idImages)) {
						$combination->setImages($idImages);
					}

					$product->checkDefaultAttributes();

					if (Tools::getValue('attribute_default')) {
						Product::updateDefaultAttribute((int) $product->id);

						if (isset($idProductAttribute)) {
							$product->cache_default_attribute = (int) $idProductAttribute;
						}

						if ($availableDate = Tools::getValue('available_date_attribute')) {
							$product->setAvailableDate($availableDate);
						} else {
							$product->setAvailableDate();
						}

					}

				}

			}

		}

	}

	/**
	 * Process price addition
	 *
	 * @since 1.9.1.0
	 */
	public function processPriceAddition() {

		// Check if a specific price has been submitted

		if (!Tools::getIsset('submitPriceAddition')) {
			return;
		}

		$idProduct = Tools::getValue('id_product');
		$idProductAttribute = Tools::getValue('sp_id_product_attribute');
		$idShop = Tools::getValue('sp_id_shop');
		$idCurrency = Tools::getValue('sp_id_currency');
		$idCountry = Tools::getValue('sp_id_country');
		$idGroup = Tools::getValue('sp_id_group');
		$idCustomer = Tools::getValue('sp_id_customer');
		$price = Tools::getValue('leave_bprice') ? '-1' : Tools::getValue('sp_price');
		$fromQuantity = Tools::getValue('sp_from_quantity');
		$reduction = (float) (Tools::getValue('sp_reduction'));
		$reductionTax = Tools::getValue('sp_reduction_tax');
		$reductionType = !$reduction ? 'amount' : Tools::getValue('sp_reduction_type');
		$reductionType = $reductionType == '-' ? 'amount' : $reductionType;
		$from = Tools::getValue('sp_from');

		if (!$from) {
			$from = '0000-00-00 00:00:00';
		}

		$to = Tools::getValue('sp_to');

		if (!$to) {
			$to = '0000-00-00 00:00:00';
		}

		if (($price == '-1') && ((float) $reduction == '0')) {
			$this->errors[] = Tools::displayError('No reduction value has been submitted');
		} else

		if ($to != '0000-00-00 00:00:00' && strtotime($to) < strtotime($from)) {
			$this->errors[] = Tools::displayError('Invalid date range');
		} else

		if ($reductionType == 'percentage' && ((float) $reduction <= 0 || (float) $reduction > 100)) {
			$this->errors[] = Tools::displayError('Submitted reduction value (0-100) is out-of-range');
		} else

		if ($this->_validateSpecificPrice($idShop, $idCurrency, $idCountry, $idGroup, $idCustomer, $price, $fromQuantity, $reduction, $reductionType, $from, $to, $idProductAttribute)) {
			$specificPrice = new SpecificPrice();
			$specificPrice->id_product = (int) $idProduct;
			$specificPrice->id_product_attribute = (int) $idProductAttribute;
			$specificPrice->id_shop = (int) $idShop;
			$specificPrice->id_currency = (int) ($idCurrency);
			$specificPrice->id_country = (int) ($idCountry);
			$specificPrice->id_group = (int) ($idGroup);
			$specificPrice->id_customer = (int) $idCustomer;
			$specificPrice->price = (float) ($price);
			$specificPrice->from_quantity = (int) ($fromQuantity);
			$specificPrice->reduction = (float) ($reductionType == 'percentage' ? $reduction / 100 : $reduction);
			$specificPrice->reduction_tax = $reductionTax;
			$specificPrice->reduction_type = $reductionType;
			$specificPrice->from = $from;
			$specificPrice->to = $to;

			if (!$specificPrice->add()) {
				$this->errors[] = Tools::displayError('An error occurred while updating the specific price.');
			}

		}

	}

	/**
	 * Process specific price priorities
	 *
	 * @since 1.9.1.0
	 */
	public function processSpecificPricePriorities() {

		if (!($obj = $this->loadObject())) {
			return;
		}

		if (!$priorities = Tools::getValue('specificPricePriority')) {
			$this->errors[] = Tools::displayError('Please specify priorities.');
		} else

		if (Tools::isSubmit('specificPricePriorityToAll')) {

			if (!SpecificPrice::setPriorities($priorities)) {
				$this->errors[] = Tools::displayError('An error occurred while updating priorities.');
			} else {
				$this->confirmations[] = $this->l('The price rule has successfully updated');
			}

		} else

		if (!SpecificPrice::setSpecificPriority((int) $obj->id, $priorities)) {
			$this->errors[] = Tools::displayError('An error occurred while setting priorities.');
		}

	}

	/**
	 * Process customization configuration
	 *
	 * @since 1.9.1.0
	 */
	public function processCustomizationConfiguration() {

		$product = $this->object;
		// Get the number of existing customization fields ($product->text_fields is the updated value, not the existing value)
		$currentCustomization = $product->getCustomizationFieldIds();
		$filesCount = 0;
		$textCount = 0;

		if (is_array($currentCustomization)) {

			foreach ($currentCustomization as $field) {

				if ($field['type'] == 1) {
					$textCount++;
				} else {
					$filesCount++;
				}

			}

		}

		if (!$product->createLabels((int) $product->uploadable_files - $filesCount, (int) $product->text_fields - $textCount)) {
			$this->errors[] = Tools::displayError('An error occurred while creating customization fields.');
		}

		if (!count($this->errors) && !$product->updateLabels()) {
			$this->errors[] = Tools::displayError('An error occurred while updating customization fields.');
		}

		$product->customizable = ($product->uploadable_files > 0 || $product->text_fields > 0) ? 1 : 0;

		if (($product->uploadable_files != $filesCount || $product->text_fields != $textCount) && !count($this->errors) && !$product->update()) {
			$this->errors[] = Tools::displayError('An error occurred while updating the custom configuration.');
		}

	}

	/**
	 * Attach an existing attachment to the product
	 *
	 * @return void
	 *
	 * @since 1.9.1.0
	 */
	public function processAttachments() {

		if ($id = (int) Tools::getValue($this->identifier)) {
			$attachments = trim(Tools::getValue('arrayAttachments'), ',');
			$attachments = explode(',', $attachments);

			if (!Attachment::attachToProduct($id, $attachments)) {
				$this->errors[] = Tools::displayError('An error occurred while saving product attachments.');
			}

		}

	}

	/**
	 * Process image legends
	 *
	 * @since 1.9.1.0
	 */
	public function processImageLegends() {

		if (Tools::getValue('key_tab') == 'Images' && Tools::getValue('submitAddproductAndStay') == 'update_legends' && Validate::isLoadedObject($product = new Product((int) Tools::getValue('id_product')))) {
			$idImage = (int) Tools::getValue('id_caption');
			$languageIds = Language::getIDs(false);

			foreach ($_POST as $key => $val) {

				if (preg_match('/^legend_([0-9]+)/i', $key, $match)) {

					foreach ($languageIds as $idLang) {

						if ($val && $idLang == $match[1]) {
							Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'image_lang SET legend = "' . pSQL($val) . '" WHERE ' . ($idImage ? 'id_image = ' . (int) $idImage : 'EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'image WHERE ' . _DB_PREFIX_ . 'image.id_image = ' . _DB_PREFIX_ . 'image_lang.id_image AND id_product = ' . (int) $product->id . ')') . ' AND id_lang = ' . (int) $idLang);
						}

					}

				}

			}

		}

	}

	/**
	 * Post treatment for warehouses
	 *
	 * @since 1.9.1.0
	 */
	public function processWarehouses() {

		if ((int) Tools::getValue('warehouse_loaded') === 1 && Validate::isLoadedObject($product = new Product((int) $idProduct = Tools::getValue('id_product')))) {
			// Get all id_product_attribute
			$attributes = $product->getAttributesResume($this->context->language->id);

			if (empty($attributes)) {
				$attributes[] = [
					'id_product_attribute'  => 0,
					'attribute_designation' => '',
				];
			}

			// Get all available warehouses
			$warehouses = Warehouse::getWarehouses(true);

			// Get already associated warehouses
			$associatedWarehousesCollection = WarehouseProductLocation::getCollection($product->id);

			$elementsToManage = [];

			// get form inforamtion

			foreach ($attributes as $attribute) {

				foreach ($warehouses as $warehouse) {
					$key = $warehouse['id_warehouse'] . '_' . $product->id . '_' . $attribute['id_product_attribute'];

					// get elements to manage

					if (Tools::isSubmit('check_warehouse_' . $key)) {
						$location = Tools::getValue('location_warehouse_' . $key, '');
						$elementsToManage[$key] = $location;
					}

				}

			}

			// Delete entry if necessary

			foreach ($associatedWarehousesCollection as $awc) {
				/** @var WarehouseProductLocation $awc */

				if (!array_key_exists($awc->id_warehouse . '_' . $awc->id_product . '_' . $awc->id_product_attribute, $elementsToManage)) {
					$awc->delete();
				}

			}

			// Manage locations

			foreach ($elementsToManage as $key => $location) {
				$params = explode('_', $key);

				$wplId = (int) WarehouseProductLocation::getIdByProductAndWarehouse((int) $params[1], (int) $params[2], (int) $params[0]);

				if (empty($wplId)) {
					//create new record
					$warehouseLocationEntity = new WarehouseProductLocation();
					$warehouseLocationEntity->id_product = (int) $params[1];
					$warehouseLocationEntity->id_product_attribute = (int) $params[2];
					$warehouseLocationEntity->id_warehouse = (int) $params[0];
					$warehouseLocationEntity->location = pSQL($location);
					$warehouseLocationEntity->save();
				} else {
					$warehouseLocationEntity = new WarehouseProductLocation((int) $wplId);

					$location = pSQL($location);

					if ($location != $warehouseLocationEntity->location) {
						$warehouseLocationEntity->location = pSQL($location);
						$warehouseLocationEntity->update();
					}

				}

			}

			StockAvailable::synchronize((int) $idProduct);
		}

	}

	public function ajaxProcessUpdateByVal() {

		$file = fopen("testUpdateByVal.txt", "w");
		$idProduct = (int) Tools::getValue('idProduct');
		$field = Tools::getValue('field');
		$fieldValue = Tools::getValue('fieldValue');
		fwrite($file, $idProduct . PHP_EOL);
		fwrite($file, $field . PHP_EOL);
		fwrite($file, $fieldValue . PHP_EOL);
		$product = new Product($idProduct);
		$classVars = get_class_vars(get_class($product));

		if (isset($classVars['definition']['fields'])) {
			$fields = $classVars['definition']['fields'];
		}

		fwrite($file, print_r($fields[$field], true));

		if (array_key_exists('lang', $fields[$field]) && $fields[$field]['lang']) {
			fwrite($file, $field . ' is lang ' . PHP_EOL);
		}

		if (Validate::isLoadedObject($product)) {

			if (array_key_exists('lang', $fields[$field]) && $fields[$field]['lang']) {
				$idLang = Context::getContext()->language->id;
				$product->{$field}

				[(int) $idLang] = $fieldValue;

			} else {
				$product->$field = $fieldValue;
			}

			fwrite($file, print_r($product, true));
			$result = $product->update();

			if (!isset($result) || !$result) {
				$this->errors[] = Tools::displayError('An error occurred while updating the product.');
			} else {
				$result = [
					'success' => true,
					'message' => $this->l('Update successful'),
				];
			}

		} else {

			$this->errors[] = Tools::displayError('An error occurred while loading the product.');
		}

		$this->errors = array_unique($this->errors);

		if (count($this->errors)) {
			$result = [
				'success' => false,
				'message' => implode(PHP_EOL, $this->errors),
			];
		}

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessProductManufacturers() {

		$manufacturers = Manufacturer::getManufacturers();
		$jsonArray = [];

		if ($manufacturers) {

			foreach ($manufacturers as $manufacturer) {
				$tmp = ["optionValue" => $manufacturer['id_manufacturer'], "optionDisplay" => htmlspecialchars(trim($manufacturer['name']))];
				$jsonArray[] = json_encode($tmp);
			}

		}

		die('[' . implode(',', $jsonArray) . ']');
	}

	public function initPageHeaderToolbar() {

		if (empty($this->display)) {
			$this->page_header_toolbar_btn['filter_category'] = [
				'desc'       => $this->l('Filter by category', null, null, false),
				'identifier' => 'filter',
				'controller' => $this->controller_name,
				'icon'       => 'process-icon-filter',
			];
			$this->page_header_toolbar_btn['fields_edit'] = [
				'desc'       => $this->l('Choose available Fields to display', null, null, false),
				'identifier' => 'field',
				'controller' => $this->controller_name,
				'icon'       => 'process-icon-excel',
			];
			$this->page_header_toolbar_btn['reset_category'] = [
				'desc'       => $this->l('Reset category', null, null, false),
				'id'         => 'reset_category',
				'identifier' => 'resetFilter',
				'controller' => $this->controller_name,
				'style'      => 'display:none;',
				'icon'       => 'process-icon-chain-broken',
			];
			$this->page_header_toolbar_btn['new_product'] = [
				'href' => static::$currentIndex . '&addproduct&token=' . $this->token,
				'desc' => $this->l('Add new product', null, null, false),
				'icon' => 'process-icon-new',
			];
		}

		if ($this->display == 'edit') {

			if (($product = $this->loadObject(true)) && $product->isAssociatedToShop()) {
				// adding button for preview this product

				if ($url_preview = $this->getPreviewUrl($product)) {
					$this->page_header_toolbar_btn['preview'] = [
						'short'  => $this->l('Preview', null, null, false),
						'href'   => $url_preview,
						'desc'   => $this->l('Preview', null, null, false),
						'target' => true,
						'class'  => 'previewUrl',
					];
				}

				$js = (bool) Image::getImages($this->context->language->id, (int) $product->id) ?
				'confirm_link(\'\', \'' . $this->l('This will copy the images too. If you wish to proceed, click "Yes". If not, click "No".', null, true, false) . '\', \'' . $this->l('Yes', null, true, false) . '\', \'' . $this->l('No', null, true, false) . '\', \'' . $this->context->link->getAdminLink('AdminProducts', true) . '&id_product=' . (int) $product->id . '&duplicateproduct' . '\', \'' . $this->context->link->getAdminLink('AdminProducts', true) . '&id_product=' . (int) $product->id . '&duplicateproduct&noimage=1' . '\')'
				:
				'document.location = \'' . $this->context->link->getAdminLink('AdminProducts', true) . '&id_product=' . (int) $product->id . '&duplicateproduct&noimage=1' . '\'';

				// adding button for duplicate this product

				if ($this->tabAccess['add']) {
					$this->page_header_toolbar_btn['duplicate'] = [
						'short'   => $this->l('Duplicate', null, null, false),
						'desc'    => $this->l('Duplicate', null, null, false),
						'confirm' => 1,
						'js'      => $js,
					];
				}

				// adding button for preview this product statistics

				if (file_exists(_EPH_MODULE_DIR_ . 'statsproduct/statsproduct.php')) {
					$this->page_header_toolbar_btn['stats'] = [
						'short' => $this->l('Statistics', null, null, false),
						'href'  => $this->context->link->getAdminLink('AdminStats') . '&module=statsproduct&id_product=' . (int) $product->id,
						'desc'  => $this->l('Product sales', null, null, false),
					];
				}

				// adding button for delete this product

				if ($this->tabAccess['delete']) {
					$this->page_header_toolbar_btn['delete'] = [
						'short'   => $this->l('Delete', null, null, false),
						'href'    => $this->context->link->getAdminLink('AdminProducts') . '&id_product=' . (int) $product->id . '&deleteproduct',
						'desc'    => $this->l('Delete this product', null, null, false),
						'confirm' => 1,
						'js'      => 'if (confirm(\'' . $this->l('Delete product?', null, true, false) . '\')){return true;}else{event.preventDefault();}',
					];
				}

			}

		}

		parent::initPageHeaderToolbar();
	}

	public function initToolbar() {

		parent::initToolbar();

		if ($this->display == 'edit' || $this->display == 'add') {
			$this->toolbar_btn['save'] = [
				'short' => 'Save',
				'href'  => '#',
				'desc'  => $this->l('Save'),
			];

			$this->toolbar_btn['save-and-stay'] = [
				'short' => 'SaveAndStay',
				'href'  => '#',
				'desc'  => $this->l('Save and stay'),
			];

			// adding button for adding a new combination in Combination tab
			$this->toolbar_btn['newCombination'] = [
				'short' => 'New combination',
				'desc'  => $this->l('New combination'),
				'class' => 'toolbar-new',
			];
		} else

		if ($this->can_import) {
			$this->toolbar_btn['import'] = [
				'href' => $this->context->link->getAdminLink('AdminImport', true) . '&import_type=products',
				'desc' => $this->l('Import'),
			];
		}

		$this->context->smarty->assign('toolbar_scroll', 1);
		$this->context->smarty->assign('show_toolbar', 1);
		$this->context->smarty->assign('toolbar_btn', $this->toolbar_btn);
	}

	public function renderForm() {

		if ($this->object->id > 0) {
			// This nice code (irony) is here to store the product name, because the row after will erase product name in multishop context
			$this->product_name = $this->object->name[$this->context->language->id];
		}

		if (!method_exists($this, 'initForm' . $this->tab_display)) {
			return '';
		}

		$product = $this->object;

		// Product for multishop
		$this->context->smarty->assign('bullet_common_field', '');

		if (Shop::isFeatureActive() && $this->display == 'edit') {

			if (Shop::getContext() != Shop::CONTEXT_SHOP) {
				$this->context->smarty->assign(
					[
						'display_multishop_checkboxes' => true,
						'multishop_check'              => Tools::getValue('multishop_check'),
					]
				);
			}

			if (Shop::getContext() != Shop::CONTEXT_ALL) {
				$this->context->smarty->assign('bullet_common_field', '<i class="icon-circle text-orange"></i>');
				$this->context->smarty->assign('display_common_field', true);
			}

		}

		$this->tpl_form_vars['tabs_preloaded'] = $this->available_tabs;

		$this->tpl_form_vars['product_type'] = (int) Tools::getValue('type_product', $product->getType());

		$this->getLanguages();

		$this->tpl_form_vars['id_lang_default'] = Configuration::get('EPH_LANG_DEFAULT');

		$this->tpl_form_vars['currentIndex'] = static::$currentIndex;
		$this->tpl_form_vars['display_multishop_checkboxes'] = (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP && $this->display == 'edit');
		$this->fields_form = [''];

		$this->tpl_form_vars['token'] = $this->token;
		$this->tpl_form_vars['combinationImagesJs'] = $this->getCombinationImagesJs();
		$this->tpl_form_vars['EPH_ALLOW_ACCENTED_CHARS_URL'] = (int) Configuration::get('EPH_ALLOW_ACCENTED_CHARS_URL');
		$this->tpl_form_vars['post_data'] = json_encode($_POST);
		$this->tpl_form_vars['save_error'] = !empty($this->errors);
		$this->tpl_form_vars['mod_evasive'] = Tools::apacheModExists('evasive');
		$this->tpl_form_vars['mod_security'] = Tools::apacheModExists('security');
		$this->tpl_form_vars['EPH_force_friendly_product'] = Configuration::get('EPH_FORCE_FRIENDLY_PRODUCT');

		// autoload rich text editor (tiny mce)
		$this->tpl_form_vars['tinymce'] = true;
		$iso = $this->context->language->iso_code;
		$this->tpl_form_vars['iso'] = file_exists(_EPH_JS_DIR_ . 'tinymce/langs/' . $iso . '.js') ? $iso : 'en';
		$this->tpl_form_vars['path_css'] = _THEME_CSS_DIR_;
		$this->tpl_form_vars['ad'] = __EPH_BASE_URI__ . basename(_EPH_ADMIN_DIR_);

		if (Validate::isLoadedObject(($this->object))) {
			$idProduct = (int) $this->object->id;
		} else {
			$idProduct = (int) Tools::getvalue('id_product');
		}

		$page = (int) Tools::getValue('page');

		$this->tpl_form_vars['form_action'] = $this->context->link->getAdminLink('AdminProducts') . '&' . ($idProduct ? 'updateproduct&id_product=' . (int) $idProduct : 'addproduct') . ($page > 1 ? '&page=' . (int) $page : '');
		$this->tpl_form_vars['id_product'] = $idProduct;

		// Transform configuration option 'upload_max_filesize' in octets
		$uploadMaxFilesize = Tools::getOctets(ini_get('upload_max_filesize'));

		// Transform configuration option 'upload_max_filesize' in MegaOctets
		$uploadMaxFilesize = ($uploadMaxFilesize / 1024) / 1024;

		$this->tpl_form_vars['upload_max_filesize'] = $uploadMaxFilesize;
		$this->tpl_form_vars['country_display_tax_label'] = $this->context->country->display_tax_label;
		$this->tpl_form_vars['has_combinations'] = $this->object->hasAttributes();
		$this->tpl_form_vars['link'] = $this->context->link;
		$this->product_exists_in_shop = true;

		if ($this->display == 'edit' && Validate::isLoadedObject($product) && Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP && !$product->isAssociatedToShop($this->context->shop->id)) {
			$this->product_exists_in_shop = false;

			if ($this->tab_display == 'Informations') {
				$this->displayWarning($this->l('Warning: The product does not exist in this shop'));
			}

			$defaultProduct = new Product();
			$definition = ObjectModel::getDefinition($product);

			foreach ($definition['fields'] as $fieldName => $field) {

				if (isset($field['shop']) && $field['shop']) {
					$product->$fieldName = ObjectModel::formatValue($defaultProduct->$fieldName, $field['type']);
				}

			}

		}

		// let's calculate this once for all

		if (!Validate::isLoadedObject($this->object) && Tools::getValue('id_product')) {
			$this->errors[] = 'Unable to load object';
		} else {
			$this->_displayDraftWarning($this->object->active);

			// if there was an error while saving, we don't want to lose posted data

			if (!empty($this->errors)) {
				$this->copyFromPost($this->object, $this->table);
			}

			$this->initPack($this->object);
			$this->{'initForm' . $this->tab_display}

			($this->object);
			$this->tpl_form_vars['product'] = $this->object;

			if ($this->ajax) {

				if (!isset($this->tpl_form_vars['custom_form'])) {
					throw new PhenyxShopException('custom_form empty for action ' . $this->tab_display);
				} else {
					return $this->tpl_form_vars['custom_form'];
				}

			}

		}

		$parent = parent::renderForm();
		$this->addJqueryPlugin(['autocomplete', 'fancybox', 'typewatch']);

		return $parent;
	}

	/**
	 * Get combination images JS
	 *
	 * @return string
	 *
	 * @since 1.9.1.0
	 */
	public function getCombinationImagesJS() {

		/** @var Product $obj */

		if (!($obj = $this->loadObject(true))) {
			return '';
		}

		$content = 'var combination_images = new Array();';

		if (!$allCombinationImages = $obj->getCombinationImages($this->context->language->id)) {
			return $content;
		}

		foreach ($allCombinationImages as $idProductAttribute => $combinationImages) {
			$i = 0;
			$content .= 'combination_images[' . (int) $idProductAttribute . '] = new Array();';

			foreach ($combinationImages as $combinationImage) {
				$content .= 'combination_images[' . (int) $idProductAttribute . '][' . $i++ . '] = ' . (int) $combinationImage['id_image'] . ';';
			}

		}

		return $content;
	}

	/**
	 * Display draft warning
	 *
	 * @param $active
	 *
	 * @since 1.9.1.0
	 */
	protected function _displayDraftWarning($active) {

		$content = '<div class="warn draft" style="' . ($active ? 'display:none' : '') . '">
                <span>' . $this->l('Your product will be saved as a draft.') . '</span>
                <a href="#" class="btn btn-default pull-right" onclick="submitAddProductAndPreview()" ><i class="icon-external-link-sign"></i> ' . $this->l('Save and preview') . '</a>
                <input type="hidden" name="fakeSubmitAddProductAndPreview" id="fakeSubmitAddProductAndPreview" />
            </div>';
		$this->tpl_form_vars['draft_warning'] = $content;
	}

	protected function initPack(Product $product) {

		$this->tpl_form_vars['is_pack'] = ($product->id && Pack::isPack($product->id)) || Tools::getValue('type_product') == Product::PTYPE_PACK;
		$product->packItems = Pack::getItems($product->id, $this->context->language->id);

		$inputPackItems = '';

		if (Tools::getValue('inputPackItems')) {
			$inputPackItems = Tools::getValue('inputPackItems');
		} else {

			if (is_array($product->packItems)) {

				foreach ($product->packItems as $packItem) {
					$inputPackItems .= $packItem->pack_quantity . 'x' . $packItem->id . '-';
				}

			}

		}

		$this->tpl_form_vars['input_pack_items'] = $inputPackItems;

		$inputNamepackItems = '';

		if (Tools::getValue('namePackItems')) {
			$inputNamepackItems = Tools::getValue('namePackItems');
		} else {

			if (is_array($product->packItems)) {

				foreach ($product->packItems as $packItem) {
					$inputNamepackItems .= $packItem->pack_quantity . ' x ' . $packItem->name . '¤';
				}

			}

		}

		$this->tpl_form_vars['input_namepack_items'] = $inputNamepackItems;
	}

	/**
	 * @param Product $obj
	 *
	 * @throws Exception
	 * @throws PhenyxShopException
	 * @throws SmartyException
	 *
	 * @since 1.9.1.0
	 */
	public function initFormAssociations($obj) {

		$product = $obj;
		$data = $this->createTemplate($this->tpl_form);
		$file = fopen("testinitFormAssociations.txt", "w");

		$root = Category::getRootCategory();

		$defaultCategory = $this->context->cookie->id_category_products_filter ? $this->context->cookie->id_category_products_filter : $this->context->shop->id_category;
		fwrite($file, $defaultCategory . PHP_EOL);

		if (!$product->id) {

			$selectedCat = Category::getCategoryInformations(Tools::getValue('categoryBox', [$defaultCategory]), $this->default_form_language);
		} else {

			if (Tools::isSubmit('categoryBox')) {
				$selectedCat = Category::getCategoryInformations(Tools::getValue('categoryBox', [$defaultCategory]), $this->default_form_language);
			} else {

				$selectedCat = Product::getProductCategoriesFull($product->id, $this->default_form_language);
			}

		}

		// Multishop block
		$data->assign('feature_shop_active', Shop::isFeatureActive());
		$helper = new HelperForm();

		if ($this->object && $this->object->id) {
			$helper->id = $this->object->id;
		} else {
			$helper->id = null;
		}

		$helper->table = $this->table;
		$helper->identifier = $this->identifier;

		// Accessories block
		$accessories = Product::getAccessoriesLight($this->context->language->id, $product->id);

		if ($postAccessories = Tools::getValue('inputAccessories')) {
			$postAccessoriesTab = explode('-', $postAccessories);

			foreach ($postAccessoriesTab as $accessoryId) {

				if (!$this->haveThisAccessory($accessoryId, $accessories) && $accessory = Product::getAccessoryById($accessoryId)) {
					$accessories[] = $accessory;
				}

			}

		}

		$data->assign('accessories', $accessories);

		$product->manufacturer_name = Manufacturer::getNameById($product->id_manufacturer);

		$manufacturers = Manufacturer::getManufacturers();

		$data->assign(
			[
				'default_category'    => $defaultCategory,
				'selected_cat_ids'    => implode(',', array_keys($selectedCat)),
				'selected_cat'        => $selectedCat,
				'id_category_default' => $product->id_category_default,
				'product'             => $product,
				'link'                => $this->context->link,
				'manufacturers'       => $manufacturers,
				'is_shop_context'     => Shop::getContext() == Shop::CONTEXT_SHOP,
			]
		);

		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function generateCategoryGridScript($category_tree) {

		$paragrid = new ParamGrid('AsssociatedCategories', 'AdminAssociatedCategories', $this->table, $this->identifier);
		$paragrid->onlyObject = true;
		$paragrid->height = 500;
		$paragrid->showNumberCell = 0;
		$paragrid->showTitle = 1;
		$paragrid->title = '\'' . $this->l('Associated Categories') . '\'';
		$paragrid->selectionModelType = 'cell';
		$paragrid->needRequestModel = false;
		$paragrid->dataModel = [
			'data' => $category_tree,
		];
		$paragrid->colModel = $this->getCategoryTreeFields();
		$paragrid->check = 'function(evt, ui) {
			console.log(ui);

        }';

		$paragrid->filterModel = [
			'on'     => true,
			'mode'   => '\'AND\'',
			'header' => true,
		];
		$paragrid->treeModel = [
			'dataIndx'           => '\'name\'',
			'id'                 => '\'id_category\'',
			'checkbox'           => 1,
			'filterShowChildren' => 1,
			'icons'              => 0,
			// 'iconCollapse' => ['\'fa fa-circle-minus toggeCategoryClose\'', '\'fa fa-circle-plus toggeCategoryOpen\''],
		];

		$paragrid->gridFunction = [
			'neutralFunction()' => '',
		];

		$option = $paragrid->generateParaGridOption();
		$script = $paragrid->generateParagridScript();
		return '<script type="text/javascript">' . PHP_EOL . $script . PHP_EOL . '</script>';
	}

	public function AjaxProcessGetAssociationFields() {

		die(Tools::jsonEncode([

			[

				'dataIndx' => 'pq_tree_cb',
				'editable' => true,
				'hidden'   => true,
			],
			[
				'title'    => $this->l('ID'),
				'dataIndx' => 'id_category',
				'dataType' => 'integer',
				'hidden'   => true,
			],

			[
				'title'    => $this->l('Name'),
				'width'    => 300,
				'dataIndx' => 'name',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',
			],

		]));

	}

	public function ajaxProcessUploadProductFile() {

		if (isset($_FILES['productFile']['name']) && !empty($_FILES['productFile']['name']) && !empty($_FILES['productFile']['tmp_name'])) {

			$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
			$reader->setReadDataOnly(true);
			$Upload['content'] = Tools::file_get_contents($_FILES['productFile']['tmp_name']);
			$Upload['name'] = $_FILES['productFile']['name'];
			$Upload['mime'] = $_FILES['productFile']['type'];
			$dir = _EPH_ADMIN_DIR_ . DIRECTORY_SEPARATOR . 'fileimport' . DIRECTORY_SEPARATOR;
			$fileName = $_FILES['productFile']['name'];
			$uploadfile = $dir . basename($fileName);
			$sourcePath = $_FILES['productFile']['tmp_name'];
			$spreadsheet = $reader->load($sourcePath);
			$sheetDatas = $spreadsheet->getActiveSheet()->toArray();

			if (is_array($sheetDatas) && is_array($sheetDatas[0])) {
				$columns = count($sheetDatas[0]);
			}

			move_uploaded_file($sourcePath, $uploadfile);
			$this->identifier_value = 'importFile';
			$this->tab_identifier = 'detail' . $this->controller_name . '-' . $this->identifier_value;
			$this->tab_link = 'tab-' . $this->controller_name . '-' . $this->identifier_value;
			$this->tab_liId = 'detail-' . $this->controller_name . '-' . $this->identifier_value;
			$this->closeTabButton = '<button type="button" class="close tabdetail" data-id="' . $this->tab_liId . '" ><i class="icon-times-circle" aria-hidden="true"></i></button>';
			$this->displayBackOfficeHeader = '';
			$this->displayBackOfficeFooter = '';
			$this->ajax_js = '';
			$this->scriptHook = '';
			$this->tab_name = 'productFile';
			$this->paragrid = false;
			$data = $this->createTemplate('controllers/products/reader.tpl');
			$this->context->smarty->assign([
				'fieldsSelector' => $this->fieldsSelector,
				'columns'        => $columns,
				'sheetDatas'     => $sheetDatas,
				'suppliers'      => Supplier::getSupplierCollection(),

			]);

			$this->content = $data->fetch();

			$this->ajaxTabDisplay();

		}

	}

	/**
	 * @param $accessoryId
	 * @param $accessories
	 *
	 * @return bool
	 *
	 * @since 1.9.1.0
	 */
	public function haveThisAccessory($accessoryId, $accessories) {

		foreach ($accessories as $accessory) {

			if ((int) $accessory['id_product'] == (int) $accessoryId) {
				return true;
			}

		}

		return false;
	}

	public function initFormPrices($obj) {

		$data = $this->createTemplate($this->tpl_form);
		$product = $obj;

		if ($obj->id) {
			$shops = Shop::getShops();
			$countries = Country::getCountries($this->context->language->id);
			$groups = Group::getGroups($this->context->language->id);
			$currencies = Currency::getCurrencies();
			$attributes = $obj->getAttributesGroups((int) $this->context->language->id);
			$combinations = [];

			foreach ($attributes as $attribute) {
				$combinations[$attribute['id_product_attribute']]['id_product_attribute'] = $attribute['id_product_attribute'];

				if (!isset($combinations[$attribute['id_product_attribute']]['attributes'])) {
					$combinations[$attribute['id_product_attribute']]['attributes'] = '';
				}

				$combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'] . ' - ';

				$combinations[$attribute['id_product_attribute']]['price'] = Tools::displayPrice(
					Tools::convertPrice(
						Product::getPriceStatic((int) $obj->id, false, $attribute['id_product_attribute']),
						$this->context->currency
					),
					$this->context->currency
				);
			}

			foreach ($combinations as &$combination) {
				$combination['attributes'] = rtrim($combination['attributes'], ' - ');
			}

			$data->assign('ecotax_tax_excl', (float) $obj->ecotax);
			$this->_applyTaxToEcotax($obj);

			$data->assign(
				[
					'shops'          => $shops,
					'admin_one_shop' => count($this->context->employee->getAssociatedShops()) == 1,
					'currencies'     => $currencies,
					'countries'      => $countries,
					'groups'         => $groups,
					'combinations'   => $combinations,
					'multi_shop'     => Shop::isFeatureActive(),
					'link'           => new Link(),
					'pack'           => new Pack(),
				]
			);
		} else {
			$this->displayWarning($this->l('You must save this product before adding specific pricing'));
			$product->id_tax_rules_group = (int) Product::getIdTaxRulesGroupMostUsed();
			$data->assign('ecotax_tax_excl', 0);
		}

		$address = new Address();
		$address->id_country = (int) $this->context->country->id;
		$taxRulesGroups = TaxRulesGroup::getTaxRulesGroups(true);
		$taxRates = [
			0 => [
				'id_tax_rules_group' => 0,
				'rates'              => [0],
				'computation_method' => 0,
			],
		];

		foreach ($taxRulesGroups as $taxRulesGroup) {
			$idTaxRulesGroup = (int) $taxRulesGroup['id_tax_rules_group'];
			$taxCalculator = TaxManagerFactory::getManager($address, $idTaxRulesGroup)->getTaxCalculator();
			$taxRates[$idTaxRulesGroup] = [
				'id_tax_rules_group' => $idTaxRulesGroup,
				'rates'              => [],
				'computation_method' => (int) $taxCalculator->computation_method,
			];

			if (isset($taxCalculator->taxes) && count($taxCalculator->taxes)) {

				foreach ($taxCalculator->taxes as $tax) {
					$taxRates[$idTaxRulesGroup]['rates'][] = (float) $tax->rate;
				}

			} else {
				$taxRates[$idTaxRulesGroup]['rates'][] = 0;
			}

		}

		// prices part
		$data->assign(
			[
				'link'                    => $this->context->link,
				'currency'                => $currency = $this->context->currency,
				'tax_rules_groups'        => $taxRulesGroups,
				'taxesRatesByGroup'       => $taxRates,
				'ecotaxTaxRate'           => Tax::getProductEcotaxRate(),
				'tax_exclude_taxe_option' => Tax::excludeTaxeOption(),
				'EPH_use_ecotax'           => Configuration::get('EPH_USE_ECOTAX'),
			]
		);

		$product->price = Tools::convertPrice($product->price, $this->context->currency, true, $this->context);

		if ($product->unit_price_ratio != 0) {
			$data->assign('unit_price', Tools::EPH_round($product->price / $product->unit_price_ratio, 6));
		} else {
			$data->assign('unit_price', 0);
		}

		$data->assign('EPH_tax', Configuration::get('EPH_TAX'));

		$data->assign('country_display_tax_label', $this->context->country->display_tax_label);
		$data->assign(
			[
				'currency', $this->context->currency,
				'product' => $product,
				'token'   => $this->token,
			]
		);

		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function getSpecificPriceRequest($idProduct) {

		$shops = Shop::getShops();
		$countries = Country::getCountries($this->context->language->id);
		$groups = Group::getGroups($this->context->language->id);
		$currencies = Currency::getCurrencies();

		$tmp = [];

		foreach ($shops as $shop) {
			$tmp[$shop['id_shop']] = $shop;
		}

		$shops = $tmp;
		$tmp = [];

		foreach ($currencies as $currency) {
			$tmp[$currency['id_currency']] = $currency;
		}

		$currencies = $tmp;

		$tmp = [];

		foreach ($countries as $country) {
			$tmp[$country['id_country']] = $country;
		}

		$countries = $tmp;

		$tmp = [];

		foreach ($groups as $group) {
			$tmp[$group['id_group']] = $group;
		}

		$groups = $tmp;

		$product = new Product($idProduct);
		$specificPrices = SpecificPrice::getByProductId((int) $product->id);

		foreach ($specificPrices as &$specificPrice) {

			$idCurrency = $specificPrice['id_currency'] ? $specificPrice['id_currency'] : $this->context->currency->id;

			if (!isset($currencies[$idCurrency])) {
				continue;
			}

			if ($specificPrice['id_currency'] > 0) {
				$specificPrice['id_currency'] = $currencies[$specificPrice['id_currency']]['name'];
			} else {
				$specificPrice['id_currency'] = $this->l('All currencies');
			}

			if ($specificPrice['id_group'] > 0) {
				$specificPrice['id_group'] = $groups[$specificPrice['id_group']]['name'];
			} else {
				$specificPrice['id_group'] = $this->l('All groups');
			}

			if ($specificPrice['price'] > 0) {
				$specificPrice['price'] = $specificPrice['price'];
			} else {
				$specificPrice['price'] = '-';
			}

			$specificPrice['id_country'] = $specificPrice['id_country'] ? $countries[$specificPrice['id_country']]['name'] : $this->l('All countries');
			$currentSpecificCurrency = $currencies[$idCurrency];

			if ($specificPrice['reduction_type'] == 'percentage') {
				$specificPrice['impact'] = '- ' . ($specificPrice['reduction'] * 100) . ' %';
			} else
			if ($specificPrice['reduction'] > 0) {
				$specificPrice['impact'] = '- ' . Tools::displayPrice(Tools::EPH_round($specificPrice['reduction'], 2), $currentSpecificCurrency) . ' ';

				if ($specificPrice['reduction_tax']) {
					$specificPrice['impact'] .= '(' . $this->l('Tax incl.') . ')';
				} else {
					$specificPrice['impact'] .= '(' . $this->l('Tax excl.') . ')';
				}

			} else {
				$specificPrice['impact'] = '--';
			}

			if ($specificPrice['from'] == '0000-00-00 00:00:00' && $specificPrice['to'] == '0000-00-00 00:00:00') {
				$specificPrice['period'] = $this->l('Unlimited');
			} else {
				$specificPrice['period'] = $this->l('From') . ' ' . ($specificPrice['from'] != '0000-00-00 00:00:00' ? $specificPrice['from'] : '0000-00-00 00:00:00') . '<br />' . $this->l('To') . ' ' . ($specificPrice['to'] != '0000-00-00 00:00:00' ? $specificPrice['to'] : '0000-00-00 00:00:00');
			}

			if ($specificPrice['id_product_attribute']) {
				$combination = new Combination((int) $specificPrice['id_product_attribute']);
				$attributes = $combination->getAttributesName((int) $this->context->language->id);
				$specificPrice['attributesName'] = '';

				foreach ($attributes as $attribute) {
					$specificPrice['attributesName'] .= $attribute['name'] . ' - ';
				}

				$specificPrice['attributesName'] = rtrim($specificPrice['attributesName'], ' - ');
			} else {
				$specificPrice['attributesName'] = $this->l('All combinations');
			}

			$rule = new SpecificPriceRule((int) $specificPrice['id_specific_price_rule']);
			$specificPrice['ruleName'] = ($rule->id ? $rule->name : '--');

			if ($specificPrice['id_customer']) {
				$customer = new Customer((int) $specificPrice['id_customer']);

				if (Validate::isLoadedObject($customer)) {
					$specificPrice['id_customer'] = $customer->firstname . ' ' . $customer->lastname;
				}

				unset($customer);
			} else {
				$specificPrice['id_customer'] = $this->l('All customers');
			}

			$specificPrice['edit_button'] = '<button class="btn btn-default" onClick="editSpecificPrice(' . $specificPrice['id_specific_price'] . ')">
								<i class="fa fa-pencil"></i>
							</button>';
			$specificPrice['delete_button'] = '<button class="btn btn-default" onClick="deleteSpecificPrice(' . $specificPrice['id_specific_price'] . ', ' . $specificPrice['id_product'] . ')">
								<i class="fa fa-plus-circle"></i>
							</button>';

		}

		return $specificPrices;
	}

	public function ajaxProcessGetSpecificPriceRequest() {

		$id_product = Tools::getValue('id_product');

		die(Tools::jsonEncode($this->getSpecificPriceRequest($id_product)));
	}

	public function AjaxProcessGetSpecificPriceFields() {

		$fileds = [
			[
				'dataIndx' => 'id_specific_price',
				'dataType' => 'integer',
				'hidden'   => true,
			],
			[
				'dataIndx' => 'id_specific_price_rule',
				'dataType' => 'integer',
				'hidden'   => true,
			],
			[
				'dataIndx' => 'id_product',
				'dataType' => 'integer',
				'hidden'   => true,
			],
			[
				'title'    => $this->l('Rule'),
				'dataIndx' => 'ruleName',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',
			],
			[
				'title'    => $this->l('Currency'),
				'dataIndx' => 'id_currency',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',
			],
			[
				'title'    => $this->l('Country'),
				'dataIndx' => 'id_country',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',
			],
			[
				'title'    => $this->l('Group'),
				'dataIndx' => 'id_group',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',
			],
			[
				'title'    => $this->l('Customer'),
				'dataIndx' => 'id_customer',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',
			],
			[
				'title'    => $this->l('Fixed price (tax excl.)'),
				'dataIndx' => 'price',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',
			],
			[
				'title'    => $this->l('Impact'),
				'dataIndx' => 'impact',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'string',
			],
			[
				'title'    => $this->l('Period'),
				'dataIndx' => 'period',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'html',
			],
			[
				'title'    => $this->l('From (quantity)'),
				'dataIndx' => 'from_quantity',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'integer',
			],
			[
				'title'    => $this->l('Edit'),
				'dataIndx' => 'edit_button',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'html',
			],
			[
				'title'    => $this->l('Delete'),
				'dataIndx' => 'delete_button',
				'align'    => 'left',
				'valign'   => 'center',
				'dataType' => 'html',
			],
		];

		die(Tools::jsonEncode($fileds));

	}

	public function ajaxProcesseditSpecificPrice() {

		$id_specific_price = Tools::getValue('id_specific_price');
		$specificPrice = new SpecificPrice($id_specific_price);
		$product = new Product($specificPrice->id_product);
		$data = $this->createTemplate('specific_prices.tpl');
		$attributes = $product->getAttributesGroups((int) $this->context->language->id);
		$combinations = [];

		foreach ($attributes as $attribute) {
			$combinations[$attribute['id_product_attribute']]['id_product_attribute'] = $attribute['id_product_attribute'];

			if (!isset($combinations[$attribute['id_product_attribute']]['attributes'])) {
				$combinations[$attribute['id_product_attribute']]['attributes'] = '';
			}

			$combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'] . ' - ';

			$combinations[$attribute['id_product_attribute']]['price'] = Tools::displayPrice(
				Tools::convertPrice(
					Product::getPriceStatic((int) $product->id, false, $attribute['id_product_attribute']),
					$this->context->currency
				),
				$this->context->currency
			);
		}

		foreach ($combinations as &$combination) {
			$combination['attributes'] = rtrim($combination['attributes'], ' - ');
		}

		if ($specificPrice->id_customer) {
			$customer = new Customer((int) $specificPrice->id_customer);

			if (Validate::isLoadedObject($customer)) {
				$specificPrice->customerName = $customer->firstname . ' ' . $customer->lastname;
			}

			unset($customer);
		} else {
			$specificPrice->customerName = $this->l('All customers');
		}

		if ($specificPrice->price == -1) {
			$price = $product->price;
			$leave_bprice = 1;
		} else {
			$price = $specificPrice->price;
			$leave_bprice = 0;
		}

		$data->assign([
			'specificPrice'   => $specificPrice,
			'price'           => $price,
			'leave_bprice'    => $leave_bprice,
			'link'            => $this->context->link,
			'id_product'      => $id_product,
			'specific_action' => 'updateSpecificPrice',
			'product'         => $product,
			'combinations'    => $combinations,
			'tagHeader'       => $this->l('Add new specifi price for ') . $product->name[$this->context->language->id],
			'shops'           => Shop::getShops(),
			'countries'       => Country::getCountries($this->context->language->id),
			'groups'          => Group::getGroups($this->context->language->id),
			'currencies'      => Currency::getCurrencies(),
			'currency'        => $this->context->currency,
			'multi_shop'      => Shop::isFeatureActive(),
			'admin_one_shop'  => count($this->context->employee->getAssociatedShops()) == 1,
		]);

		$result = [
			'html'          => $data->fetch(),
			'currentText'   => $this->l('Now'),
			'closeText'     => $this->l('Done'),
			'timeOnlyTitle' => $this->l('Choose Time'),
			'timeText'      => $this->l('Time'),
			'hourText'      => $this->l('Hour'),
			'minuteText'    => $this->l('Minute'),
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessAddSpecificPrice() {

		$id_product = Tools::getValue('id_product');
		$product = new Product($id_product);
		$specificPrice = new SpecificPrice();
		$data = $this->createTemplate('specific_prices.tpl');
		$attributes = $product->getAttributesGroups((int) $this->context->language->id);
		$combinations = [];
		$specificPrice->customerName = $this->l('All customers');
		$specificPrice->from_quantity = 1;
		$price = $product->price;
		$specificPrice->reduction_type = 'amount';
		$specificPrice->reduction_tax = 1;

		foreach ($attributes as $attribute) {
			$combinations[$attribute['id_product_attribute']]['id_product_attribute'] = $attribute['id_product_attribute'];

			if (!isset($combinations[$attribute['id_product_attribute']]['attributes'])) {
				$combinations[$attribute['id_product_attribute']]['attributes'] = '';
			}

			$combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'] . ' - ';

			$combinations[$attribute['id_product_attribute']]['price'] = Tools::displayPrice(
				Tools::convertPrice(
					Product::getPriceStatic((int) $product->id, false, $attribute['id_product_attribute']),
					$this->context->currency
				),
				$this->context->currency
			);
		}

		foreach ($combinations as &$combination) {
			$combination['attributes'] = rtrim($combination['attributes'], ' - ');
		}

		$data->assign([
			'specificPrice'   => $specificPrice,
			'price'           => $price,
			'link'            => $this->context->link,
			'leave_bprice'    => 1,
			'sp_reduction'    => '0.00',
			'id_product'      => $id_product,
			'specific_action' => 'newSpecificPrice',
			'product'         => $product,
			'combinations'    => $combinations,
			'tagHeader'       => $this->l('Add new specifi price for ') . $product->name[$this->context->language->id],
			'shops'           => Shop::getShops(),
			'countries'       => Country::getCountries($this->context->language->id),
			'groups'          => Group::getGroups($this->context->language->id),
			'currencies'      => Currency::getCurrencies(),
			'currency'        => $this->context->currency,
			'multi_shop'      => Shop::isFeatureActive(),
			'admin_one_shop'  => count($this->context->employee->getAssociatedShops()) == 1,
		]);

		$result = [
			'html'          => $data->fetch(),
			'currentText'   => $this->l('Now'),
			'closeText'     => $this->l('Done'),
			'timeOnlyTitle' => $this->l('Choose Time'),
			'timeText'      => $this->l('Time'),
			'hourText'      => $this->l('Hour'),
			'minuteText'    => $this->l('Minute'),
		];

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessNewSpecificPrice() {

		$idProduct = Tools::getValue('id_product');
		$idProductAttribute = Tools::getValue('sp_id_product_attribute');
		$idShop = Tools::getValue('sp_id_shop');
		$idCurrency = Tools::getValue('sp_id_currency');
		$idCountry = Tools::getValue('sp_id_country');
		$idGroup = Tools::getValue('sp_id_group');
		$idCustomer = Tools::getValue('sp_id_customer');
		$price = Tools::getValue('leave_bprice') ? '-1' : Tools::getValue('sp_price');
		$fromQuantity = Tools::getValue('sp_from_quantity');
		$reduction = (float) (Tools::getValue('sp_reduction'));
		$reductionTax = Tools::getValue('sp_reduction_tax');
		$reductionType = !$reduction ? 'amount' : Tools::getValue('sp_reduction_type');
		$reductionType = $reductionType == '-' ? 'amount' : $reductionType;
		$from = Tools::getValue('sp_from');

		if (!$from) {
			$from = '0000-00-00 00:00:00';
		}

		$to = Tools::getValue('sp_to');

		if (!$to) {
			$to = '0000-00-00 00:00:00';
		}

		if (($price == '-1') && ((float) $reduction == '0')) {
			$this->errors[] = Tools::displayError('No reduction value has been submitted');
		} else

		if ($to != '0000-00-00 00:00:00' && strtotime($to) < strtotime($from)) {
			$this->errors[] = Tools::displayError('Invalid date range');
		} else

		if ($reductionType == 'percentage' && ((float) $reduction <= 0 || (float) $reduction > 100)) {
			$this->errors[] = Tools::displayError('Submitted reduction value (0-100) is out-of-range');
		} else

		if ($this->_validateSpecificPrice($idShop, $idCurrency, $idCountry, $idGroup, $idCustomer, $price, $fromQuantity, $reduction, $reductionType, $from, $to, $idProductAttribute)) {
			$specificPrice = new SpecificPrice();
			$specificPrice->id_product = (int) $idProduct;
			$specificPrice->id_product_attribute = (int) $idProductAttribute;
			$specificPrice->id_shop = (int) $idShop;
			$specificPrice->id_currency = (int) ($idCurrency);
			$specificPrice->id_country = (int) ($idCountry);
			$specificPrice->id_group = (int) ($idGroup);
			$specificPrice->id_customer = (int) $idCustomer;
			$specificPrice->price = (float) ($price);
			$specificPrice->from_quantity = (int) ($fromQuantity);
			$specificPrice->reduction = (float) ($reductionType == 'percentage' ? $reduction / 100 : $reduction);
			$specificPrice->reduction_tax = $reductionTax;
			$specificPrice->reduction_type = $reductionType;
			$specificPrice->from = $from;
			$specificPrice->to = $to;

			if (!$specificPrice->add()) {
				$this->errors[] = Tools::displayError('An error occurred while updating the specific price.');
			}

		}

		$this->errors = array_unique($this->errors);

		if (count($this->errors)) {
			$result = [
				'success' => false,
				'message' => implode(PHP_EOL, $this->errors),
			];
		} else {
			$result = [
				'success' => true,
				'message' => $this->l('News specific price has been added successfully'),
			];
		}

		die(Tools::jsonEncode($result));

	}

	public function ajaxProcessdeleteSpecificPrice() {

		$id_specific_price = Tools::getValue('id_specific_price');
		$specificPrice = new SpecificPrice($id_specific_price);
		$specificPrice->delete();
		$result = [
			'success' => true,
			'message' => $this->l('The specific price has been deleted successfully'),
		];
		die(Tools::jsonEncode($result));
	}

	protected function _applyTaxToEcotax($product) {

		if ($product->ecotax) {
			$product->ecotax = Tools::EPH_round($product->ecotax * (1 + Tax::getProductEcotaxRate() / 100), 2);
		}

	}

	public function initFormSeo($product) {

		if (!$this->default_form_language) {
			$this->getLanguages();
		}

		$data = $this->createTemplate($this->tpl_form);

		$context = $this->context;
		$rewrittenLinks = [];

		if (!Validate::isLoadedObject($product) || !$product->id_category_default) {

			foreach ($this->_languages as $language) {
				$rewrittenLinks[(int) $language['id_lang']] = [$this->l('Unable to determine the preview URL. This product has not been linked with a category, yet.')];
			}

		} else {

			foreach ($this->_languages as $language) {
				$rewrittenLinks[(int) $language['id_lang']] = explode(
					'[REWRITE]',
					$context->link->getProductLink($product->id, '[REWRITE]', (int) $product->id_category_default)
				);
			}

		}

		$data->assign(
			[
				'product'               => $product,
				'languages'             => $this->_languages,
				'id_lang'               => $this->context->language->id,
				'EPH_ssl_enabled'        => Configuration::get('EPH_SSL_ENABLED'),
				'curent_shop_url'       => $this->context->shop->getBaseURL(),
				'default_form_language' => $this->default_form_language,
				'rewritten_links'       => $rewrittenLinks,
			]
		);

		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function initFormAccounting($obj) {

		$data = $this->createTemplate($this->tpl_form);
		$product = $obj;

		$data->assign(
			[

				'product' => $product,
				'link'    => $this->context->link,
			]
		);

		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function ajaxProcessPack() {

		$idProduct = Tools::getValue('id_product');
		$this->tpl_form = 'controllers/products/pack.tpl';
		$product = new Product($idProduct);
		$data = $this->initFormPack($product);
		die($data);

	}

	public function initFormPack($product) {

		$data = $this->createTemplate($this->tpl_form);

		// If pack items have been submitted, we want to display them instead of the actuel content of the pack
		// in database. In case of a submit error, the posted data is not lost and can be sent again.

		if (Tools::getValue('namePackItems')) {
			$inputPackItems = Tools::getValue('inputPackItems');
			$inputNamepackItems = Tools::getValue('namePackItems');
			$packItems = $this->getPackItems();
		} else {
			$product->packItems = Pack::getItems($product->id, $this->context->language->id);
			$packItems = $this->getPackItems($product);
			$inputNamepackItems = '';
			$inputPackItems = '';

			foreach ($packItems as $packItem) {
				$inputPackItems .= $packItem['pack_quantity'] . 'x' . $packItem['id'] . 'x' . $packItem['id_product_attribute'] . '-';
				$inputNamepackItems .= $packItem['pack_quantity'] . ' x ' . $packItem['name'] . '¤';
			}

		}

		$data->assign(
			[
				'input_pack_items'     => $inputPackItems,
				'input_namepack_items' => $inputNamepackItems,
				'pack_items'           => $packItems,
				'product_type'         => (int) Tools::getValue('type_product', $product->getType()),
			]
		);

		return $data->fetch();
	}

	public function getPackItems($product = null) {

		$packItems = [];

		if (!$product) {
			$namesInput = Tools::getValue('namePackItems');
			$idsInput = Tools::getValue('inputPackItems');

			if (!$namesInput || !$idsInput) {
				return [];
			}

			// ids is an array of string with format : QTYxID
			$ids = array_unique(explode('-', $idsInput));
			$names = array_unique(explode('¤', $namesInput));

			if (!empty($ids)) {
				$length = count($ids);

				for ($i = 0; $i < $length; $i++) {

					if (!empty($ids[$i]) && !empty($names[$i])) {
						list($packItems[$i]['pack_quantity'], $packItems[$i]['id']) = explode('x', $ids[$i]);
						$explodedName = explode('x', $names[$i]);
						$packItems[$i]['name'] = $explodedName[1];
					}

				}

			}

		} else {

			if (is_array($product->packItems)) {
				$i = 0;

				foreach ($product->packItems as $packItem) {
					$packItems[$i]['id'] = $packItem->id;
					$packItems[$i]['pack_quantity'] = $packItem->pack_quantity;
					$packItems[$i]['name'] = $packItem->name;
					$packItems[$i]['reference'] = $packItem->reference;
					$packItems[$i]['id_product_attribute'] = isset($packItem->id_pack_product_attribute) && $packItem->id_pack_product_attribute ? $packItem->id_pack_product_attribute : 0;
					$cover = $packItem->id_pack_product_attribute ? Product::getCombinationImageById($packItem->id_pack_product_attribute, $this->context->language->id) : Product::getCover($packItem->id);
					$packItems[$i]['image'] = $this->context->link->getImageLink($packItem->link_rewrite, $cover['id_image'], 'home_default');
					// @todo: don't rely on 'home_default'
					//$path_to_image = _EPH_IMG_DIR_.'p/'.Image::getImgFolderStatic($cover['id_image']).(int)$cover['id_image'].'.jpg';
					//$pack_items[$i]['image'] = ImageManager::thumbnail($path_to_image, 'pack_mini_'.$pack_item->id.'_'.$this->context->shop->id.'.jpg', 120);
					$i++;
				}

			}

		}

		return $packItems;
	}

	public function initFormVirtualProduct($product) {

		$data = $this->createTemplate($this->tpl_form);

		$currency = $this->context->currency;

		$idProductDownload = ProductDownload::getIdFromIdProduct($product->id, false);
		// This might give an empty record, which is fine.
		$productDownload = new ProductDownload($idProductDownload);

		if (!ProductDownload::checkWritableDir()) {
			$this->errors[] = Tools::displayError('Download repository is not writable.');
			$this->tab_display = 'VirtualProduct';
		}

		if ($productDownload->id) {
			// Check the downloadable file.
			$fileNotRight = false;

			if (!$productDownload->filename) {
				$this->errors[] = Tools::displayError('A downloadable file is missing.');
				$fileNotRight = true;
			} else {

				if (!$productDownload->display_filename) {
					$this->errors[] = Tools::displayError('A file name is required.');
					$fileNotRight = true;
				}

				if (!$productDownload->checkFile()) {
					$this->errors[] = Tools::displayError('File on the server is missing, should be') . ' ' . _EPH_DOWNLOAD_DIR_ . $productDownload->filename . '.';
					$fileNotRight = true;
				}

			}

			if ($fileNotRight) {

				if ($productDownload->active) {
					$productDownload->active = false;
					$productDownload->update();
				}

				$this->tab_display = 'VirtualProduct';
			}

		}

		$product->productDownload = $productDownload;

		$virtualProductFileUploader = new HelperUploader('virtual_product_file_uploader');
		$virtualProductFileUploader->setMultiple(false)->setUrl(
			$this->context->link->getAdminLink('AdminProducts') . '&ajax=1&id_product=' . (int) $product->id
			. '&action=AddVirtualProductFile'
		)->setPostMaxSize(Tools::getOctets(ini_get('upload_max_filesize')))
			->setTemplate('virtual_product.tpl');

		if ($productDownload->date_expiration !== '0000-00-00 00:00:00') {
			$productDownload->date_expiration = substr($productDownload->date_expiration, 0, 10);
		} else {
			$productDownload->date_expiration = '';
		}

		$data->assign(
			[
				'product'                       => $product,
				'token'                         => $this->token,
				'currency'                      => $currency,
				'link'                          => $this->context->link,
				'is_file'                       => $product->productDownload->checkFile(),
				'virtual_product_file_uploader' => $virtualProductFileUploader->render(),
			]
		);
		$data->assign($this->tpl_form_vars);
		$this->tpl_form_vars['product'] = $product;
		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function initFormCustomization($obj) {

		$data = $this->createTemplate($this->tpl_form);

		if ((bool) $obj->id) {

			if ($this->product_exists_in_shop) {
				$labels = $obj->getCustomizationFields();

				$hasFileLabels = (int) $this->getFieldValue($obj, 'uploadable_files');
				$hasTextLabels = (int) $this->getFieldValue($obj, 'text_fields');

				$data->assign(
					[
						'obj'                 => $obj,
						'table'               => $this->table,
						'languages'           => $this->_languages,
						'has_file_labels'     => $hasFileLabels,
						'display_file_labels' => $this->_displayLabelFields($obj, $labels, $this->_languages, Configuration::get('EPH_LANG_DEFAULT'), Product::CUSTOMIZE_FILE),
						'has_text_labels'     => $hasTextLabels,
						'display_text_labels' => $this->_displayLabelFields($obj, $labels, $this->_languages, Configuration::get('EPH_LANG_DEFAULT'), Product::CUSTOMIZE_TEXTFIELD),
						'uploadable_files'    => (int) ($this->getFieldValue($obj, 'uploadable_files') ? (int) $this->getFieldValue($obj, 'uploadable_files') : '0'),
						'text_fields'         => (int) ($this->getFieldValue($obj, 'text_fields') ? (int) $this->getFieldValue($obj, 'text_fields') : '0'),
					]
				);
			} else {
				$this->displayWarning($this->l('You must save the product in this shop before adding customization.'));
			}

		} else {
			$this->displayWarning($this->l('You must save this product before adding customization.'));
		}

		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	protected function _displayLabelFields(&$obj, &$labels, $languages, $defaultLanguage, $type) {

		$content = '';
		$type = (int) ($type);
		$labelGenerated = [Product::CUSTOMIZE_FILE => (isset($labels[Product::CUSTOMIZE_FILE]) ? count($labels[Product::CUSTOMIZE_FILE]) : 0), Product::CUSTOMIZE_TEXTFIELD => (isset($labels[Product::CUSTOMIZE_TEXTFIELD]) ? count($labels[Product::CUSTOMIZE_TEXTFIELD]) : 0)];

		$fieldIds = $this->_getCustomizationFieldIds($labels, $labelGenerated, $obj);

		if (isset($labels[$type])) {

			foreach ($labels[$type] as $idCustomizationField => $label) {
				$content .= $this->_displayLabelField($label, $languages, $defaultLanguage, $type, $fieldIds, (int) ($idCustomizationField));
			}

		}

		return $content;
	}

	protected function _getCustomizationFieldIds($labels, $alreadyGenerated, $obj) {

		$customizableFieldIds = [];

		if (isset($labels[Product::CUSTOMIZE_FILE])) {

			foreach ($labels[Product::CUSTOMIZE_FILE] as $idCustomizationField => $label) {
				$customizableFieldIds[] = 'label_' . Product::CUSTOMIZE_FILE . '_' . (int) ($idCustomizationField);
			}

		}

		if (isset($labels[Product::CUSTOMIZE_TEXTFIELD])) {

			foreach ($labels[Product::CUSTOMIZE_TEXTFIELD] as $idCustomizationField => $label) {
				$customizableFieldIds[] = 'label_' . Product::CUSTOMIZE_TEXTFIELD . '_' . (int) ($idCustomizationField);
			}

		}

		$j = 0;

		for ($i = $alreadyGenerated[Product::CUSTOMIZE_FILE]; $i < (int) ($this->getFieldValue($obj, 'uploadable_files')); $i++) {
			$customizableFieldIds[] = 'newLabel_' . Product::CUSTOMIZE_FILE . '_' . $j++;
		}

		$j = 0;

		for ($i = $alreadyGenerated[Product::CUSTOMIZE_TEXTFIELD]; $i < (int) ($this->getFieldValue($obj, 'text_fields')); $i++) {
			$customizableFieldIds[] = 'newLabel_' . Product::CUSTOMIZE_TEXTFIELD . '_' . $j++;
		}

		return implode('¤', $customizableFieldIds);
	}

	protected function _displayLabelField(&$label, $languages, $defaultLanguage, $type, $fieldIds, $idCustomizationField) {

		foreach ($languages as $language) {
			$inputValue[$language['id_lang']] = (isset($label[(int) ($language['id_lang'])])) ? $label[(int) ($language['id_lang'])]['name'] : '';
		}

		$required = (isset($label[(int) ($language['id_lang'])])) ? $label[(int) ($language['id_lang'])]['required'] : false;

		$template = $this->context->smarty->createTemplate(
			'controllers/products/input_text_lang.tpl',
			$this->context->smarty
		);

		return '<div class="form-group">'
		. '<div class="col-lg-6">'
		. $template->assign(
			[
				'languages'   => $languages,
				'input_name'  => 'label_' . $type . '_' . (int) ($idCustomizationField),
				'input_value' => $inputValue,
			]
		)->fetch()
		. '</div>'
		. '<div class="col-lg-6">'
		. '<div class="checkbox">'
		. '<label for="require_' . $type . '_' . (int) ($idCustomizationField) . '">'
		. '<input type="checkbox" name="require_' . $type . '_' . (int) ($idCustomizationField) . '" id="require_' . $type . '_' . (int) ($idCustomizationField) . '" value="1" ' . ($required ? 'checked="checked"' : '') . '/>'
		. $this->l('Required')
			. '</label>'
			. '</div>'
			. '</div>'
			. '</div>';
	}

	public function initFormAttachments($obj) {

		if (!$this->default_form_language) {
			$this->getLanguages();
		}

		$data = $this->createTemplate($this->tpl_form);
		$data->assign('default_form_language', $this->default_form_language);

		if ((bool) $obj->id) {

			if ($this->product_exists_in_shop) {
				$attachmentName = [];
				$attachmentDescription = [];

				foreach ($this->_languages as $language) {
					$attachmentName[$language['id_lang']] = '';
					$attachmentDescription[$language['id_lang']] = '';
				}

				$isoTinyMce = $this->context->language->iso_code;
				$isoTinyMce = (file_exists(_EPH_ROOT_ADMIN_DIR_ . '/js/tinymce/langs/' . $isoTinyMce . '.js') ? $isoTinyMce : 'en');

				$data->assign(
					[
						'obj'                    => $obj,
						'table'                  => $this->table,
						'ad'                     => __EPH_BASE_URI__ . basename(_EPH_ADMIN_DIR_),
						'iso_tiny_mce'           => $isoTinyMce,
						'languages'              => $this->_languages,
						'id_lang'                => $this->context->language->id,
						'attach1'                => Attachment::getAttachments($this->context->language->id, $obj->id, true),
						'attach2'                => Attachment::getAttachments($this->context->language->id, $obj->id, false),
						'default_form_language'  => (int) Configuration::get('EPH_LANG_DEFAULT'),
						'attachment_name'        => $attachmentName,
						'attachment_description' => $attachmentDescription,
						'bo_imgdir'              => _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
					]
				);
			} else {
				$this->displayWarning($this->l('You must save the product in this shop before adding attachements.'));
			}

		} else {
			$this->displayWarning($this->l('You must save this product before adding attachements.'));
		}

		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function initFormInformations($product) {

		if (!$this->default_form_language) {
			$this->getLanguages();
		}

		$data = $this->createTemplate($this->tpl_form);

		$currency = $this->context->currency;

		$data->assign(
			[
				'languages'             => $this->_languages,
				'default_form_language' => $this->default_form_language,
				'currency'              => $currency,
			]
		);
		$this->object = $product;
		//$this->display = 'edit';
		$data->assign('product_name_redirected', Product::getProductName((int) $product->id_product_redirected, null, (int) $this->context->language->id));
		/*
			                    * Form for adding a virtual product like software, mp3, etc...
		*/
		$productDownload = new ProductDownload();

		if ($idProductDownload = $productDownload->getIdFromIdProduct($this->getFieldValue($product, 'id'))) {
			$productDownload = new ProductDownload($idProductDownload);
		}

		$product->{'productDownload'}

		= $productDownload;

		$productProps = [];
		// global informations
		array_push($productProps, 'reference', 'ean13', 'upc', 'available_for_order', 'show_price', 'online_only', 'id_manufacturer');

		// specific / detailled information
		array_push(
			$productProps,
			// physical product
			'width', 'height', 'weight', 'active',
			// virtual product
			'is_virtual', 'cache_default_attribute',
			// customization
			'uploadable_files', 'text_fields'
		);
		// prices
		array_push(
			$productProps,
			'price',
			'wholesale_price',
			'id_tax_rules_group',
			'unit_price_ratio',
			'on_sale',
			'unity',
			'minimum_quantity',
			'additional_shipping_cost',
			'available_now',
			'available_later',
			'available_date'
		);

		if (Configuration::get('EPH_USE_ECOTAX')) {
			array_push($productProps, 'ecotax');
		}

		foreach ($productProps as $prop) {
			$product->$prop = $this->getFieldValue($product, $prop);
		}

		$product->name['class'] = 'updateCurrentText';

		if (!$product->id || Configuration::get('EPH_FORCE_FRIENDLY_PRODUCT')) {
			$product->name['class'] .= ' copy2friendlyUrl';
		}

		$images = Image::getImages($this->context->language->id, $product->id);

		if (is_array($images)) {

			foreach ($images as $k => $image) {
				$images[$k]['src'] = $this->context->link->getImageLink($product->link_rewrite[$this->context->language->id], $product->id . '-' . $image['id_image'], ImageType::getFormatedName('small'));
			}

			$data->assign('images', $images);
		}

		$data->assign('imagesTypes', ImageType::getImagesTypes('products'));

		$product->tags = Tag::getProductTags($product->id);

		$data->assign('product_type', (int) Tools::getValue('type_product', $product->getType()));
		$data->assign('is_in_pack', (int) Pack::isPacked($product->id));

		$checkProductAssociationAjax = false;

		if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_ALL) {
			$checkProductAssociationAjax = true;
		}

		// TinyMCE
		$isoTinyMce = $this->context->language->iso_code;
		$isoTinyMce = (file_exists(_EPH_ROOT_ADMIN_DIR_ . '/js/tinymce/langs/' . $isoTinyMce . '.js') ? $isoTinyMce : 'en');
		$data->assign(
			[
				'ad'                             => dirname($_SERVER['PHP_SELF']),
				'iso_tiny_mce'                   => $isoTinyMce,
				'check_product_association_ajax' => $checkProductAssociationAjax,
				'id_lang'                        => $this->context->language->id,
				'product'                        => $product,
				'token'                          => $this->token,
				'currency'                       => $currency,
				'link'                           => $this->context->link,
				'EPH_PRODUCT_SHORT_DESC_LIMIT'    => Configuration::get('EPH_PRODUCT_SHORT_DESC_LIMIT') ? Configuration::get('EPH_PRODUCT_SHORT_DESC_LIMIT') : 400,
			]
		);
		$data->assign($this->tpl_form_vars);

		$this->tpl_form_vars['product'] = $product;
		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function initFormShipping($obj) {

		$data = $this->createTemplate($this->tpl_form);
		$data->assign(
			[
				'product'                   => $obj,
				'EPH_dimension_unit'         => Configuration::get('EPH_DIMENSION_UNIT'),
				'EPH_weight_unit'            => Configuration::get('EPH_WEIGHT_UNIT'),
				'carrier_list'              => $this->getCarrierList(),
				'currency'                  => $this->context->currency,
				'country_display_tax_label' => $this->context->country->display_tax_label,
			]
		);
		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	protected function getCarrierList() {

		$carrierList = Carrier::getCarriers($this->context->language->id, false, false, false, null, Carrier::ALL_CARRIERS);

		if ($product = $this->loadObject(true)) {
			/** @var Product $product */
			$carrierSelectedList = $product->getCarriers();

			foreach ($carrierList as &$carrier) {

				foreach ($carrierSelectedList as $carrierSelected) {

					if ($carrierSelected['id_reference'] == $carrier['id_reference']) {
						$carrier['selected'] = true;
						continue;
					}

				}

			}

		}

		return $carrierList;
	}

	public function initFormImages($obj) {

		$data = $this->createTemplate($this->tpl_form);

		if ((bool) $obj->id) {

			if ($this->product_exists_in_shop) {
				$data->assign('product', $this->loadObject());

				$shops = false;

				if (Shop::isFeatureActive()) {
					$shops = Shop::getShops();
				}

				if ($shops) {

					foreach ($shops as $key => $shop) {

						if (!$obj->isAssociatedToShop($shop['id_shop'])) {
							unset($shops[$key]);
						}

					}

				}

				$data->assign('shops', $shops);

				$countImages = Db::getInstance()->getValue(
					'
                    SELECT COUNT(id_product)
                    FROM ' . _DB_PREFIX_ . 'image
                    WHERE id_product = ' . (int) $obj->id
				);

				$images = Image::getImages($this->context->language->id, $obj->id);

				foreach ($images as $k => $image) {
					$images[$k] = new Image($image['id_image']);
				}

				if ($this->context->shop->getContext() == Shop::CONTEXT_SHOP) {
					$currentShopId = (int) $this->context->shop->id;
				} else {
					$currentShopId = 0;
				}

				$languages = Language::getLanguages(true);

				$data->assign(
					[
						'countImages'         => $countImages,
						'id_product'          => (int) Tools::getValue('id_product'),
						'id_category_default' => (int) $this->_category->id,
						'images'              => $images,
						'iso_lang'            => $languages[0]['iso_code'],
						'languages'           => $this->_languages,
						'token'               => $this->token,
						'table'               => $this->table,
						'max_image_size'      => $this->max_image_size / 1024 / 1024,
						'currency'            => $this->context->currency,
						'current_shop_id'     => $currentShopId,
						'languages'           => $this->_languages,
						'default_language'    => (int) Configuration::get('EPH_LANG_DEFAULT'),
						'link'                => $this->context->link,
						'base_link'           => $this->context->link->getBaseFrontLink(),
						'bo_imgdir'           => _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
					]
				);

				$type = ImageType::getByNameNType('%', 'products', 'height');

				if (isset($type['name'])) {
					$data->assign('imageType', $type['name']);
				} else {
					$data->assign('imageType', ImageType::getFormatedName('small'));
				}

			} else {
				$this->displayWarning($this->l('You must save the product in this shop before adding images.'));
			}

		} else {
			$this->displayWarning($this->l('You must save this product before adding images.'));
		}

		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function getImagesRequest($idProduct) {

		$obj = new Product($idProduct);
		$images = Image::getImages($this->context->language->id, $obj->id);

		foreach ($images as &$image) {
			$img = new Image($image['id_image']);
			$path = $this->context->link->getBaseFrontLink() . '/img/p/' . $img->getExistingImgPath() . '-small_default.jpg';
			$image['Image'] = '<img src="' . $path . '" class="imgm img-thumbnail">';
			$image['imagePosition'] = $img->position;
			$image['position'] = '<div class="dragGroup"><div class="imagePosition_' . $img->id . ' positions" data-id="' . $img->id . '" data-parent="' . $img->id . '" data-position="' . $img->position . '">' . $img->position . '</div></div>';

			if ($image['cover'] == 1) {
				$image['is_cover'] = 'YES';
				$image['disabled'] = true;
			} else
			if ($image['cover'] == 0) {
				$image['is_cover'] = 'NO';
			}

			$image['delete'] = '<a href="javascript:void(0)" onClick="deleteObject(\'' . $this->controller_name . '\', \'Image\', \'' . $this->l('Delete this image') . '\', \'' . $this->l('Are you sure you want to delete this Image?') . '\', \'' . $this->l('Yes, delete it') . '\', \'' . $this->l('Cancel') . '\', ' . $img->id . ');" class="pull-right btn btn-default" ><i class="fa fa-trash"></i> Supprimer cette image</a>';
		}

		return $images;

	}

	public function ajaxProcessGetImagesRequest() {

		$idProduct = Tools::getValue('id_product');
		die(Tools::jsonEncode($this->getImagesRequest($idProduct)));

	}

	public function AjaxProcessGetImagesFields() {

		die(Tools::jsonEncode([

			[

				'dataIndx' => 'id_image',
				'dataType' => 'integer',
				'hidden'   => true,
			],
			[

				'dataIndx' => 'id_product',
				'dataType' => 'integer',
				'hidden'   => true,
			],
			[

				'dataIndx' => 'cover',
				'dataType' => 'integer',
				'hidden'   => true,
			],
			[

				'dataIndx' => 'disabled',
				'dataType' => 'integer',
				'hidden'   => true,
			],
			[
				'title'     => $this->l('Image'),
				'width'     => 50,
				'exWidth'   => 30,
				'dataIndx'  => 'Image',
				'align'     => 'center',
				'valign'    => 'center',
				'cls'       => 'thumb_product',
				'dataType'  => 'html',
				'editable'  => false,
				'updatable' => false,
				'sortable'  => false,

			],
			[
				'title'     => $this->l('Caption'),
				'minWidth'  => 100,
				'exWidth'   => 30,
				'dataIndx'  => 'legend',
				'editable'  => true,
				'updatable' => false,
				'align'     => 'left',
				'valign'    => 'center',
				'dataType'  => 'string',
			],

			[
				'title'      => ' ',
				'width'      => 10,
				'dataIndx'   => 'imagePosition',
				'dataType'   => 'integer',
				'hidden'     => true,
				'hiddenable' => 'no',
			],

			[
				'title'    => $this->l('Position'),
				'minWidth' => 100,
				'maxWidth' => 100,
				'dataIndx' => 'position',
				'valign'   => 'center',
				'editable' => false,
				'cls'      => 'pointer dragHandle',
				'dataType' => 'html',
				'align'    => 'center',
			],
			[
				'title'    => $this->l('Cover'),
				'width'    => 100,
				'dataIndx' => 'is_cover',
				'valign'   => 'center',
				'type'     => 'checkbox',
				'align'    => 'center',
				'cb'       => [
					'all'      => 0,
					'maxCheck' => 1,
					'check'    => 'YES',
					'uncheck'  => 'NO',
				],

				'render'   => 'imageCheckBoxRender',
			],

			[
				'title'    => $this->l('Delete'),
				'width'    => 100,
				'dataIndx' => 'delete',
				'valign'   => 'center',
				'dataType' => 'html',
				'align'    => 'center',
			],

		]));

	}

	public function ajaxProcessUpdateImageByVal() {

		$idImage = Tools::getValue('idImage');

		$img = new Image($idImage);
		$field = Tools::getValue('field');
		$fieldValue = Tools::getValue('fieldValue');

		if ($field == 'is_cover' && $fieldValue == 'YES') {

			$objects = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
				(new DbQuery())
					->select('id_image')
					->from('image')
					->where('`id_product` = ' . (int) $img->id_product)
			);

			foreach ($objects as $image) {
				Db::getInstance()->execute(
					'UPDATE `' . _DB_PREFIX_ . 'image`
				 SET `cover`= 0
				 WHERE `id_image` =' . (int) $image['id_image']);

			}

			Db::getInstance()->execute(
				'UPDATE `' . _DB_PREFIX_ . 'image`
			 SET `cover`= 1
			 WHERE `id_image` =' . (int) $idImage);

		} else {

			$classVars = get_class_vars(get_class($img));

			if (isset($classVars['definition']['fields'])) {
				$fields = $classVars['definition']['fields'];
			}

			if (array_key_exists('lang', $fields[$field]) && $fields[$field]['lang']) {
				$idLang = Context::getContext()->language->id;
				$img->{$field}
				[(int) $idLang] = $fieldValue;

			} else {
				$img->$field = $fieldValue;
			}

			$result = $img->update();

		}

		$result = [
			'success' => true,
			'message' => $this->l('Default Image has been updated successfully'),
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessUpdateImagePosition() {

		if ($this->tabAccess['edit'] === '0') {
			$this->ajaxDie(json_encode(['error' => $this->l('You do not have the right permission')]));
			$return = [
				'success' => false,
				'message' => $this->l('You do not have the right permission'),
			];

			die(Tools::jsonEncode($return));
		}

		$idImage = Tools::getValue('idImage');
		$id_product = Tools::getValue('id_product');
		$startIndex = Tools::getValue('startIndex');
		$stopIndex = Tools::getValue('stopIndex');
		$image = new Image($idImage);
		$initPosition = $image->position;

		if ($initPosition > $stopIndex) {
			$objects = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
				(new DbQuery())
					->select('id_image,  `position` ')
					->from('image')
					->where('`id_product` = ' . (int) $id_product . ' AND `position` >= ' . (int) $stopIndex . ' AND `position` < ' . (int) $initPosition)
					->orderBy('`position` ASC')
			);

			if (is_array($objects) && count($objects)) {
				$k = $stopIndex + 1;

				foreach ($objects as $moveObject) {
					$result = Db::getInstance()->execute(
						'UPDATE `' . _DB_PREFIX_ . 'image`
						SET `position`= ' . (int) $k . '
						WHERE `id_image` =' . (int) $moveObject['id_image']);
					$k++;
				}

				$result = Db::getInstance()->execute(
					'UPDATE `' . _DB_PREFIX_ . 'image`
					SET `position`= ' . (int) $stopIndex . '
					WHERE `id_image` =' . (int) $idImage);

			}

		} else {
			$objects = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
				(new DbQuery())
					->select('id_image,  `position` ')
					->from('image')
					->where('`id_product` = ' . (int) $id_product . ' AND `position` >>= ' . (int) $initPosition)
					->orderBy('`position` ASC')
			);

			if (is_array($objects) && count($objects)) {
				$k = $initPosition - 1;

				foreach ($objects as $moveObject) {
					$result = Db::getInstance()->execute(
						'UPDATE `' . _DB_PREFIX_ . 'image`
						SET `position`= ' . (int) $k . '
						WHERE `id_image` =' . (int) $moveObject['id_image']);
					$k++;
				}

				$result = Db::getInstance()->execute(
					'UPDATE `' . _DB_PREFIX_ . 'image`
					SET `position`= ' . (int) $stopIndex . '
					WHERE `id_image` =' . (int) $idImage);

			}

		}

		$return = [
			'success' => true,
			'message' => $this->l('Image position has been updated successfully'),
		];

		die(Tools::jsonEncode($return));

	}

	public function ajaxProcessAddNewProductImage() {

		static::$currentIndex = 'index.php?tab=AdminProducts';
		$filetest = fopen("testProcessAddNewProductImage.txt", "w");

		$product = new Product((int) Tools::getValue('id_product'));

		$legends = Tools::getValue('legend');

		if (!is_array($legends)) {
			$legends = (array) $legends;
		}

		if (!Validate::isLoadedObject($product)) {

			$files = [];
			$files[0]['error'] = Tools::displayError('Cannot add image because product creation failed.');
		}

		$imageUploader = new HelperImageUploader('image');
		$imageUploader->setAcceptTypes(['jpeg', 'gif', 'png', 'jpg'])->setMaxSize($this->max_image_size);
		$files = $imageUploader->process();

		foreach ($files as &$file) {

			fwrite($filetest, print_r($file, true));
			$image = new Image();
			$image->id_product = (int) ($product->id);
			$image->position = Image::getHighestPosition($product->id) + 1;

			foreach (Language::getIDs(false) as $idLang) {

				if (!empty($legends)) {
					$image->legend[(int) $idLang] = $legends;
				}

			}

			if (!Image::getCover($image->id_product)) {
				$image->cover = 1;
			} else {
				$image->cover = 0;
			}

			if (isset($file['error']) && (!is_numeric($file['error']) || $file['error'] != 0)) {
				continue;
			}

			fwrite($filetest, print_r($image, true));
			try {

				$result = $image->add();

			} catch (Exception $e) {

				fwrite($filetest, $e->getMessage() . PHP_EOL);
				$result = [
					'success' => false,
					'message' => $e->getMessage(),
				];
			}

			if ($result) {
				$newPath = $image->getPathForCreation();
				fwrite($filetest, $newPath . PHP_EOL);
				$error = 0;

				if (!ImageManager::resize($file['save_path'], $newPath . '.' . $image->image_format, null, null, 'jpg', false, $error)) {

					switch ($error) {
					case ImageManager::ERROR_FILE_NOT_EXIST :
						$file['error'] = Tools::displayError('An error occurred while copying image, the file does not exist anymore.');
						break;

					case ImageManager::ERROR_FILE_WIDTH:
						$file['error'] = Tools::displayError('An error occurred while copying image, the file width is 0px.');
						break;

					case ImageManager::ERROR_MEMORY_LIMIT:
						$file['error'] = Tools::displayError('An error occurred while copying image, check your memory limit.');
						break;

					default:
						$file['error'] = Tools::displayError('An error occurred while copying image.');
						break;
					}

					continue;
				} else {
					$imagesTypes = ImageType::getImagesTypes('products');
					$generateHighDpiImages = (bool) Configuration::get('EPH_HIGHT_DPI');

					foreach ($imagesTypes as $imageType) {

						if (!ImageManager::resize($file['save_path'], $newPath . '-' . stripslashes($imageType['name']) . '.' . $image->image_format, $imageType['width'], $imageType['height'], $image->image_format)) {
							$file['error'] = Tools::displayError('An error occurred while copying image:') . ' ' . stripslashes($imageType['name']);
							continue;
						}

						if ($generateHighDpiImages) {

							if (!ImageManager::resize($file['save_path'], $newPath . '-' . stripslashes($imageType['name']) . '2x.' . $image->image_format, (int) $imageType['width'] * 2, (int) $imageType['height'] * 2, $image->image_format)) {
								$file['error'] = Tools::displayError('An error occurred while copying image:') . ' ' . stripslashes($imageType['name']);
								continue;
							}

						}

					}

				}

				unlink($file['save_path']);
				//Necesary to prevent hacking
				unset($file['save_path']);

				if (!$image->update()) {
					$file['error'] = Tools::displayError('Error while updating status');
					continue;
				}

				$file['status'] = 'ok';
				$file['id'] = $image->id;
				$file['position'] = $image->position;
				$file['cover'] = $image->cover;
				$file['legend'] = $legends;
				$file['path'] = $image->getExistingImgPath();

				@unlink(_EPH_TMP_IMG_DIR_ . 'product_' . (int) $product->id . '.jpg');
				@unlink(_EPH_TMP_IMG_DIR_ . 'product_mini_' . (int) $product->id . '_' . $this->context->shop->id . '.jpg');

				$result = [
					'success'                 => true,
					$imageUploader->getName() => $files,
				];
			}

		}

		die(Tools::jsonEncode($result));
	}

	/**
	 * Initialize combinations form
	 *
	 * @param $obj
	 *
	 * @since 1.9.1.0
	 */
	public function initFormCombinations($obj) {

		return $this->initFormAttributes($obj);
	}

	public function ajaxProcessLaunchNewDeclinaison() {

		$id_product = Tools::getValue('id_product');

		$product = new Product($id_product);

		$data = $this->createTemplate('controllers/products/addcombination.tpl');

		if (!$this->default_form_language) {
			$this->getLanguages();
		}

		if (Validate::isLoadedObject($product)) {

			$attributeJs = [];

			$attributes = Attributes::getAttributes($this->context->language->id, true);

			foreach ($attributes as $k => $attribute) {
				$attributeJs[$attribute['id_attribute_group']][$attribute['id_attribute']] = $attribute['name'];
			}

			foreach ($attributeJs as $k => $ajs) {
				natsort($attributeJs[$k]);
			}

			$currency = $this->context->currency;
			$jsAttribute = [];

			foreach ($attributeJs as $key => $values) {
				$attributeToPush = [];

				foreach ($values as $k => $value) {
					$attributeToPush[$k] = $value;
				}

				$jsAttribute[$key] = $attributeToPush;
			}

			$data->assign('attributeJs', $attributeJs);
			$data->assign('jsAttribute', $jsAttribute);
			$data->assign('attributes_groups', AttributeGroup::getAttributesGroups($this->context->language->id));

			$data->assign('currency', $currency);

			$images = Image::getImages($this->context->language->id, $product->id);

			$data->assign('tax_exclude_option', Tax::excludeTaxeOption());
			$data->assign('EPH_weight_unit', Configuration::get('EPH_WEIGHT_UNIT'));
			$isoTinyMce = $this->context->language->iso_code;
			$isoTinyMce = (file_exists(_EPH_ROOT_ADMIN_DIR_ . '/js/tinymce/langs/' . $isoTinyMce . '.js') ? $isoTinyMce : 'en');
			$currency = $this->context->currency;

			$data->assign(
				[
					'ad'                    => dirname($_SERVER['PHP_SELF']),
					'iso_tiny_mce'          => $isoTinyMce,
					'id_lang'               => $this->context->language->id,
					'token'                 => $this->token,
					'languages'             => $this->_languages,
					'default_form_language' => $this->default_form_language,
					'bo_imgdir'             => _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
				]
			);

			$i = 0;
			$type = ImageType::getByNameNType('%', 'product', 'height');

			if (isset($type['name'])) {
				$data->assign('imageType', $type['name']);
			} else {
				$data->assign('imageType', ImageType::getFormatedName('small'));
			}

			$data->assign('imageWidth', (isset($imageType['width']) ? (int) ($imageType['width']) : 64) + 25);

			foreach ($images as $k => $image) {
				$images[$k]['obj'] = new Image($image['id_image']);
				++$i;
			}

			$data->assign('images', $images);

			$data->assign($this->tpl_form_vars);
			$data->assign(
				[

					'combinationScript'  => $this->buildDeclinaisonScript($product->id),
					'declinaisonFields'  => EmployeeConfiguration::get('EXPERT_EDUCATION_DECLINAISON_FIELDS'),
					'_THEME_PROD_DIR_'   => _THEME_PROD_DIR_,
					'product'            => $product,
					'token_generator'    => Tools::getAdminTokenLite('AdminAttributeGenerator'),
					'combination_exists' => (count(AttributeGroup::getAttributesGroups($this->context->language->id)) > 0 && $product->hasAttributes()),
				]
			);

		}

		$li = '<li id="uperAdddeclinaison" data-controller="AdminDashboard"><a href="#declinaisonAddAdminProducts">Ajouter une nouvelle déclinaisons </a><button type="button" class="close tabdetail" data-id="declinaisonEditAdminProducts"><i class="icon icon-times-circle"></i></button></li>';
		$html = '<div id="declinaisonAddAdminProducts" class="panel col-lg-12" style="display; flow-root;">' . $data->fetch() . '</div>';

		$result = [
			'li'   => $li,
			'html' => $html,
		];
		die(Tools::jsonEncode($result));

	}

	public function initFormAttributes($product) {

		$data = $this->createTemplate($this->tpl_form);

		if (!$this->default_form_language) {
			$this->getLanguages();
		}

		if (Validate::isLoadedObject($product)) {

			if ($this->product_exists_in_shop) {

				$attributeJs = [];

				$attributes = Attributes::getAttributes($this->context->language->id, true);

				foreach ($attributes as $k => $attribute) {
					$attributeJs[$attribute['id_attribute_group']][$attribute['id_attribute']] = $attribute['name'];
				}

				foreach ($attributeJs as $k => $ajs) {
					natsort($attributeJs[$k]);
				}

				$currency = $this->context->currency;
				$jsAttribute = [];

				foreach ($attributeJs as $key => $values) {
					$attributeToPush = [];

					foreach ($values as $k => $value) {
						$attributeToPush[$k] = $value;
					}

					$jsAttribute[$key] = $attributeToPush;
				}

				$data->assign('attributeJs', $attributeJs);
				$data->assign('jsAttribute', $jsAttribute);
				$data->assign('attributes_groups', AttributeGroup::getAttributesGroups($this->context->language->id));

				$data->assign('currency', $currency);

				$images = Image::getImages($this->context->language->id, $product->id);

				$data->assign('tax_exclude_option', Tax::excludeTaxeOption());
				$data->assign('EPH_weight_unit', Configuration::get('EPH_WEIGHT_UNIT'));
				$isoTinyMce = $this->context->language->iso_code;
				$isoTinyMce = (file_exists(_EPH_ROOT_ADMIN_DIR_ . '/js/tinymce/langs/' . $isoTinyMce . '.js') ? $isoTinyMce : 'en');
				$currency = $this->context->currency;

				$data->assign(
					[
						'ad'                    => dirname($_SERVER['PHP_SELF']),
						'iso_tiny_mce'          => $isoTinyMce,
						'id_lang'               => $this->context->language->id,
						'token'                 => $this->token,
						'languages'             => $this->_languages,
						'default_form_language' => $this->default_form_language,
						'link'                  => $this->context->link,

					]
				);

				$i = 0;
				$type = ImageType::getByNameNType('%', 'educations', 'height');

				if (isset($type['name'])) {
					$data->assign('imageType', $type['name']);
				} else {
					$data->assign('imageType', ImageType::getFormatedName('small'));
				}

				$data->assign('imageWidth', (isset($imageType['width']) ? (int) ($imageType['width']) : 64) + 25);

				foreach ($images as $k => $image) {
					$images[$k]['obj'] = new Image($image['id_image']);
					++$i;
				}

				$data->assign('images', $images);

				$data->assign($this->tpl_form_vars);
				$declinaisons = $product->getAttributeCombinations($this->context->language->id);

				$data->assign(
					[

						'combinationScript'  => $this->buildDeclinaisonScript($product->id),
						'declinaisons'       => $declinaisons,
						'declinaisonFields'  => EmployeeConfiguration::get('EXPERT_EDUCATION_DECLINAISON_FIELDS'),
						'_THEME_EDUC_DIR_'   => _THEME_EDUC_DIR_,
						'product'            => $product,
						'token_generator'    => Tools::getAdminTokenLite('AdminAttributeGenerator'),
						'combination_exists' => (count(AttributeGroup::getAttributesGroups($this->context->language->id)) > 0 && $product->hasAttributes()),
						'bo_imgdir'          => __EPH_BASE_URI__ . $this->admin_webpath . _EPH_ADMIN_THEME_DIR_ . $this->bo_theme . '/img/',
						'has_declinaisons'   => $product->hasAttributes(),
					]
				);

			} else {
				$this->displayWarning($this->l('You must save the product in this shop before adding declinaisons.'));
			}

		} else {
			$data->assign('product', $product);
			$this->displayWarning($this->l('You must save this product before adding declinaisons.'));
		}

		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function buildDeclinaisonScript($idProduct) {

		$className = 'Combination';
		$table = 'product_attribute';
		$controller_name = "AdminCombinations";
		$identifier = 'id_product_attribute';

		$paragrid = new ParamGrid($className, $this->controller_name, $table, $identifier);
		$paragrid->paramTable = $table;
		$paragrid->paramController = $this->controller_name;
		$paragrid->requestModel = '{
            location: "remote",
            dataType: "json",
            method: "GET",
            recIndx: "id_product_attribute",
            url: AjaxLinkAdminProducts+"&action=getCombinationRequest&id_product=' . $idProduct . '&ajax=1",
            getData: function (dataJSON) {
                return { data: dataJSON };
                }


        }';
		$paragrid->height = '700';

		//$paragrid->ajaxUrl = 'AjaxLinkAdminEducations + "&action=getProductRequest&ajax=1&idCategory="+getURLParameter(\'idCategory\')';
		$paragrid->showNumberCell = 0;
		$paragrid->pageModel = [
			'type'       => '\'local\'',
			'rPP'        => 40,
			'rPPOptions' => [10, 20, 40, 50, 100, 200, 500],
		];

		$paragrid->rowInit = 'function (ui) {
            return {' . PHP_EOL . '
                    attr: \'data-link="\'+AjaxLink' . $controller_name . '+\'" data-class="' . $className . '" data-rowIndx="\' + ui.rowIndx+\'" data-object="\' + ui.rowData.' . $identifier . '+\' "\',
            };
        }';

		$paragrid->selectionModelType = 'row';

		$paragrid->showTitle = 1;
		$paragrid->title = '\'Declinaisons\'';
		$paragrid->fillHandle = '\'all\'';
		$paragrid->contextMenu = [
			'#grid_' . $controller_name => [
				'selector'  => '\'.pq-body-outer .pq-grid-row\'',
				'animation' => [
					'duration' => 250,
					'show'     => '\'fadeIn\'',
					'hide'     => '\'fadeOut\'',
				],
				'build'     => 'function($triggerElement, e){

                var rowIndex = $($triggerElement).attr("data-rowIndx");
                var rowData = ' . 'grid' . $className . '.getRowData( {rowIndx: rowIndex} );
                selected = selgrid' . $className . '.getSelection().length;
                var dataLenght = grid' . $className . '.option(\'dataModel.data\').length;
                return {
                    callback: function(){},
                    items: {
                        "add": {
                            name: \'' . $this->l('Ajouter une nouvelle déclinaison') . ' \',
                            icon: "add",
                            callback: function(itemKey, opt, e) {
                                var datalink = rowData.addLink;
                                openAjaxProductLink(datalink);
                            }
                        },
                        "edit": {
                            name : \'' . $this->l('Visualiser ou modifier la déclinaison: ') . '\'' . '+rowData.reference,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                var datalink = rowData.openLink;
                                console.log(datalink);
                                openAjaxProductLink(datalink);
                            }
                        },
						"trash": {
                            name : \'' . $this->l('Supprimer la déclinaison: ') . '\'' . '+rowData.reference,
                            icon: "edit",
                            callback: function(itemKey, opt, e) {
                                var datalink = rowData.deleteLink;
                                console.log(datalink);
                                openAjaxProductLink(datalink);
                            }
                        },


                    },
                };
            }',
			]];

		$option = $paragrid->generateParaGridOption();
		return $paragrid->generateParagridScript();
	}

	public function renderListAttributes($product, $currency) {

		$this->bulk_actions = ['delete' => ['text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?')]];
		$this->addRowAction('edit');
		$this->addRowAction('default');
		$this->addRowAction('delete');

		$defaultClass = 'highlighted';

		$this->fields_list = [
			'attributes' => ['title' => $this->l('Attribute - value pair'), 'align' => 'left'],
			'price'      => ['title' => $this->l('Impact on price'), 'type' => 'price', 'align' => 'left'],
			'weight'     => ['title' => $this->l('Impact on weight'), 'align' => 'left'],
			'reference'  => ['title' => $this->l('Reference'), 'align' => 'left'],
			'ean13'      => ['title' => $this->l('EAN-13'), 'align' => 'left'],
			'upc'        => ['title' => $this->l('UPC'), 'align' => 'left'],
		];

		if ($product->id) {
			/* Build attributes combinations */
			$combinations = $product->getAttributeCombinations($this->context->language->id);
			$groups = [];
			$combArray = [];

			if (is_array($combinations)) {
				$combinationImages = $product->getCombinationImages($this->context->language->id);

				foreach ($combinations as $k => $combination) {
					$priceToConvert = Tools::convertPrice($combination['price'], $currency);
					$price = Tools::displayPrice($priceToConvert, $currency);

					$combArray[$combination['id_product_attribute']]['id_product_attribute'] = $combination['id_product_attribute'];
					$combArray[$combination['id_product_attribute']]['attributes'][] = [$combination['group_name'], $combination['attribute_name'], $combination['id_attribute']];
					$combArray[$combination['id_product_attribute']]['wholesale_price'] = $combination['wholesale_price'];
					$combArray[$combination['id_product_attribute']]['price'] = $price;
					$combArray[$combination['id_product_attribute']]['weight'] = $combination['weight'] . Configuration::get('EPH_WEIGHT_UNIT');
					$combArray[$combination['id_product_attribute']]['unit_impact'] = $combination['unit_price_impact'];
					$combArray[$combination['id_product_attribute']]['reference'] = $combination['reference'];
					$combArray[$combination['id_product_attribute']]['ean13'] = $combination['ean13'];
					$combArray[$combination['id_product_attribute']]['upc'] = $combination['upc'];
					$combArray[$combination['id_product_attribute']]['id_image'] = isset($combinationImages[$combination['id_product_attribute']][0]['id_image']) ? $combinationImages[$combination['id_product_attribute']][0]['id_image'] : 0;
					$combArray[$combination['id_product_attribute']]['available_date'] = strftime($combination['available_date']);
					$combArray[$combination['id_product_attribute']]['default_on'] = $combination['default_on'];

					if ($combination['is_color_group']) {
						$groups[$combination['id_attribute_group']] = $combination['group_name'];
					}

				}

			}

			if (isset($combArray)) {

				foreach ($combArray as $id_product_attribute => $product_attribute) {
					$list = '';

					/* In order to keep the same attributes order */
					asort($product_attribute['attributes']);

					foreach ($product_attribute['attributes'] as $attribute) {
						$list .= $attribute[0] . ' - ' . $attribute[1] . ', ';
					}

					$list = rtrim($list, ', ');
					$combArray[$id_product_attribute]['image'] = $product_attribute['id_image'] ? new Image($product_attribute['id_image']) : false;
					$combArray[$id_product_attribute]['available_date'] = $product_attribute['available_date'] != 0 ? date('Y-m-d', strtotime($product_attribute['available_date'])) : '0000-00-00';
					$combArray[$id_product_attribute]['attributes'] = $list;
					$combArray[$id_product_attribute]['name'] = $list;

					if ($product_attribute['default_on']) {
						$combArray[$id_product_attribute]['class'] = $defaultClass;
					}

				}

			}

		}

		foreach ($this->actions_available as $action) {

			if (!in_array($action, $this->actions) && isset($this->$action) && $this->$action) {
				$this->actions[] = $action;
			}

		}

		$helper = new HelperList();
		$helper->identifier = 'id_product_attribute';
		$helper->table_id = 'combinations-list';
		$helper->token = $this->token;
		$helper->currentIndex = static::$currentIndex;
		$helper->no_link = true;
		$helper->simple_header = true;
		$helper->show_toolbar = false;
		$helper->shopLinkType = $this->shopLinkType;
		$helper->actions = $this->actions;
		$helper->list_skip_actions = $this->list_skip_actions;
		$helper->colorOnBackground = true;
		$helper->override_folder = $this->tpl_folder . 'combination/';

		return $helper->generateList($combArray, $this->fields_list);
	}

	public function initFormQuantities($obj) {

		if (!$this->default_form_language) {
			$this->getLanguages();
		}

		$data = $this->createTemplate($this->tpl_form);
		$data->assign('default_form_language', $this->default_form_language);

		if ($obj->id) {

			if ($this->product_exists_in_shop) {
				// Get all id_product_attribute
				$attributes = $obj->getAttributesResume($this->context->language->id);

				if (empty($attributes)) {
					$attributes[] = [
						'id_product_attribute'  => 0,
						'attribute_designation' => '',
					];
				}

				// Get available quantities
				$available_quantity = [];
				$product_designation = [];

				foreach ($attributes as $attribute) {
					// Get available quantity for the current product attribute in the current shop
					$available_quantity[$attribute['id_product_attribute']] = isset($attribute['id_product_attribute']) && $attribute['id_product_attribute'] ? (int) $attribute['quantity'] : (int) $obj->quantity;
					// Get all product designation
					$product_designation[$attribute['id_product_attribute']] = rtrim(
						$obj->name[$this->context->language->id] . ' - ' . $attribute['attribute_designation'],
						' - '
					);
				}

				$show_quantities = true;
				$shop_context = Shop::getContext();
				$shop_group = new ShopGroup((int) Shop::getContextShopGroupID());

				// if we are in all shops context, it's not possible to manage quantities at this level

				if (Shop::isFeatureActive() && $shop_context == Shop::CONTEXT_ALL) {
					$show_quantities = false;
				}

				// if we are in group shop context
				else

				if (Shop::isFeatureActive() && $shop_context == Shop::CONTEXT_GROUP) {
					// if quantities are not shared between shops of the group, it's not possible to manage them at group level

					if (!$shop_group->share_stock) {
						$show_quantities = false;
					}

				}

				// if we are in shop context
				else

				if (Shop::isFeatureActive()) {
					// if quantities are shared between shops of the group, it's not possible to manage them for a given shop

					if ($shop_group->share_stock) {
						$show_quantities = false;
					}

				}

				$data->assign('EPH_stock_management', Configuration::get('EPH_STOCK_MANAGEMENT'));
				$data->assign('has_attribute', $obj->hasAttributes());
				// Check if product has combination, to display the available date only for the product or for each combination

				if (Combination::isFeatureActive()) {
					$data->assign('countAttributes', (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
						(new DbQuery())
							->select('COUNT(`id_product`)')

							->from('product_attribute')
							->where('`id_product` = ' . (int) $obj->id)
					));
				} else {
					$data->assign('countAttributes', false);
				}

				// if advanced stock management is active, checks associations
				$advanced_stock_management_warning = false;

				if (Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT') && $obj->advanced_stock_management) {
					$p_attributes = Product::getProductAttributesIds($obj->id);
					$warehouses = [];

					if (!$p_attributes) {
						$warehouses[] = Warehouse::getProductWarehouseList($obj->id, 0);
					}

					foreach ($p_attributes as $p_attribute) {
						$ws = Warehouse::getProductWarehouseList($obj->id, $p_attribute['id_product_attribute']);

						if ($ws) {
							$warehouses[] = $ws;
						}

					}

					$warehouses = Tools::arrayUnique($warehouses);

					if (empty($warehouses)) {
						$advanced_stock_management_warning = true;
					}

				}

				if ($advanced_stock_management_warning) {
					$this->displayWarning($this->l('If you wish to use the advanced stock management, you must:'));
					$this->displayWarning('- ' . $this->l('associate your products with warehouses.'));
					$this->displayWarning('- ' . $this->l('associate your warehouses with carriers.'));
					$this->displayWarning('- ' . $this->l('associate your warehouses with the appropriate shops.'));
				}

				$pack_quantity = null;
				// if product is a pack

				if (Pack::isPack($obj->id)) {
					$items = Pack::getItems((int) $obj->id, Configuration::get('EPH_LANG_DEFAULT'));

					// gets an array of quantities (quantity for the product / quantity in pack)
					$pack_quantities = [];

					foreach ($items as $item) {
						/** @var Product $item */

						if (!$item->isAvailableWhenOutOfStock((int) $item->out_of_stock)) {
							$pack_id_product_attribute = Product::getDefaultAttribute($item->id, 1);
							$pack_quantities[] = Product::getQuantity($item->id, $pack_id_product_attribute) / ($item->pack_quantity !== 0 ? $item->pack_quantity : 1);
						}

					}

					// gets the minimum

					if (count($pack_quantities)) {
						$pack_quantity = $pack_quantities[0];

						foreach ($pack_quantities as $value) {

							if ($pack_quantity > $value) {
								$pack_quantity = $value;
							}

						}

					}

					if (Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT') && !Warehouse::getPackWarehouses((int) $obj->id)) {
						$this->displayWarning($this->l('You must have a common warehouse between this pack and its product.'));
					}

				}

				$data->assign(
					[
						'attributes'              => $attributes,
						'available_quantity'      => $available_quantity,
						'pack_quantity'           => $pack_quantity,
						'stock_management_active' => Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT'),
						'product_designation'     => $product_designation,
						'product'                 => $obj,
						'show_quantities'         => $show_quantities,
						'order_out_of_stock'      => Configuration::get('EPH_ORDER_OUT_OF_STOCK'),
						'pack_stock_type'         => Configuration::get('EPH_PACK_STOCK_TYPE'),
						'token_preferences'       => Tools::getAdminTokenLite('AdminPPreferences'),

						'token'                   => $this->token,
						'languages'               => $this->_languages,
						'id_lang'                 => $this->context->language->id,
					]
				);
			} else {
				$this->displayWarning($this->l('You must save the product in this shop before managing quantities.'));
			}

		} else {
			$this->displayWarning($this->l('You must save this product before managing quantities.'));
		}

		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function initFormSuppliers($obj) {

		$data = $this->createTemplate($this->tpl_form);

		if ($obj->id) {

			if ($this->product_exists_in_shop) {
				// Get all id_product_attribute
				$attributes = $obj->getAttributesResume($this->context->language->id);

				if (empty($attributes)) {
					$attributes[] = [
						'id_product'            => $obj->id,
						'id_product_attribute'  => 0,
						'attribute_designation' => '',
					];
				}

				$product_designation = [];

				foreach ($attributes as $attribute) {
					$product_designation[$attribute['id_product_attribute']] = rtrim(
						$obj->name[$this->context->language->id] . ' - ' . $attribute['attribute_designation'],
						' - '
					);
				}

				// Get all available suppliers
				$suppliers = Supplier::getSuppliers();

				// Get already associated suppliers
				$associated_suppliers = ProductSupplier::getSupplierCollection($obj->id);

				// Get already associated suppliers and force to retreive product declinaisons
				$product_supplier_collection = ProductSupplier::getSupplierCollection($obj->id, false);

				$default_supplier = 0;

				foreach ($suppliers as &$supplier) {
					$supplier['is_selected'] = false;
					$supplier['is_default'] = false;

					foreach ($associated_suppliers as $associated_supplier) {
						/** @var ProductSupplier $associated_supplier */

						if ($associated_supplier->id_supplier == $supplier['id_supplier']) {
							$associated_supplier->name = $supplier['name'];
							$supplier['is_selected'] = true;

							if ($obj->id_supplier == $supplier['id_supplier']) {
								$supplier['is_default'] = true;
								$default_supplier = $supplier['id_supplier'];
							}

						}

					}

				}

				$data->assign(
					[
						'attributes'                      => $attributes,
						'suppliers'                       => $suppliers,
						'default_supplier'                => $default_supplier,
						'associated_suppliers'            => $associated_suppliers,
						'associated_suppliers_collection' => $product_supplier_collection,
						'product_designation'             => $product_designation,
						'currencies'                      => Currency::getCurrencies(),
						'product'                         => $obj,
						'link'                            => $this->context->link,
						'token'                           => $this->token,
						'id_default_currency'             => Configuration::get('EPH_CURRENCY_DEFAULT'),
					]
				);
			} else {
				$this->displayWarning($this->l('You must save the product in this shop before managing suppliers.'));
			}

		} else {
			$this->displayWarning($this->l('You must save this product before managing suppliers.'));
		}

		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function initFormWarehouses($obj) {

		$data = $this->createTemplate($this->tpl_form);

		if ($obj->id) {

			if ($this->product_exists_in_shop) {
				// Get all id_product_attribute
				$attributes = $obj->getAttributesResume($this->context->language->id);

				if (empty($attributes)) {
					$attributes[] = [
						'id_product'            => $obj->id,
						'id_product_attribute'  => 0,
						'attribute_designation' => '',
					];
				}

				$product_designation = [];

				foreach ($attributes as $attribute) {
					$product_designation[$attribute['id_product_attribute']] = rtrim(
						$obj->name[$this->context->language->id] . ' - ' . $attribute['attribute_designation'],
						' - '
					);
				}

				// Get all available warehouses
				$warehouses = Warehouse::getWarehouses(true);

				// Get already associated warehouses
				$associated_warehouses_collection = WarehouseProductLocation::getCollection($obj->id);

				$data->assign(
					[
						'attributes'            => $attributes,
						'warehouses'            => $warehouses,
						'associated_warehouses' => $associated_warehouses_collection,
						'product_designation'   => $product_designation,
						'product'               => $obj,
						'link'                  => $this->context->link,
						'token'                 => $this->token,
					]
				);
			} else {
				$this->displayWarning($this->l('You must save the product in this shop before managing warehouses.'));
			}

		} else {
			$this->displayWarning($this->l('You must save this product before managing warehouses.'));
		}

		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function initFormFeatures($obj) {

		if (!$this->default_form_language) {
			$this->getLanguages();
		}

		$data = $this->createTemplate($this->tpl_form);
		$data->assign('default_form_language', $this->default_form_language);
		$data->assign('languages', $this->_languages);

		if (!Feature::isFeatureActive()) {
			$this->displayWarning($this->l('This feature has been disabled. ') . ' <a href="index.php?tab=AdminPerformance&token=' . Tools::getAdminTokenLite('AdminPerformance') . '#featuresDetachables">' . $this->l('Performances') . '</a>');
		} else {

			if ($obj->id) {

				if ($this->product_exists_in_shop) {
					$features = Feature::getFeatures($this->context->language->id, (Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP));

					foreach ($features as $k => $tab_features) {
						$features[$k]['current_item'] = false;
						$features[$k]['val'] = [];

						$custom = true;

						foreach ($obj->getFeatures() as $tab_products) {

							if ($tab_products['id_feature'] == $tab_features['id_feature']) {
								$features[$k]['current_item'] = $tab_products['id_feature_value'];
							}

						}

						$features[$k]['featureValues'] = FeatureValue::getFeatureValuesWithLang($this->context->language->id, (int) $tab_features['id_feature']);

						if (count($features[$k]['featureValues'])) {

							foreach ($features[$k]['featureValues'] as $value) {

								if ($features[$k]['current_item'] == $value['id_feature_value']) {
									$custom = false;
								}

							}

						}

						if ($custom) {
							$feature_values_lang = FeatureValue::getFeatureValueLang($features[$k]['current_item']);

							foreach ($feature_values_lang as $feature_value) {
								$features[$k]['val'][$feature_value['id_lang']] = $feature_value;
							}

						}

					}

					$data->assign('available_features', $features);
					$data->assign('product', $obj);
					$data->assign('link', $this->context->link);
					$data->assign('default_form_language', $this->default_form_language);
				} else {
					$this->displayWarning($this->l('You must save the product in this shop before adding features.'));
				}

			} else {
				$this->displayWarning($this->l('You must save this product before adding features.'));
			}

		}

		$this->tpl_form_vars['custom_form'] = $data->fetch();
	}

	public function ajaxProcessProductQuantity() {

		if ($this->tabAccess['edit'] === '0') {
			$this->ajaxDie(json_encode(['error' => $this->l('You do not have the right permission')]));
		}

		if (!Tools::getValue('actionQty')) {
			$this->ajaxDie(json_encode(['error' => $this->l('Undefined action')]));
		}

		$product = new Product((int) Tools::getValue('id_product'), true);

		switch (Tools::getValue('actionQty')) {
		case 'depends_on_stock':

			if (Tools::getValue('value') === false) {
				$this->ajaxDie(json_encode(['error' => $this->l('Undefined value')]));
			}

			if ((int) Tools::getValue('value') != 0 && (int) Tools::getValue('value') != 1) {
				$this->ajaxDie(json_encode(['error' => $this->l('Incorrect value')]));
			}

			if (!$product->advanced_stock_management && (int) Tools::getValue('value') == 1) {
				$this->ajaxDie(json_encode(['error' => $this->l('Not possible if advanced stock management is disabled. ')]));
			}

			if (Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT') && (int) Tools::getValue('value') == 1 && (Pack::isPack($product->id) && !Pack::allUsesAdvancedStockManagement($product->id)
				&& ($product->pack_stock_type == 2 || $product->pack_stock_type == 1 ||
					($product->pack_stock_type == 3 && (Configuration::get('EPH_PACK_STOCK_TYPE') == 1 || Configuration::get('EPH_PACK_STOCK_TYPE') == 2))))
			) {
				$this->ajaxDie(
					json_encode(
						[
							'error' => $this->l('You cannot use advanced stock management for this pack because') . '<br />' .
							$this->l('- advanced stock management is not enabled for these products') . '<br />' .
							$this->l('- you have chosen to decrement products quantities.'),
						]
					)
				);
			}

			StockAvailable::setProductDependsOnStock($product->id, (int) Tools::getValue('value'));
			break;

		case 'pack_stock_type':
			$value = Tools::getValue('value');

			if ($value === false) {
				$this->ajaxDie(json_encode(['error' => $this->l('Undefined value')]));
			}

			if ((int) $value != 0 && (int) $value != 1
				&& (int) $value != 2 && (int) $value != 3
			) {
				$this->ajaxDie(json_encode(['error' => $this->l('Incorrect value')]));
			}

			if ($product->depends_on_stock && !Pack::allUsesAdvancedStockManagement($product->id) && ((int) $value == 1
				|| (int) $value == 2 || ((int) $value == 3 && (Configuration::get('EPH_PACK_STOCK_TYPE') == 1 || Configuration::get('EPH_PACK_STOCK_TYPE') == 2)))
			) {
				$this->ajaxDie(
					json_encode(
						[
							'error' => $this->l('You cannot use this stock management option because:') . '<br />' .
							$this->l('- advanced stock management is not enabled for these products') . '<br />' .
							$this->l('- advanced stock management is enabled for the pack'),
						]
					)
				);
			}

			Product::setPackStockType($product->id, $value);
			break;

		case 'out_of_stock':

			if (Tools::getValue('value') === false) {
				$this->ajaxDie(json_encode(['error' => $this->l('Undefined value')]));
			}

			if (!in_array((int) Tools::getValue('value'), [0, 1, 2])) {
				$this->ajaxDie(json_encode(['error' => $this->l('Incorrect value')]));
			}

			StockAvailable::setProductOutOfStock($product->id, (int) Tools::getValue('value'));
			break;

		case 'set_qty':

			if (Tools::getValue('value') === false || (!is_numeric(trim(Tools::getValue('value'))))) {
				$this->ajaxDie(json_encode(['error' => $this->l('Undefined value')]));
			}

			if (Tools::getValue('id_product_attribute') === false) {
				$this->ajaxDie(json_encode(['error' => $this->l('Undefined id product attribute')]));
			}

			StockAvailable::setQuantity($product->id, (int) Tools::getValue('id_product_attribute'), (int) Tools::getValue('value'));
			Hook::exec('actionProductUpdate', ['id_product' => (int) $product->id, 'product' => $product]);

			// Catch potential echo from modules
			$error = ob_get_contents();

			if (!empty($error)) {
				ob_end_clean();
				$this->ajaxDie(json_encode(['error' => $error]));
			}

			break;
		case 'advanced_stock_management':

			if (Tools::getValue('value') === false) {
				$this->ajaxDie(json_encode(['error' => $this->l('Undefined value')]));
			}

			if ((int) Tools::getValue('value') != 1 && (int) Tools::getValue('value') != 0) {
				$this->ajaxDie(json_encode(['error' => $this->l('Incorrect value')]));
			}

			if (!Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT') && (int) Tools::getValue('value') == 1) {
				$this->ajaxDie(json_encode(['error' => $this->l('Not possible if advanced stock management is disabled. ')]));
			}

			$product->setAdvancedStockManagement((int) Tools::getValue('value'));

			if (StockAvailable::dependsOnStock($product->id) == 1 && (int) Tools::getValue('value') == 0) {
				StockAvailable::setProductDependsOnStock($product->id, 0);
			}

			break;

		}

		$this->ajaxDie(json_encode(['error' => false]));
	}

	public function initFormModules($obj) {

		$idModule = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('`id_module`')
				->from('module')
				->where('`name` = \'' . pSQL($this->tab_display_module) . '\'')
		);
		$this->tpl_form_vars['custom_form'] = Hook::exec('displayAdminProductsExtra', [], (int) $idModule);
	}

	public function getL($key) {

		$trad = [
			'Default category:'                                                 => $this->l('Default category'),
			'Catalog:'                                                          => $this->l('Catalog'),
			'Consider changing the default category.'                           => $this->l('Consider changing the default category.'),
			'ID'                                                                => $this->l('ID'),
			'Name'                                                              => $this->l('Name'),
			'Mark all checkbox(es) of categories in which product is to appear' => $this->l('Mark the checkbox of each categories in which this product will appear.'),
		];

		return $trad[$key];
	}

	public function ajaxProcessCheckProductName() {

		if ($this->tabAccess['view'] === '1') {
			$search = Tools::getValue('q');
			$id_lang = Tools::getValue('id_lang');
			$limit = Tools::getValue('limit');

			if ($this->context->shop->getContext() != Shop::CONTEXT_SHOP) {
				$result = false;
			} else {
				$result = Db::getInstance()->executeS(
					'
                    SELECT DISTINCT pl.`name`, p.`id_product`, pl.`id_shop`
                    FROM `' . _DB_PREFIX_ . 'product` p
                    LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                        ON (pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . (int) $id_lang . ')
                    WHERE pl.`name` LIKE "%' . pSQL($search) . '%" AND ps.id_product IS NULL
                    GROUP BY pl.`id_product`
                    LIMIT ' . (int) $limit
				);
			}

			$this->ajaxDie(json_encode($result));
		}

	}

	public function ajaxProcessUpdatePositions() {

		if ($this->tabAccess['edit'] === '1') {
			$way = (int) (Tools::getValue('way'));
			$id_product = (int) Tools::getValue('id_product');
			$id_category = (int) Tools::getValue('id_category');
			$positions = Tools::getValue('product');
			$page = (int) Tools::getValue('page');
			$selected_pagination = (int) Tools::getValue('selected_pagination');

			if (is_array($positions)) {

				foreach ($positions as $position => $value) {
					$pos = explode('_', $value);

					if ((isset($pos[1]) && isset($pos[2])) && ($pos[1] == $id_category && (int) $pos[2] === $id_product)) {

						if ($page > 1) {
							$position = $position + (($page - 1) * $selected_pagination);
						}

						if ($product = new Product((int) $pos[2])) {

							if (isset($position) && $product->updatePosition($way, $position)) {
								$category = new Category((int) $id_category);

								if (Validate::isLoadedObject($category)) {
									hook::Exec('categoryUpdate', ['category' => $category]);
								}

								echo 'ok position ' . (int) $position . ' for product ' . (int) $pos[2] . "\r\n";
							} else {
								echo '{"hasError" : true, "errors" : "Can not update product ' . (int) $id_product . ' to position ' . (int) $position . ' "}';
							}

						} else {
							echo '{"hasError" : true, "errors" : "This product (' . (int) $id_product . ') can t be loaded"}';
						}

						break;
					}

				}

			}

		}

	}

	public function ajaxProcessPublishProduct() {

		if ($this->tabAccess['edit'] === '1') {

			if ($id_product = (int) Tools::getValue('id_product')) {
				$bo_product_url = dirname($_SERVER['PHP_SELF']) . '/index.php?tab=AdminProducts&id_product=' . $id_product . '&updateproduct&token=' . $this->token;

				if (Tools::getValue('redirect')) {
					die($bo_product_url);
				}

				$product = new Product((int) $id_product);

				if (!Validate::isLoadedObject($product)) {
					die('error: invalid id');
				}

				$product->active = 1;

				if ($product->save()) {
					die($bo_product_url);
				} else {
					die('error: saving');
				}

			}

		}

	}

	public function displayPreviewLink($id, $token = null, $name = null) {

		$tpl = $this->createTemplate('helpers/list/list_action_preview.tpl');

		if (!array_key_exists('Bad SQL query', static::$cache_lang)) {
			static::$cache_lang['Preview'] = $this->l('Preview', 'Helper');
		}

		$tpl->assign(
			[
				'href'   => $this->getPreviewUrl(new Product((int) $id)),
				'action' => static::$cache_lang['Preview'],
			]
		);

		return $tpl->fetch();
	}

	protected function processBulkDelete() {

		if ($this->tabAccess['delete'] === '1') {

			if (is_array($this->boxes) && !empty($this->boxes)) {
				$object = new $this->className();

				if (isset($object->noZeroObject) &&
					// Check if all object will be deleted
					(count(call_user_func([$this->className, $object->noZeroObject])) <= 1 || count($_POST[$this->table . 'Box']) == count(call_user_func([$this->className, $object->noZeroObject])))
				) {
					$this->errors[] = Tools::displayError('You need at least one object.') . ' <b>' . $this->table . '</b><br />' . Tools::displayError('You cannot delete all of the items.');
				} else {
					$success = 1;
					$products = Tools::getValue($this->table . 'Box');

					if (is_array($products) && ($count = count($products))) {
						// Deleting products can be quite long on a cheap server. Let's say 1.5 seconds by product (I've seen it!).

						if (intval(ini_get('max_execution_time')) < round($count * 1.5)) {
							ini_set('max_execution_time', round($count * 1.5));
						}

						if (Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT')) {
							$stockManager = StockManagerFactory::getManager();
						}

						foreach ($products as $id_product) {
							$product = new Product((int) $id_product);
							/*
								                                                             * @since 1.5.0
								                                                             * It is NOT possible to delete a product if there are currently:
								                                                             * - physical stock for this product
								                                                             * - supply order(s) for this product
							*/

							if (Configuration::get('EPH_ADVANCED_STOCK_MANAGEMENT') && $product->advanced_stock_management) {
								$physical_quantity = $stockManager->getProductPhysicalQuantities($product->id, 0);
								$real_quantity = $stockManager->getProductRealQuantities($product->id, 0);

								if ($physical_quantity > 0 || $real_quantity > $physical_quantity) {
									$this->errors[] = sprintf(Tools::displayError('You cannot delete the product #%d because there is physical stock left.'), $product->id);
								}

							}

							if (!count($this->errors)) {

								if ($product->delete()) {
									Logger::addLog(sprintf($this->l('%s deletion', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int) $product->id, true, (int) $this->context->employee->id);
								} else {
									$success = false;
								}

							} else {
								$success = 0;
							}

						}

					}

					if ($success) {
						$id_category = (int) Tools::getValue('id_category');
						$category_url = empty($id_category) ? '' : '&id_category=' . (int) $id_category;
						$this->redirect_after = static::$currentIndex . '&conf=2&token=' . $this->token . $category_url;
					} else {
						$this->errors[] = Tools::displayError('An error occurred while deleting this selection.');
					}

				}

			} else {
				$this->errors[] = Tools::displayError('You must select at least one element to delete.');
			}

		} else {
			$this->errors[] = Tools::displayError('You do not have permission to delete this.');
		}

	}

	protected function updateAssoShop($idObject) {

		return;
	}

	protected function _getFinalPrice($specificPrice, $productPrice, $taxRate) {

		return $this->object->getPrice(false, $specificPrice['id_product_attribute'], 2);
	}

	protected function _displayUnavailableProductWarning() {

		$content = '<div class="alert">
            <span>' . $this->l('Your product will be saved as a draft.') . '</span>
                <a href="#" class="btn btn-default pull-right" onclick="submitAddProductAndPreview()" ><i class="icon-external-link-sign"></i> ' . $this->l('Save and preview') . '</a>
                <input type="hidden" name="fakeSubmitAddProductAndPreview" id="fakeSubmitAddProductAndPreview" />
            </div>';
		$this->tpl_form_vars['warning_unavailable_product'] = $content;
	}

	public function ajaxProcessAutoCompleteSearch() {

		$keyword = Tools::getValue('keyword', false);

		if (!$keyword || $keyword == '' || Tools::strlen($keyword) < 1) {
			exit();
		}

		if ($pos = strpos($keyword, ' (ref:')) {
			$keyword = Tools::substr($keyword, 0, $pos);
		}

		$exclude_ids = Tools::getValue('excludeIds', false);

		if ($exclude_ids && $exclude_ids != 'NaN') {
			$exclude_ids = implode(',', array_map('intval', explode(',', $exclude_ids)));
		} else {
			$exclude_ids = '';
		}

		// Excluding downloadable products from packs because download from pack is not supported
		$exclude_virtuals = (bool) Tools::getValue('excludeVirtuals', false);
		$exclude_packs = (bool) Tools::getValue('exclude_packs', false);
		$items = MaSearch::searchAccessories($exclude_ids, $keyword, $exclude_virtuals, $exclude_packs);
		$acc = (bool) Tools::isSubmit('excludeIds');

		if ($items && $acc) {
			$results = [];
			header('Content-Type: application/json');

			foreach ($items as $item) {
				$results[] = [
					'product' => trim($item['name']) . (!empty($item['reference']) ? ' (ref: ' . $item['reference'] . ')' : ''),
					'id'      => (int) $item['id_product']];

			}

			die(Tools::jsonEncode(array_values($results)));
		} else

		if ($items) {
			// packs
			$results = [];

			foreach ($items as $item) {
				$product = [
					'id'    => (int) $item['id_product'],
					'name'  => $item['name'],
					'ref'   => (!empty($item['reference']) ? $item['reference'] : ''),
					'image' => str_replace('http://', Tools::getShopProtocol(), Context::getContext()->link->getImageLink($item['link_rewrite'], $item['id_image'], ImageType::getFormatedName('home'))),
				];
				array_push($results, $product);
			}

			die($results);
		} else {
			Tools::jsonEncode(new stdClass());
		}

	}

	public function ajaxProcessInactiveProduct() {

		$idProduct = Tools::getValue('idProduct');
		$product = new Product($idProduct, true);
		$product->active = 0;
		$product->update();

		$result = [
			'success' => true,
			'message' => $this->l('Le produit a été désactivé avec succès'),
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessActiveProduct() {

		$idProduct = Tools::getValue('idProduct');
		$product = new Product($idProduct, true);
		$product->active = 1;
		$product->update();

		$result = [
			'success' => true,
			'message' => $this->l('Le produit a été activé avec succès'),
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessAvailableProduct() {

		$idProduct = Tools::getValue('idProduct');
		$product = new Product($idProduct, true);
		$product->available_for_order = 1;
		$product->update();

		$result = [
			'success' => true,
			'message' => $this->l('Product has been availabled for order'),
		];

		die(Tools::jsonEncode($result));
	}

	public function ajaxProcessInavailableProduct() {

		$idProduct = Tools::getValue('idProduct');
		$product = new Product($idProduct, true);
		$product->available_for_order = 0;
		$product->update();

		$result = [
			'success' => true,
			'message' => $this->l('Product has been tunr unavailabled for order'),
		];

		die(Tools::jsonEncode($result));
	}

}
