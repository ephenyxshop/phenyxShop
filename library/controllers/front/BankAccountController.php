<?php

/**
 * Class BankAccountControllerCore
 *
 * @since 1.8.1.0
 */
class BankAccountControllerCore extends FrontController {

	// @codingStandardsIgnoreStart
	/** @var bool $auth */
	public $auth = true;
	/** @var string $php_self */
	public $php_self = 'bank-account';
	/** @var string $authRedirection */
	public $authRedirection = 'bank-account';
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

		if (!Validate::isLoadedObject($this->context->customer)) {
			die(Tools::displayError('The customer could not be found.'));
		}

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

		$adresse = Address::getFirstCustomerAddressId((int) ($this->context->cookie->id_customer));
		$address = new Address($adresse);
		$this->context->smarty->assign('address', $address);
		$this->context->smarty->assign('id_customer', $this->context->cookie->id_customer);

		if (Configuration::get('EPH_BANK_IBAN') == 1) {
			$countries = Country::getCountries($this->context->language->id, false);
		} else {
			$countries = BankAccount::getCountries($this->context->language->id, true);
		}

		$this->context->smarty->assign('Shop_Name', Configuration::get('EPH_SHOP_NAME'));
		$this->context->smarty->assign('ICSNumber', Configuration::get('EPH_ICS_NUMBER'));
		$idCompany = Configuration::get('EPH_COMPANY_ID');
		$company = new Company($idCompany);
		$this->context->smarty->assign('company', $company);
		$this->context->smarty->assign('mandat', '+' . CustomerPieces::generateReference());

		$this->context->smarty->assign(
			[
				'customer'  => $this->context->customer,
				'countries' => $countries,
				'errors'    => $this->errors,
			]
		);

		if ($this->context->customer->id_default_group > 3) {
			$this->setTemplate(_EPH_THEME_DIR_ . 'bank-btobe.tpl');
		} else {
			$this->setTemplate(_EPH_THEME_DIR_ . 'bank.tpl');
		}

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
		$this->addJS(_THEME_JS_DIR_ . 'bank.js');

	}

	public function ajaxProcessHasIban() {

		$id_country = Tools::getValue('id_country');
		$iban = BankAccount::hasIban($id_country);

		if ($iban) {
			die(Tools::jsonEncode($iban));
		} else {
			die(false);
		}

	}

	public function ajaxProcessAddNewBankAccount() {

		$file = fopen("testProcessAddNewBankAccount.txt", "w");
		$bank = new BankAccount();

		foreach ($_POST as $key => $value) {
			fwrite($file, $key . ' => ' . $value . PHP_EOL);

			if (property_exists($bank, $key) && $key != 'id_bank_account') {

				$bank->{$key}
				= $value;

			}

		}

		fwrite($file, print_r($bank, true) . PHP_EOL);
		$result = $bank->add();

		if ($result) {
			$mandat = new Mandat();
			$mandat_type = Tools::getValue('mandat_type');

			if ($mandat_type == 'Unique') {
				$step = 'OOFF';
			} else if ($mandat_type == 'Recurent') {
				$step = 'FRST';
			}

			$mandat->id_bank = $bank->id;
			$mandat->mandat_sepa = Tools::getValue('mandat');
			$mandat->mandat_type = $mandat_type;
			$mandat->step = $step;
			$mandat->IP_Registration = $_SERVER['REMOTE_ADDR'];
			$mandat->add(true);
			$pdfUploader = new HelperUploader('ribUrl');
			$pdfUploader->setAcceptTypes(['pdf', 'jpeg', 'gif', 'png', 'jpg'])->setMaxSize($this->max_image_size);
			$files = $pdfUploader->process();

			if (is_array($files) && count($files)) {

				foreach ($files as $file) {
					$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
					$destinationFile = _EPH_UPLOAD_DIR_ . 'RIB_' . $bank->id . '.' . $ext;
					copy($file['save_path'], $destinationFile);

				}

			}

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
			$customer = new Customer($bank->id_customer);

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

			$filePath = _EPH_EXPORT_DIR_;
			$fileName = $this->l('Mandat Sepa ').$bank->id.'.pdf';

			$mpdf->SetTitle($company->company_name . $this->l('Mandat Sepa ') . $bank->id);
			$mpdf->SetAuthor($company->company_name);

			$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
			$mpdf->WriteHTML($data->fetch());

			$mpdf->Output($filePath . $fileName, "F");

			$fileName = _EPH_EXPORT_DIR_ . $fileName;
			$fileAttachement[] = [
				'content' => chunk_split(base64_encode(file_get_contents($fileName))),
				'name'    => $this->l('Mandat Sepa ').$bank->id.'.pdf',
			];
			$tpl = $context->smarty->createTemplate(_EPH_MAIL_DIR_ . 'fr/mandat_sepa.tpl');
			$tpl->assign([
				'customer' => $customer,
				'bank'     => $bank,
				'mandat'   => $mandat,

			]);
			$postfields = [
				'sender'      => [
					'name'  => "Service Administratif " . Configuration::get('EPH_SHOP_NAME'),
					'email' => Configuration::get('EPH_SHOP_EMAIL'),
				],
				'to'          => [
					[
						'name'  => $customer->firstname . ' ' . $customer->lastname,
						'email' => $customer->email,
					],
				],

				'subject'     => 'Votre mandat Sepa',
				"htmlContent" => $tpl->fetch(),
				'attachment'  => $fileAttachement,
			];

			$result = Tools::sendEmail($postfields);
			$return = [
				'success' => true,
			];

		} else {
			$return = [
				'success' => true,
				'message' => 'un truc a merder',
			];
		}

		die(Tools::jsonEncode($return));
	}

}
