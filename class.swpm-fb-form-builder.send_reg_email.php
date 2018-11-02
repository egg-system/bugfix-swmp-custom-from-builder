    protected function send_reg_email() {
		global $wpdb;
		if (empty($this->data)) {
			return false;
		}
		$member_info = $this->data;
		$settings = SwpmSettings::get_instance();
		$subject = empty($this->formmeta->notification_setting) ?
				$settings->get_value('reg-complete-mail-subject') : stripslashes($this->formmeta->notification_subject);

		$body = empty($this->formmeta->notification_setting) ?
				$settings->get_value('reg-complete-mail-body') : stripslashes(html_entity_decode(wp_kses_stripslashes(($this->formmeta->notification_message))));
		$from_address = empty($this->formmeta->notification_setting) ?
				$settings->get_value('email-from') : stripslashes($this->formmeta->notification_email_name);

		$login_link = $settings->get_value('login-page-url');
		$headers = 'From: ' . $from_address . "\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		if (!empty($this->formmeta->notification_setting)) {
			$headers .= "Content-Type: text/html;\r\n";
		}
		$query = "SELECT alias FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE id = " . $this->get_level_info('id');
		$member_info['membership_level_name'] = $wpdb->get_var($query);
		$member_info['password'] = $member_info['plain_password'];
		$member_info['login_link'] = $login_link;
		
		$values = array_values($member_info);
		$keys = array_map('swpm_enclose_var', array_keys($member_info));
		$body = str_replace($keys, $values, $body);
		
		if(method_exists("SwpmMiscUtils", "replace_dynamic_tags")){
			$member_id = $member_info['member_id'];
			$body = SwpmMiscUtils::replace_dynamic_tags($body, $member_id);//Do the standard merge var replacement.
		}
		
		//Add the raw custom fields data to the email (if the merge tag is present).
		$custom_fields_arr = $this->custom;
		foreach ($custom_fields_arr as $field_id => $field_value) {
			$body = str_replace("{{$field_id}}", $field_value, $body);
		}
		
		//Send the member notification email.
		$email = sanitize_email($this->formmeta->type == self::REGISTRATION ? $this->data['email'] : $this->member_info->email);
		
		$subject = apply_filters('swpm_email_registration_complete_subject',$subject);
		$body = apply_filters('swpm_email_registration_complete_body',$body);//You can override the email to empty to disable this email.
		wp_mail(trim($email), $subject, nl2br($body), $headers);
		SwpmLog::log_simple_debug('Form builder addon - registration complete email sent to: '.$email.'. From Email Address value used: '.$from_address, true);
		

		if ($settings->get_value('enable-admin-notification-after-reg')) {
			$to_email_address = $settings->get_value('admin-notification-email');
			$admin_notification = empty($to_email_address) ? $from_address : $to_email_address;
			$notify_emails_array = explode(",", $admin_notification);
			
			$headers = 'From: ' . $from_address . "\r\n";
			$subject = "Notification of New Member Registration";
			
			$admin_notify_body = $settings->get_value('reg-complete-mail-body-admin');
			if(empty($admin_notify_body)){
				$admin_notify_body = "A new member has completed the registration.\n\n" .
				"Username: {user_name}\n" .
				"Email: {email}\n\n" .
				"Please login to the admin dashboard to view details of this user.\n\n" .
				"You can customize this email message from the Email Settings menu of the plugin.\n\n" .
				"Thank You";                        
			}
			$admin_notify_body = SwpmMiscUtils::replace_dynamic_tags($admin_notify_body, $member_id);//Do the standard merge var replacement.
			foreach ($custom_fields_arr as $field_id => $field_value) {
				$admin_notify_body = str_replace("{{$field_id}}", $field_value, $body);
			}

			foreach ($notify_emails_array as $to_email){
				$to_email = trim($to_email);
				wp_mail($to_email, $subject, nl2br($admin_notify_body), $headers);
				if(method_exists("SwpmLog", "log_simple_debug")){
					SwpmLog::log_simple_debug('Form builder addon - admin notification email sent to: '.$to_email, true);
				}
			}
		}
		return true;
    }