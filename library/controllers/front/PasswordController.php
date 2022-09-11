<?php

/**
 * Class PasswordControllerCore
 *
 * @since 1.8.1.0
 */
class PasswordControllerCore extends FrontController {

    // @codingStandardsIgnoreStart
    /** @var string $php_self */
    public $php_self = 'password';
    /** @var bool $auth */
    public $auth = false;
    // @codingStandardsIgnoreEnd

    /**
     * Start forms process
     *
     * @see FrontController::postProcess()
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function postProcess() {

        if (Tools::isSubmit('email')) {

            if (!($email = trim(Tools::getValue('email'))) || !Validate::isEmail($email)) {
                $this->errors[] = Tools::displayError('Invalid email address.');
            } else {
                $customer = new Customer();
                $customer->getByemail($email);

                if (!Validate::isLoadedObject($customer)) {
                    $this->errors[] = Tools::displayError('There is no account registered for this email address.');
                } else if (!$customer->active) {
                    $this->errors[] = Tools::displayError('You cannot regenerate the password for this account.');
                } else if ((strtotime($customer->last_passwd_gen . '+' . ($minTime = (int) Configuration::get('PS_PASSWD_TIME_FRONT')) . ' minutes') - time()) > 0) {
                    $this->errors[] = sprintf(Tools::displayError('You can regenerate your password only every %d minute(s)'), (int) $minTime);
                } else {
                    $mailParams = [
                        '{email}'     => $customer->email,
                        '{lastname}'  => $customer->lastname,
                        '{firstname}' => $customer->firstname,
                        '{url}'       => $this->context->link->getPageLink('password', true, null, 'token=' . $customer->secure_key . '&id_customer=' . (int) $customer->id),
                    ];

                    if (Mail::Send($this->context->language->id, 'password_query', Mail::l('Password query confirmation'), $mailParams, $customer->email, $customer->firstname . ' ' . $customer->lastname)) {
                        $this->context->smarty->assign(['confirmation' => 2, 'customer_email' => $customer->email]);
                    } else {
                        $this->errors[] = Tools::displayError('An error occurred while sending the email.');
                    }

                }

            }

        } else if (($token = Tools::getValue('token')) && ($idCustomer = (int) Tools::getValue('id_customer'))) {
            $email = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('c.`email`')
                    ->from('customer', 'c')
                    ->where('c.`secure_key` = \'' . pSQL($token) . '\'')
                    ->where('c.`id_customer` = ' . (int) $idCustomer)
            );

            if ($email) {
                $customer = new Customer();
                $customer->getByemail($email);

                if (!Validate::isLoadedObject($customer)) {
                    $this->errors[] = Tools::displayError('Customer account not found');
                } else if (!$customer->active) {
                    $this->errors[] = Tools::displayError('You cannot regenerate the password for this account.');
                } else if ((strtotime($customer->last_passwd_gen . '+' . (int) Configuration::get('PS_PASSWD_TIME_FRONT') . ' minutes') - time()) > 0) {
                    Tools::redirect('index.php?controller=authentication&error_regen_pwd');
                } else {
                    $customer->passwd = Tools::hash($password = Tools::passwdGen(MIN_PASSWD_LENGTH, 'RANDOM'));
                    $customer->last_passwd_gen = date('Y-m-d H:i:s', time());

                    if ($customer->update()) {
                        Hook::exec('actionPasswordRenew', ['customer' => $customer, 'password' => $password]);
                        $mailParams = [
                            '{email}'     => $customer->email,
                            '{lastname}'  => $customer->lastname,
                            '{firstname}' => $customer->firstname,
                            '{passwd}'    => $password,
                        ];

                        if (Mail::Send($this->context->language->id, 'password', Mail::l('Your new password'), $mailParams, $customer->email, $customer->firstname . ' ' . $customer->lastname)) {
                            $this->context->smarty->assign(['confirmation' => 1, 'customer_email' => $customer->email]);
                        } else {
                            $this->errors[] = Tools::displayError('An error occurred while sending the email.');
                        }

                    } else {
                        $this->errors[] = Tools::displayError('An error occurred with your account, which prevents us from sending you a new password. Please report this issue using the contact form.');
                    }

                }

            } else {
                $this->errors[] = Tools::displayError('We cannot regenerate your password with the data you\'ve submitted.');
            }

        } else if (Tools::getValue('token') || Tools::getValue('id_customer')) {
            $this->errors[] = Tools::displayError('We cannot regenerate your password with the data you\'ve submitted.');
        }

    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @return void
     *
     * @since 1.8.1.0
     */
    public function initContent() {

        parent::initContent();
        $this->setTemplate(_PS_THEME_DIR_ . 'password.tpl');
    }

}
