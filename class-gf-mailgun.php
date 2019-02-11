<?php

defined( 'ABSPATH' ) or die();

GFForms::include_addon_framework();

/**
 * Gravity Forms Mailgun Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2017, Rocketgenius
 */
class GF_Mailgun extends GFAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Mailgun Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_version Contains the version, defined from mailgun.php
	 */
	protected $_version = GF_MAILGUN_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '2.2.3.8';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformsmailgun';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformsmailgun/mailgun.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'https://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms Mailgun Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Mailgun';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_mailgun';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_mailgun';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_mailgun_uninstall';

	/**
	 * Defines the capabilities needed for the Mailgun Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_mailgun', 'gravityforms_mailgun_uninstall' );

	/**
	 * Contains an instance of the Mailgun API libray, if available.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    GF_Mailgun_API $api If available, contains an instance of the Mailgun API library.
	 */
	protected $api = null;

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return GF_Mailgun
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Register needed hooks.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function init() {

		parent::init();

		// Add Mailgun as a notification service.
		add_filter( 'gform_notification_services', array( $this, 'add_notification_service' ) );

		// Add Mailgun notification fields.
		add_filter( 'gform_notification_ui_settings', array( $this, 'add_notification_fields' ), 10, 4 );

		// Save Mailgun notification fields.
		add_filter( 'gform_pre_notification_save', array( $this, 'save_notification_fields' ), 10, 2 );

		// Ensure notification from email is a valid domain in Mailgun.
		add_filter( 'gform_notification_validation', array( $this, 'validate_notification' ), 10, 3 );

		// Handle Mailgun notifications.
		add_filter( 'gform_pre_send_email', array( $this, 'maybe_send_email' ), 19, 4 );

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Define plugin settings fields.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'description' => sprintf(
					'<p>%s</p>',
					sprintf(
						esc_html__( 'Mailgun makes it easy to reliably send and track email notifications. If you don\'t have a Mailgun account, you can %1$ssign up for one here.%2$s Once you have signed up, you can %3$sfind your Private API Key here%4$s.', 'gravityformsmailgun' ),
						'<a href="https://www.mailgun.com/" target="_blank">', '</a>',
						'<a href="https://app.mailgun.com/app/account/security" target="_blank">', '</a>'
					)
				),
				'fields'      => array(
					array(
						'name'          => 'region',
						'label'         => esc_html__( 'Region', 'gravityformsmailgun' ),
						'type'          => 'radio',
						'horizontal'    => true,
						'default_value' => 'us',
						'tooltip'       => esc_html__( 'Choose which region (United States or Europe) you want your message data to be processed by.', 'gravityformsmailgun' ),
						'choices'       => array(
							array(
								'label' => esc_html__( 'United States', 'gravityformsmailgun' ),
								'value' => 'us',
							),
							array(
								'label' => esc_html__( 'Europe', 'gravityformsmailgun' ),
								'value' => 'eu',
							),
						),
					),
					array(
						'name'              => 'apiKey',
						'label'             => esc_html__( 'Private API Key', 'gravityformsmailgun' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' ),
					),
					array(
						'type'     => 'save',
						'messages' => array(
							'success' => esc_html__( 'Mailgun settings have been updated.', 'gravityformsmailgun' ),
						),
					),
				),
			),
		);

	}





	// # NOTIFICATIONS -------------------------------------------------------------------------------------------------

	/**
	 * Add Mailgun as a notification service.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $services Existing notification services.
	 *
	 * @uses   GFAddOn::get_base_url()
	 * @uses   GF_Mailgun::initialize_api()
	 *
	 * @return array
	 */
	public function add_notification_service( $services ) {

		// If running GF prior to 2.4, check that API is initialized.
		if ( version_compare( GFFormsModel::get_database_version(), '2.4-beta-2', '<' ) && ! $this->initialize_api() ) {
			return $services;
		}

		// Add the service.
		$services['mailgun'] = array(
			'label'            => esc_html__( 'Mailgun', 'gravityformsmailgun' ),
			'image'            => $this->get_base_url() . '/images/icon.svg',
			'disabled'         => ! $this->initialize_api(),
			'disabled_message' => sprintf(
				esc_html__( 'You must %sauthenticate with Mailgun%s before sending emails using their service.', 'gravityformsmailgun' ),
				"<a href='" . esc_url( admin_url( 'admin.php?page=gf_settings&subview=' . $this->_slug ) ) . "'>",
				'</a>'
			),
		);

		return $services;

	}

	/**
	 * Add Mailgun notification fields.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $ui_settings  An array of settings for the notification UI.
	 * @param  array $notification The current notification object being edited.
	 * @param  array $form         The current form object to which the notification being edited belongs.
	 * @param  bool  $is_valid     If the notification is valid.
	 *
	 * @return array
	 */
	public function add_notification_fields( $ui_settings, $notification, $form, $is_valid ) {

		// If Mailgun is not the selected notification service, return default UI settings.
		if ( ( ! rgpost( 'gform_notification_service' ) && 'mailgun' !== rgar( $notification, 'service' ) ) || ( rgpost( 'gform_notification_service' ) && 'mailgun' !== rgpost( 'gform_notification_service' ) ) ) {
			return $ui_settings;
		}

		// Get from address variables.
		$from_email          = rgempty( 'from', $notification ) ? '{admin_email}' : esc_attr( rgget( 'from', $notification ) );
		$from_email_validate = GFCommon::replace_variables( rgar( $notification, 'from' ), $form, array(), false, false );
		$from_email_validate = explode( '@', $from_email_validate );
		$from_email_validate = end( $from_email_validate );
		$from_email_valid    = rgpost( 'save' ) ? $is_valid && $this->is_valid_domain( $from_email_validate ) : null;
		$from_address_class  = ! $is_valid ? 'gfield_error' : '';

		// Build from address row.
		$from_address  = sprintf( '<tr valign="top" class="%s">', esc_attr( $from_address_class ) );
		$from_address .= '<th scope="row">';
		$from_address .= sprintf( '<label for="gform_notification_from">%s %s</label>', esc_html__( 'From Email', 'gravityforms' ), gform_tooltip( 'notification_form_email' ) );
		$from_address .= '</th>';
		$from_address .= '<td>';
		$from_address .= sprintf( '<input type="text" class="fieldwidth-2 merge-tag-support mt-position-right mt-hide_all_fields" name="gform_notification_from" id="gform_notification_from" value="%s" />', esc_attr( $from_email ) );

		$settings = $this->get_plugin_settings();
		$selected_region = rgar( $settings, 'region' ) ? $settings['region'] : 'us';
		$selected_region = $selected_region == 'us' ? __( 'United States', 'gravityformsmailgun' ) : __( 'Europe', 'gravityformsmailgun' );

		// Validation message.
		if ( $from_email_valid === false ) {
			$from_address .= '<div class="validation_message"><p>';
			$from_address .= sprintf(
				esc_html__( 'From Email domain must be an %1$sactive domain%3$s for the %4$s region in Mailgun. You can learn more about verifying your domain in the %2$sMailgun documentation%3$s.', 'gravityformsmailgun' ),
				'<a href="https://mailgun.com/app/domains">',
				'<a href="https://documentation.mailgun.com/user_manual.html#verifying-your-domain">',
				'</a>',
				$selected_region
			);
			$from_address .= '</p></div>';
		}

		$from_address .= '</td>';
		$from_address .= '</tr>';

		// Add from address row.
		$ui_settings['notification_from'] = $from_address;

		// Build tracking row.
		$tracking  = '<tr valign="top">';

		$tracking .= '<th scope="row">';
		$tracking .= '<label>' . esc_html__( 'Email Tracking', 'gravityformsmailgun' ) . '</label>';
		$tracking .= '</th>';
		$tracking .= '<td>';
		$tracking .= '<input type="checkbox" name="gform_notification_mailgun_track_clicks" id="gform_notification_mailgun_track_clicks" value="1" ' . checked( '1', rgar( $notification, 'mailgunTrackClicks' ), false ) . ' />';
		$tracking .= '<label for="gform_notification_mailgun_track_clicks" class="inline">&nbsp;&nbsp;' . esc_html__( 'Enable click tracking for this notification', 'gravityformsmailgun' ) . '</label><br />';
		$tracking .= '<input type="checkbox" name="gform_notification_mailgun_track_opens" id="gform_notification_mailgun_track_opens" value="1" ' . checked( '1', rgar( $notification, 'mailgunTrackOpens' ), false ) . ' />';
		$tracking .= '<label for="gform_notification_mailgun_track_opens" class="inline">&nbsp;&nbsp;' . esc_html__( 'Enable open tracking for this notification', 'gravityformsmailgun' ) . '</label>';
		$tracking .= '</td>';
		$tracking .= '</tr>';

		// Get UI settings array keys.
		$ui_settings_keys = array_keys( $ui_settings );

		// Loop through UI settings.
		foreach ( $ui_settings as $key => $ui_setting ) {

			// If this is not the conditional logic setting, skip.
			if ( 'notification_conditional_logic' !== $key ) {
				continue;
			}

			// Get position.
			$position = array_search( $key, $ui_settings_keys );

			// Add tracking row.
			array_splice( $ui_settings, $position, 0, array( 'mailgun_tracking' => $tracking ) );

		}

		return $ui_settings;

	}

	/**
	 * Save Mailgun notification fields.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $notification The notification object about to be saved.
	 * @param array $form         The current form object to which the notification being saved belongs.
	 *
	 * @return array
	 */
	public function save_notification_fields( $notification, $form ) {

		if ( 'mailgun' === rgpost( 'gform_notification_service' ) ) {

			$notification['mailgunTrackClicks'] = sanitize_text_field( rgpost( 'gform_notification_mailgun_track_clicks' ) );
			$notification['mailgunTrackOpens']  = sanitize_text_field( rgpost( 'gform_notification_mailgun_track_opens' ) );

		}

		return $notification;

	}

	/**
	 * Validate sender domain when saving notification.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  bool  $is_valid     Validation state.
	 * @param  array $notification The current Notification object.
	 * @param  array $form         The current Form object.
	 *
	 * @uses   GFCommon::is_invalid_or_empty_email()
	 * @uses   GFCommon::replace_variables()
	 * @uses   GF_Mailgun::initialize_api()
	 * @uses   GF_Mailgun::is_valid_domain()
	 *
	 * @return bool
	 */
	public function validate_notification( $is_valid, $notification, $form ) {

		// If this is not a Mailgun notification or the API is not initialized, return the validation state.
		if ( 'mailgun' !== rgar( $notification, 'service' ) || ! rgpost( 'save' ) || ! $this->initialize_api() ) {
			return $is_valid;
		}

		// Get the from email address.
		$from_email = GFCommon::replace_variables( rgar( $notification, 'from' ), $form, array(), false, false );

		// If from email address is invalid or empty, set validation to false.
		if ( GFCommon::is_invalid_or_empty_email( $from_email ) ) {
			$is_valid = false;
			return $is_valid;
		}

		// Get from email address domain.
		$from_domain = explode( '@', $from_email );
		$from_domain = end( $from_domain );

		// Check domain validity.
		if ( ! $this->is_valid_domain( $from_domain ) ) {
			$is_valid = false;
		}

		return $is_valid;

	}

	/**
	 * Send email through Mailgun if notification send via is set to Mailgun.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array  $email          The email details.
	 * @param string $message_format The message format, html or text.
	 * @param array  $notification   The Notification object.
	 * @param array  $entry          The current Entry object.
	 *
	 * @return array  $email
	 */
	public function maybe_send_email( $email, $message_format, $notification, $entry ) {

		// If email has been aborted, return the email.
		if ( $email['abort_email'] ) {
			$this->log_debug( __METHOD__ . '(): Not sending email via Mailgun because the notification has already been aborted by another Add-On.' );
			return $email;
		}

		// If this is not a Mailgun notification or Mailgun API is not initialized, return the email.
		if ( rgar( $notification, 'service' ) !== 'mailgun' || ! $this->initialize_api() ) {
			return $email;
		}

		// Get form object.
		$form = GFAPI::get_form( $entry['form_id'] );

		// Get from email domain.
		preg_match( '/<(.*)>/', $email['headers']['From'], $from_email );
		$from_domain = explode( '@', $from_email[1] );
		$from_domain = end( $from_domain );

		// Prepare email for Mailgun.
		$mailgun_email = array(
			'to'                => $email['to'],
			'from'              => rgar( $notification, 'fromName' ) ? GFCommon::replace_variables( rgar( $notification, 'fromName' ), $form, $entry, false, false, false, 'text' ) . '<' . $from_email[1] . '>' : $from_email[1],
			'subject'           => rgar( $email, 'subject' ),
			'o:tracking-clicks' => rgar( $notification, 'mailgunTrackClicks' ) ? 'yes' : 'no',
			'o:tracking-opens'  => rgar( $notification, 'mailgunTrackOpens' ) ? 'yes' : 'no',
			'bcc'               => GFCommon::replace_variables( rgar( $notification, 'bcc' ), $form, $entry, false, false, false, 'text' ),
			'h:Reply-To'        => GFCommon::replace_variables( rgar( $notification, 'replyTo' ), $form, $entry, false, false, false, 'text' ),
		);

		// Add body based on message format.
		if ( 'html' === $message_format ) {
			$mailgun_email['html'] = $email['message'];
		} else {
			$mailgun_email['text'] = $email['message'];
		}

		// Add attachments to Mailgun email.
		if ( ! empty( $email['attachments'] ) ) {

			// Loop through attachments, add to email.
			foreach ( $email['attachments'] as $attachment ) {
				$mailgun_email['attachment'][] = array(
					'name' => basename( $attachment ),
					'path' => $attachment,
				);
			}

		}

		// Remove empty email parameters.
		$mailgun_email = array_filter( $mailgun_email );

		/**
		 * Modify the email being sent by Mailgun.
		 *
		 * @since 1.0
		 * @since 1.1 Added entry parameter.
		 *
		 * @param array $email          The Mailgun email arguments.
		 * @param array $email          The original email details.
		 * @param array $message_format The message format, html or text.
		 * @param array $notification   The Notification object.
		 * @param array $entry          The current Entry object.
		 */
		$mailgun_email = apply_filters( 'gform_mailgun_email', $mailgun_email, $email, $message_format, $notification, $entry );

		// Log the email to be sent.
		$this->log_debug( __METHOD__ . '(): Sending email via Mailgun; ' . print_r( $mailgun_email, true ) );

		try {

			// Send email.
			$sent_email = $this->api->send_email( $from_domain, $mailgun_email );

			// Log that email was sent.
			$this->log_debug( __METHOD__ . '(): Email successfully sent by Mailgun; ' . print_r( $sent_email, true ) );

			// Prevent Gravity Forms from sending email.
			$email['abort_email'] = true;

		} catch ( Exception $e ) {

			// Log that email failed to send.
			$this->log_error( __METHOD__ . '(): Email unable to be sent by Mailgun; ' . $e->getMessage() );

		}

		return $email;

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Initializes Mailgun API if credentials are valid.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses   GFAddOn::get_plugin_settings()
	 * @uses   GFAddOn::log_debug()
	 * @uses   GFAddOn::log_error()
	 * @uses   GF_Mailgun_API::get_domains()
	 *
	 * @return bool|null
	 */
	public function initialize_api() {

		// If API object is already setup, return true.
		if ( ! is_null( $this->api ) ) {
			return true;
		}

		// Get the plugin settings.
        $settings = $this->get_plugin_settings();

		// If API key is empty, do not initialize API.
		if ( ! rgar( $settings, 'apiKey' ) ) {
			return null;
		}

		// Get the region.
		$region = rgar( $settings, 'region' ) ? $settings['region'] : 'us';

		// Load the Mailgun API library.
		if ( ! class_exists( 'GF_Mailgun_API' ) ) {
			require_once( 'includes/class-gf-mailgun-api.php' );
		}

		// Log that were testing the API credentials.
		$this->log_debug( __METHOD__ . '(): Validating API credentials.' );

		try {

			// Setup a new Mailgun API object.
			$mailgun = new GF_Mailgun_API( $settings['apiKey'], $region );

			// Attempt to get account domains.
			$mailgun->get_domains();

			// Assign the Mailgun API object to this instance.
			$this->api = $mailgun;

			// Log that test passed.
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );

			return true;

		} catch ( Exception $e ) {

			// Log that test failed.
			$this->log_error( __METHOD__ . '(): API credentials are invalid; ' . $e->getMessage() );

			return false;

		}

	}

	/**
	 * Check if domain name is active in Mailgun.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  string $domain Domain name being validated.
	 *
	 * @uses   GFAddOn::log_error()
	 * @uses   GF_Mailgun::initialize_api()
	 * @uses   GF_Mailgun_API::get_domain()
	 *
	 * @return bool
	 */
	public function is_valid_domain( $domain ) {

		// Set initial validation state.
		$is_valid = false;

		// If the API is not initialized or the domain is blank, return/
		if ( ! $this->initialize_api() || rgblank( $domain ) ) {
			return $is_valid;
		}

		try {

			// Get the domain details from Mailgun.
			$domain_details = $this->api->get_domain( $domain );

			// If the domain is active, set validation state to true.
			if ( 'active' === $domain_details['domain']['state'] ) {
				$is_valid = true;
			}

		} catch ( Exception $e ) {

			// Log that domain could not be found.
			$this->log_error( __METHOD__ . '(): Could not get domain information for "' . $domain . '"; ' . $e->getMessage() . ' (' . $e->getCode() . ')' );

		}

		return $is_valid;

	}

}
