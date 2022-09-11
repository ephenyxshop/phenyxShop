<?php

/**
 * Class MandatSepaControllerCore
 *
 * @since 1.8.1.0
 */
class MandatSepaControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'mandat-sepa';
    /** @var string $authRedirection */
    public $authRedirection = 'mandat-sepa';
    /** @var bool $ssl */
    public $ssl = true;
    /** @var Customer */
    protected $customer;
    // @codingStandardsIgnoreEnd
	public $display_column_left = false;
    public $display_column_right = false;
    /**
     * Initialize controller
     *
     * @since 1.8.1.0
     */
    public function init() {

        parent::init();
        $this->customer = $this->context->customer;
    }

    /**
     * Start forms process
     *
     * @return Customer
     *
     * @since 1.8.1.0
     */
    public function postProcess() {

        parent::postProcess();
        
        $_POST = array_map('stripslashes', $this->customer->getFields());

        return $this->customer;
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

		
        $mandats = Sepa::getBanks($this->customer->id, $this->context->language->id);
        /* Generate years, months and days */
        $this->context->smarty->assign(
            [
                'customer'   => $this->customer,
				'mandats' => $mandats,
                'errors'    => $this->errors,
            ]
        );


        $this->setTemplate(_EPH_THEME_DIR_ . 'mandats.tpl');
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
        $this->addCSS(_THEME_CSS_DIR_ . 'bank.css');
        $this->addJS(_THEME_JS_DIR_ . 'banks.js');
        Media::addJsDef([
            'AjaxLinkBankAccount' => $this->context->link->getPageLink('bank_account', true),

        ]);
    }
	
	public function ajaxProcessPrintMandat() {

        $idBank = Tools::getValue('idBank');
		$idMandat = Tools::getValue('idMandat');
        $bank = new BankAccount($idBank);
		$mandat = new Mandat($idMandat);
		$customer = new Customer($bank->id_customer);
		
		$pathLogo = $this->getLogo();
		$width = 0;
		$height = 0;

		if (!empty($pathLogo)) {
			list($width, $height) = getimagesize($pathLogo);
		}

		$maximumHeight = 150;

		if ($height > $maximumHeight) {
			$ratio = $maximumHeight / $height;
			$height *= $ratio;
			$width *= $ratio;
		}

		$context = Context::getContext();

		if ($customer->id_default_group > 3) {
			$template = 'sepa_btob.tpl';
		} else {
			$template = 'mandat_sepa.tpl';
		}

		$adresse = Address::getFirstCustomerAddressId((int) ($customer->id));
		$address = new Address($adresse);
		$idCompany = Configuration::get('EPH_COMPANY_ID');
		$company = new Company($idCompany);
		$ics = Configuration::get('EPH_ICS_NUMBER');
		$mpdf = new \Mpdf\Mpdf([
			'margin_left'   => 10,
			'margin_right'  => 10,
			'margin_top'    => 90,
			'margin_bottom' => 30,
			'margin_header' => 10,
			'margin_footer' => 10,
		]);
		$data = $context->smarty->createTemplate(_EPH_PDF_DIR_.'header.tpl');
		$data->assign(
			[
				'company'     => $company,
				'logo_path'   => $pathLogo,
				'width_logo'  => $width,
				'height_logo' => $height,
				'customer'    => $customer,
				'address'     => $address,
			]
		);

		$mpdf->SetHTMLHeader($data->fetch());
		$data = $context->smarty->createTemplate(_EPH_PDF_DIR_.'footer.tpl');
		$data->assign(
			[
				'mandat' => $mandat,
			]
		);
		$mpdf->SetHTMLFooter($data->fetch(), 'O');

		$data = $context->smarty->createTemplate(_EPH_PDF_DIR_.'pdf.css.tpl');
		$data->assign(
			[
				'color' => '#ef9331',
			]
		);
		$stylesheet = $data->fetch();

		$data = $context->smarty->createTemplate(_EPH_PDF_DIR_.'' . $template);

		$data->assign(
			[
				'company'   => $company,
				'customer'  => $customer,
				'address'   => $address,
				'ICSNumber' => $ics,
				'bank'      => $bank,
				'mandat'    => $mandat,
				'Shop_Name' => $company->company_name,
			]
		);

		$filePath = _EPH_UPLOAD_DIR_;
		$fileName = $this->l('Mandat Sepa ').$bank->id.'.pdf';

		$mpdf->SetTitle($company->company_name . $this->l('Mandat Sepa ') . $bank->id);
		$mpdf->SetAuthor($company->company_name);

		$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
		$mpdf->WriteHTML($data->fetch());

		$mpdf->Output($filePath . $fileName, "F");
		
		$response = [
			'fileExport' => 'upload' . DIRECTORY_SEPARATOR . $fileName,
		];
		die(Tools::jsonEncode($response));

    }


}
