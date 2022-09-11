<?php

/**
 * Class HelperCategoriesTreeCore
 *
 * @since 3.1.0.0
 */
class HelperCategoriesTreeCore extends Helper {

  
	protected $fields_tree = [];
	public $category_tree =[];
    protected $disabled_categories;
    protected $id_product;
	public $identifier;
    protected $lang;
    protected $root_category;
    protected $selected_categories;
    protected $use_checkbox;
    protected $use_search;
    
	
    public function __construct() {

        $this->base_folder = 'helpers/categories/';
       
		$this->base_tpl = 'categories.tpl';
        parent::__construct();
    }

   
	public function generateTree($fieldsTree) {

        $this->fields_tree = $fieldsTree;

        return $this->generate();
    }
	
	public function generate() {

        $this->tpl = $this->createTemplate($this->base_tpl);

       
		$paragridScript = $this->generateCategoryGridScript($this->category_tree, $this->identifier);
		$this->context->smarty->assign('categoryScript', $paragridScript);



        return parent::generate();
    }
	
	public function generateCategoryGridScript($category_tree, $identifier) {

		$paragrid = new ParamGrid('AsssociatedCategory', 'AdminAssociatedCategory', $this->table, $this->identifier);
		$paragrid->height = 500;
		$paragrid->showNumberCell = 0;
		$paragrid->showTitle = 1;
        $paragrid->title =  '\'' . $this->l('Filter by Category') . '\'';
		$paragrid->selectionModelType = 'null';
		$paragrid->needRequestModel = false;
		$paragrid->dataModel  = [
			'data' => $category_tree
		];
		$paragrid->toolbar = [
			'items' => [

				[
					'type'     => '\'button\'',
					'icon'     => '\'ui-icon-disk\'',
					'label'    => '\'' . $this->l('Tout réduire') . '\'',
					'listener' => 'function () {' . PHP_EOL . '
                       this.Tree().collapseAll();' . PHP_EOL . '
                    }' . PHP_EOL,
				],

				[
					'type'  => '\'button\'',
					'icon'  => '\'ui-icon-disk\'',
					'label' => '\'' . $this->l('Développer tout') . '\'',
					'listener' => 'function () {' . PHP_EOL . '
                       this.Tree().expandAll();' . PHP_EOL . '
                    }' . PHP_EOL,
				],
			],
		];
		$paragrid->colModel = $this->getCategoryTreeFields();
		$paragrid->check = 'function(evt, ui) {
			var idCategory = ui.rows[0].rowData.id_category;
			$("#'.$identifier.'").val(idCategory);
			filerFridbyCategory(idCategory);
        }';
        
		$paragrid->filterModel = [
            'on'     => true,
            'mode'   => '\'AND\'',
            'header' => true,
        ];
		$paragrid->sortModel = [
			'ignoreCase' => true
		];
        $paragrid->treeModel = [
            'dataIndx'     => '\'name\'',
            'id'           => '\'id_category\'',
            'checkbox'     => 1,
			'filterShowChildren' => 1,
            'icons'        => 0,
			'maxCheck' => 1
        ];
		
		
        $paragrid->gridFunction = [
			'neutralFunction()'                  => ''
		];

        $option = $paragrid->generateParaGridOption();
		$script = $paragrid->generateParagridScript();
		return '<script type="text/javascript">' . PHP_EOL . $script . PHP_EOL . '</script>';
    }
	
	public function getCategoryTreeFields() {

        return Tools::jsonEncode([

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
                'title'      => $this->l('Name'),
                'width'      => 300,
                'dataIndx'   => 'name',
                'align'      => 'left',
                'valign'     => 'center',
                'dataType'   => 'string',
            ],
           
        ]);

    }
}
