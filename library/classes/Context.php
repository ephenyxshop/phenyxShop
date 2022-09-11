<?php

/**
 * Class ContextCore
 *
 * @since 2.1.0.0
 */
class ContextCore {

	// @codingStandardsIgnoreStart
	/** @var int */
	const DEVICE_COMPUTER = 1;
	/** @var int */
	const DEVICE_TABLET = 2;
	/** @var int */
	const DEVICE_MOBILE = 4;
	/** @var int */
	const MODE_STD = 1;
	/** @var int */
	const MODE_STD_CONTRIB = 2;
	/** @var int */
	const MODE_HOST_CONTRIB = 4;
	/** @var int */
	const MODE_HOST = 8;
	/* @var Context */
	protected static $instance;
	/** @var Company */
	public $company;
	/** @var Cart */
	public $cart;
	/** @var Customer */
	public $customer;
	/** @var Cookie */
	public $cookie;
	/** @var Link */
	public $link;
	/** @var Country */
	public $country;
	/** @var Employee */
	public $employee;
	/** @var AdminController|FrontController */
	public $controller;
	/** @var string */
	public $override_controller_name_for_translations;
	/** @var Language */
	public $language;
	/** @var Currency */
	public $currency;
	/** @var AdminTab */
	public $tab;
	/** @var Shop */
	public $shop;
	/** @var Theme */
	public $theme;
	/** @var Smarty */
	public $smarty;
	/** @var Mobile_Detect */
	public $mobile_detect;
	/** @var int */
	public $mode;
	/**
	 * Mobile device of the customer
	 *
	 * @var bool|null
	 */
	protected $mobile_device = null;
	/** @var bool|null */
	protected $is_mobile = null;
	/** @var bool|null */
	protected $is_tablet = null;
	// @codingStandardsIgnoreEnd

	/**
	 * @param Context $testInstance Unit testing purpose only
	 *
	 * @since 2.1.0.0
	 */
	public static function setInstanceForTesting($testInstance) {

		static::$instance = $testInstance;
	}

	/**
	 * Unit testing purpose only
	 *
	 * @since 2.1.0.0
	 */
	public static function deleteTestingInstance() {

		static::$instance = null;
	}

	/**
	 * Sets mobile_device context variable
	 *
	 * @return bool
	 *
	 * @since 2.1.0.0
	 * @throws PhenyxShopException
	 */
	public function getMobileDevice() {

		if ($this->mobile_device === null) {
			$this->mobile_device = false;

			if ($this->checkMobileContext()) {

				if (isset(Context::getContext()->cookie->no_mobile) && Context::getContext()->cookie->no_mobile == false && (int) Configuration::get('PS_ALLOW_MOBILE_DEVICE') != 0) {
					$this->mobile_device = true;
				} else {

					switch ((int) Configuration::get('PS_ALLOW_MOBILE_DEVICE')) {
					case 1: // Only for mobile device

						if ($this->isMobile() && !$this->isTablet()) {
							$this->mobile_device = true;
						}

						break;
					case 2: // Only for touchpads

						if ($this->isTablet() && !$this->isMobile()) {
							$this->mobile_device = true;
						}

						break;
					case 3: // For touchpad or mobile devices

						if ($this->isMobile() || $this->isTablet()) {
							$this->mobile_device = true;
						}

						break;
					}

				}

			}

		}

		return $this->mobile_device;
	}

	/**
	 * Get a singleton instance of Context object
	 *
	 * @return Context
	 *
	 * @since 2.1.0.0
	 */
	public static function getContext() {

		if (!isset(static::$instance)) {
			static::$instance = new Context();
		}

		return static::$instance;
	}

	/**
	 * Checks if visitor's device is a mobile device
	 *
	 * @return bool
	 *
	 * @since 2.1.0.0
	 */
	public function isMobile() {

		if ($this->is_mobile === null) {
			$mobileDetect = $this->getMobileDetect();
			$this->is_mobile = $mobileDetect->isMobile();
		}

		return $this->is_mobile;
	}

	/**
	 * Sets Mobile_Detect tool object
	 *
	 * @return Mobile_Detect
	 *
	 * @since 2.1.0.0
	 */
	public function getMobileDetect() {

		if ($this->mobile_detect === null) {
			$this->mobile_detect = new Mobile_Detect();
		}

		return $this->mobile_detect;
	}

	/**
	 * Checks if visitor's device is a tablet device
	 *
	 * @return bool
	 *
	 * @since 2.1.0.0
	 */
	public function isTablet() {

		if ($this->is_tablet === null) {
			$mobileDetect = $this->getMobileDetect();
			$this->is_tablet = $mobileDetect->isTablet();
		}

		return $this->is_tablet;
	}

	/**
	 * Returns mobile device type
	 *
	 * @return int
	 *
	 * @since 2.1.0.0
	 */
	public function getDevice() {

		static $device = null;

		if ($device === null) {

			if ($this->isTablet()) {
				$device = Context::DEVICE_TABLET;
			} else

			if ($this->isMobile()) {
				$device = Context::DEVICE_MOBILE;
			} else {
				$device = Context::DEVICE_COMPUTER;
			}

		}

		return $device;
	}

	/**
	 * Clone current context object
	 *
	 * @return Context
	 *
	 * @since 2.1.0.0
	 */
	public function cloneContext() {

		return clone ($this);
	}

	/**
	 * Checks if mobile context is possible
	 *
	 * @return bool
	 * @throws PhenyxShopException
	 *
	 * @since 2.1.0.0
	 */
	protected function checkMobileContext() {

		// Check mobile context

		if (Tools::isSubmit('no_mobile_theme')) {
			Context::getContext()->cookie->no_mobile = true;

			if (Context::getContext()->cookie->id_guest) {
				$guest = new Guest(Context::getContext()->cookie->id_guest);
				$guest->mobile_theme = false;
				$guest->update();
			}

		} else

		if (Tools::isSubmit('mobile_theme_ok')) {
			Context::getContext()->cookie->no_mobile = false;

			if (Context::getContext()->cookie->id_guest) {
				$guest = new Guest(Context::getContext()->cookie->id_guest);
				$guest->mobile_theme = true;
				$guest->update();
			}

		}

		return isset($_SERVER['HTTP_USER_AGENT'])
		&& isset(Context::getContext()->cookie)
		&& (bool) Configuration::get('PS_ALLOW_MOBILE_DEVICE')
		&& @filemtime(_PS_THEME_MOBILE_DIR_)
		&& !Context::getContext()->cookie->no_mobile;
	}

}
