<?php

class NotificationsControllerCore extends FrontController {

	public $auth = true;
	public $php_self = 'notifications';
	public $ssl = true;

	public function setMedia() {

		parent::setMedia();

	}

	public function postProcess() {

		if (Tools::getValue('process') == 'reset_notification') {
			$this->processResetNotification();
		}

		if (Tools::getValue('process') == 'update_notification') {
			$this->processupdateNotification();
		}

		if (Tools::getValue('process') == 'get_post') {
			$this->processgetPostContent();
		}

	}

	protected function processgetPostContent() {

		$id_post = Tools::getValue('id_post');
		$id_notification = Tools::getValue('id_notification');
		$post = new MemberNotification($id_notification);
		$post->content_read = 1;
		$post->update();
		$this->setTemplate(_EPH_THEME_DIR_ . 'post_ajax.tpl');
		$this->context->smarty->assign([
			'postContent' => Memberpost::getContentPost($id_post, $this->context->customer->id),
		]);
		$return = [
			'fletch' => $this->context->smarty->fetch($this->template),
		];
		die(Tools::jsonEncode($return));
	}

	protected function processResetNotification() {

		$id_member = Tools::getValue('idNotification');

		$notifs = MemberNotification::getUnsignNotification($id_member);

		foreach ($notifs as $notif) {
			$notification = new MemberNotification($notif['id_member_notification']);
			$notification->content_status = 1;
			$notification->update();
		}

		die(true);
	}

	protected function processupdateNotification() {

		$id_customer = Tools::getValue('id_customer');

		$nbNotif = MemberNotification::getNotification($id_customer, true);

		$notifications = MemberNotification::getNotification($id_customer);
		$string = '';

		foreach ($notifications as $notif) {
			$id_member_notification = $notif['id_member_notification'];
			$link = Link::getProfilImageLink('user_icon', $notif['id_friend']);
			$notification_content = $notif['content'];

			if ($notif['content_read'] == 0) {
				$classe = 'notifications_unread';
			} else {
				$classe = 'notifications_read';
			}

			$string .= '<li class="' . $classe . '" data-value="' . $id_member_notification . '" data-post="' . $notif['id_member_post'] . '">
							<img src="' . $link . '" alt="" class="thumb_icon item_photo_user ">
    							<span class="notification_item_general notification_type_friend_request">
      								' . $notification_content . '
								</span>
  						</li>';
		}

		$return = [
			'nbNotif'       => $nbNotif,
			'notifications' => $string,
		];
		die(Tools::jsonEncode($return));

	}

	public function initContent() {

		parent::initContent();
		$this->ajax = true;
	}

}
