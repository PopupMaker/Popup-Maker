<?php
/**
 * Class for Admin Templates
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Templates
 *
 * @since 1.7.0
 */
class PUM_Admin_Templates {

	/**
	 *
	 */
	public static function init() {
		if ( did_action( 'admin_footer' ) || doing_action( 'admin_footer' ) ) {
			self::render();
		} else {
			add_action( 'admin_footer', [ __CLASS__, 'render' ] );
		}
	}

	/**
	 *
	 */
	public static function render() {
		self::general_fields();
		self::html5_fields();
		self::custom_fields();
		self::misc_fields();
		self::helpers();
		self::conditions_editor();
		self::triggers_editor();
		self::cookies_editor();

		if ( class_exists( 'PUM_MCI' ) && version_compare( PUM_MCI::$VER, '1.3.0', '<' ) ) {
			?>

			<script type="text/html" id="tmpl-pum-field-mc_api_key">
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<# var valid = data.value !== '' && <?php echo PUM_Utils_Array::safe_json_encode( pum_get_option( 'mci_api_key_is_valid', false ) ); ?>; #>
				<input type="{{valid ? 'password' : 'text'}}" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
				<button type="button" class="pum-mci-check-api-key">
					<?php esc_html_e( 'Check', 'popup-maker' ); ?>
					<i class="dashicons dashicons-{{valid ? 'yes' : 'no'}}" style="display: {{valid ? 'inline-block' : 'none'}};"></i>
				</button>
				<?php wp_nonce_field( 'pum-mci-check-api-key', null ); ?>
			</script>

			<?php
		}
	}

	/**
	 *
	 */
	public static function general_fields() {
		?>
		<script type="text/html" id="tmpl-pum-field-text">
			<input type="text" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
		</script>

		<script type="text/html" id="tmpl-pum-field-password">
			<input type="password" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
		</script>

		<script type="text/html" id="tmpl-pum-field-select">
			<select id="{{data.id}}" name="{{data.name}}" data-allow-clear="true" {{{data.meta}}}>
				<# _.each(data.options, function(option, key) {

				if (option.options !== undefined && option.options.length) { #>

				<optgroup label="{{option.label}}">

					<# _.each(option.options, function(option, key) { #>
					<option value="{{option.value}}" {{{option.meta}}}>{{option.label}}</option>
					<# }); #>

				</optgroup>

				<# } else { #>
				<option value="{{option.value}}" {{{option.meta}}}>{{option.label}}</option>
				<# }

				}); #>
			</select>
		</script>

		<script type="text/html" id="tmpl-pum-field-radio">
			<ul class="pum-field-radio-list">
				<# _.each(data.options, function(option, key) { #>
				<li
				<# print(option.value === data.value ? 'class="pum-selected"' : ''); #>>
				<input type="radio" id="{{data.id}}_{{key}}" name="{{data.name}}" value="{{option.value}}" {{{option.meta}}}/>
				<label for="{{data.id}}_{{key}}">{{option.label}}</label>
				</li>
				<# }); #>
			</ul>
		</script>

		<script type="text/html" id="tmpl-pum-field-checkbox">
			<input type="checkbox" id="{{data.id}}" name="{{data.name}}" value="1" {{{data.meta}}} {{data.disabled ? 'disabled' : ''}} {{data.checked ? 'checked' : ''}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-multicheck">
			<ul class="pum-field-mulitcheck-list">
				<# _.each(data.options, function(option, key) { #>
				<li>
					<input type="checkbox" id="{{data.id}}_{{key}}" name="{{data.name}}[{{option.value}}]" value="{{option.value}}" {{{option.meta}}}/>
					<label for="{{data.id}}_{{key}}">{{{option.label}}}</label>
				</li>
				<# }); #>
			</ul>
		</script>

		<script type="text/html" id="tmpl-pum-field-textarea">
			<textarea name="{{data.name}}" id="{{data.id}}" class="{{data.size}}-text" {{{data.meta}}}>{{data.value}}</textarea>
		</script>

		<script type="text/html" id="tmpl-pum-field-hidden">
			<input type="hidden" class="{{data.classes}}" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
		</script>
		<?php
	}

	/**
	 *
	 */
	public static function html5_fields() {
		?>
		<script type="text/html" id="tmpl-pum-field-range">
			<input type="range" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
		</script>

		<script type="text/html" id="tmpl-pum-field-search">
			<input type="search" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
		</script>

		<script type="text/html" id="tmpl-pum-field-number">
			<input type="number" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
		</script>

		<script type="text/html" id="tmpl-pum-field-email">
			<input type="email" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
		</script>

		<script type="text/html" id="tmpl-pum-field-url">
			<input type="url" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
		</script>

		<script type="text/html" id="tmpl-pum-field-tel">
			<input type="tel" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
		</script>
		<?php
	}

	/**
	 *
	 */
	public static function custom_fields() {
		?>
		<script type="text/html" id="tmpl-pum-field-editor">
			<textarea name="{{data.name}}" id="{{data.id}}" class="pum-wpeditor {{data.size}}-text" {{{data.meta}}}>{{data.value}}</textarea>
		</script>

		<script type="text/html" id="tmpl-pum-field-link">
			<button type="button" class="dashicons dashicons-admin-generic button"></button>
			<input type="text" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
		</script>

		<script type="text/html" id="tmpl-pum-field-rangeslider">
			<input type="text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" class="pum-range-manual" {{{data.meta}}}/>
			<span class="pum-range-value-unit regular-text">{{data.unit}}</span>
		</script>

		<script type="text/html" id="tmpl-pum-field-color">
			<input type="text" class="pum-color-picker color-picker" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" data-default-color="{{data.std}}" {{{data.meta}}}/>
		</script>

		<script type="text/html" id="tmpl-pum-field-measure">
			<input type="number" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" size="5" {{{data.meta}}}/>
			<select id="{{data.id}}_unit" name="<# print(data.name.replace(data.id, data.id + '_unit')); #>">
				<# _.each(data.units, function(option, key) { #>
				<option value="{{option.value}}" {{{option.meta}}}>{{option.label}}</option>
				<# }); #>
			</select>
		</script>

		<script type="text/html" id="tmpl-pum-field-license_key">
			<#
			var isActive = data.value.status === 'valid';
			var shouldMask = isActive && data.value.key && data.value.key.length > 6;
			var shouldDisable = isActive || data.value.auto_activated;
			var displayValue = shouldMask ? data.value.key.substring(0,3) + '*'.repeat(data.value.key.length - 6) + data.value.key.slice(-3) : data.value.key;
			#>

			<# if (data.value.auto_activated) { #>
			<!-- Auto-activated license: readonly field with notice -->
			<input class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{displayValue}}" autocomplete="off" readonly style="background: #f1f1f1;" {{{data.meta}}}/>
			<p class="description" style="color: #0073aa; font-weight: 500;">
				<?php esc_html_e( 'This license is automatically activated.', 'popup-maker' ); ?>
			</p>
			<# } else { #>
			<!-- Regular license field: mask and disable when active -->
			<input class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{displayValue}}" autocomplete="off" <# if (shouldDisable) { #>disabled<# } #> {{{data.meta}}}/>
			<# if (isActive && shouldMask) { #>
			<p class="description" style="color: #666; font-style: italic; margin: 5px 0;">
				<?php esc_html_e( 'License key is masked for security. Deactivate to make changes.', 'popup-maker' ); ?>
			</p>
			<# } #>
			<# } #>

			<# if (data.value.key !== '') { #>
			<?php wp_nonce_field( 'pum_license_activation', 'pum_license_activation_nonce' ); ?>
			<# if (data.value.status === 'valid') { #>
			<span class="pum-license-status"><?php esc_html_e( 'Active', 'popup-maker' ); ?></span>
			<input type="submit" class="button-secondary pum-license-deactivate" id="{{data.id}}_deactivate" name="pum_license_deactivate[{{data.id}}]" value="<?php esc_attr_e( 'Deactivate License', 'popup-maker' ); ?>"/>
			<# } else { #>
			<span class="pum-license-status"><?php esc_html_e( 'Inactive', 'popup-maker' ); ?></span>
			<input type="submit" class="button-secondary pum-license-activate" id="{{data.id}}_activate" name="pum_license_activate[{{data.id}}]" value="<?php esc_attr_e( 'Activate License', 'popup-maker' ); ?>"/>
			<# } #>
			<# } #>

			<# if (data.value.messages && data.value.messages.length) { #>
			<div class="pum-license-messages">
				<# for(var i=0; i < data.value.messages.length; i++) { #>
				<p>{{{data.value.messages[i]}}}</p>
				<# } #>
			</div>
			<# } #>
		</script>

		<?php
		$license_service   = \PopupMaker\plugin( 'license' );
		$is_auto_activated = $license_service->is_auto_activated();

		?>

		<script type="text/html" id="tmpl-pum-field-pro_license">
			<#
			var hasKey = data.value && data.value.key && data.value.key.length > 0;
			var shouldMask = hasKey && data.value.key.length > 6;
			var displayValue = shouldMask ? data.value.key.substring(0,3) + '*'.repeat(data.value.key.length - 6) + data.value.key.slice(-3) : (data.value && data.value.key ? data.value.key : '');
			var safeValue = data.value || {};
			var isAutoActivated = <?php echo $is_auto_activated ? 'true' : 'false'; ?>;

			// Use comprehensive status system instead of binary logic
			var status = (data.value && data.value.status) ? data.value.status : 'empty';
			var statusClasses = (data.value && data.value.classes) ? data.value.classes : 'pum-license-empty';
			var isActive = status === 'valid';
			var isDeactivated = status === 'deactivated';
			var isInactive = !isActive && !isDeactivated && hasKey;

			// Get license tier (pro or pro_plus).
			var licenseTier = (data.value && data.value.license_tier) ? data.value.license_tier : 'pro';
			var isProPlus = licenseTier === 'pro_plus';

			// Get Pro installation status.
			var isProInstalled = (data.value && data.value.is_pro_installed) ? data.value.is_pro_installed : false;
			var isProActive = (data.value && data.value.is_pro_active) ? data.value.is_pro_active : false;

			// Add status class to parent field wrapper after render
			setTimeout(function() {
				var fieldWrapper = document.querySelector('.pum-field-pro_license');
				if (fieldWrapper) {
					// Remove any existing license status classes
					fieldWrapper.className = fieldWrapper.className.replace(/pum-license-\w+-notice/g, '');
					// Remove any existing tier classes
					fieldWrapper.className = fieldWrapper.className.replace(/pum-license-tier-\w+/g, '');

					// Add license tier class
					fieldWrapper.classList.add('pum-license-tier-' + licenseTier.replace('_', '-'));

					// Add new status class for field-level styling
					if (status === 'deactivated') {
						fieldWrapper.classList.add('pum-license-deactivated-notice');
					} else if (status === 'valid') {
						fieldWrapper.classList.add('pum-license-valid-notice');
					} else if (status === 'expired') {
						fieldWrapper.classList.add('pum-license-expired-notice');
					} else if (status === 'error') {
						fieldWrapper.classList.add('pum-license-error-notice');
					}
				}
			}, 10);
			#>

			<!-- Main Content Container (like Content Control) -->
			<div class="pum-pro-license-content <# if (hasKey && isProPlus) { #>pum-license-tier-pro-plus<# } else if (hasKey) { #>pum-license-tier-pro<# } #>">
				<!-- Pro Licensing Header (like Content Control) -->
				<div class="pum-pro-license-header <# if (isProPlus) { #>pro-plus<# } #>">
					<img class="pum-license-logo" src="<?php echo esc_url( POPMAKE_URL . '/assets/images/mark.svg' ); ?>" alt="<?php esc_attr_e( 'Popup Maker', 'popup-maker' ); ?>" />

					<div class="pum-license-header-text">
						<h3 class="pum-license-heading">
							<# if (isProPlus) { #>
								<?php esc_html_e( 'Pro+', 'popup-maker' ); ?>
							<# } else { #>
								<?php esc_html_e( 'Pro', 'popup-maker' ); ?>
							<# } #>
							<?php esc_html_e( 'Licensing', 'popup-maker' ); ?>
						</h3>

						<# if (isProPlus) { #>
						<span class="pum-license-subtitle"><?php esc_html_e( 'Premium Ecommerce Edition', 'popup-maker' ); ?></span>
						<# } #>
					</div>


					<# if (hasKey) { #>
					<span class="pum-license-status-badge <# if (isActive) { #>active<# } else if (isDeactivated) { #>deactivated<# } else if (status === 'expired') { #>expired<# } else { #>error<# } #> <# if (isProPlus) { #>pro-plus<# } #>">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="crown-icon"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path><path d="M5 21h14"></path></svg>
						<# if (isActive) { #>
							<# if (isProPlus) { #>
								<?php esc_html_e( 'Pro+', 'popup-maker' ); ?>
							<# } else { #>
								<?php esc_html_e( 'Pro', 'popup-maker' ); ?>
							<# } #>
							<?php esc_html_e( 'Activated', 'popup-maker' ); ?>
						<# } else if (isDeactivated) { #>
							<# if (isProPlus) { #>
								<?php esc_html_e( 'Pro+', 'popup-maker' ); ?>
							<# } else { #>
								<?php esc_html_e( 'Pro', 'popup-maker' ); ?>
							<# } #>
							<?php esc_html_e( 'Deactivated', 'popup-maker' ); ?>
						<# } else if (status === 'expired') { #>
							<?php esc_html_e( 'Expired', 'popup-maker' ); ?>
						<# } else { #>
							<?php esc_html_e( 'Invalid', 'popup-maker' ); ?>
						<# } #>
						<# if (isActive) { #>
							<# if (isProPlus) { #>
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lightning-icon"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path></svg>
							<# } else { #>
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="star-icon"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path><path d="M4 17v2"></path><path d="M5 18H3"></path></svg>
							<# } #>
						<# } #>
					</span>
					<# } #>
				</div>

				<div class="pum-pro-license-content-inner pum-flex pum-flex--column pum-flex--gap-l">

					<div class="pum-flex pum-flex--column pum-flex--gap-m">

						<div class="pum-flex pum-flex--column pum-flex--gap-s">
							<h3 class="pum-license-heading">
								<?php esc_html_e( 'Enter your Popup Maker Pro License Key', 'popup-maker' ); ?>
							</h3>

							<p class="pum-license-description">
								<# if (isActive) { #>
									<# if (isProPlus) { #>
										<?php esc_html_e( 'Your Pro+ license key is active.', 'popup-maker' ); ?>
									<# } else { #>
										<?php esc_html_e( 'Your Pro license key is active.', 'popup-maker' ); ?>
									<# } #>
								<# } else if (isDeactivated) { #>
									<?php esc_html_e( 'Your Pro license is valid but deactivated on this site.', 'popup-maker' ); ?>
								<# } else if (data.value && data.value.has_extensions) { #>
									<?php esc_html_e( 'You are currently using Popup Maker with extensions — keep enjoying the enhanced features!', 'popup-maker' ); ?> 🚀
								<# } else { #>
									<?php esc_html_e( 'You are currently using Popup Maker Free — no license key required. Enjoy!', 'popup-maker' ); ?> 😄
								<# } #>
							</p>
						</div>

						<# if (!isAutoActivated) { #>

							<div class="pum-flex pum-flex--column pum-flex--gap-xs">


								<span class="pum-license-input-label">
										<?php esc_html_e( 'Enter your license key below to activate', 'popup-maker' ); ?> <strong><# if (isProPlus) { #><?php esc_html_e( 'Popup Maker Pro+', 'popup-maker' ); ?><# } else { #><?php esc_html_e( 'Popup Maker Pro', 'popup-maker' ); ?><# } #></strong><?php esc_html_e( '!', 'popup-maker' ); ?>
								</span>

								<div class="pum-license-input-wrapper">


									<div class="pum-license-input-group">
										<input type="text" placeholder="<?php esc_attr_e( 'Paste or enter your license key here.', 'popup-maker' ); ?>" class="{{data.size}}-text pum-license-key-input" id="{{data.id}}" name="{{data.name}}" value="{{displayValue}}" autocomplete="off" <# if (isActive) { #>disabled<# } #> {{{data.meta}}}/>


										<div class="pum-license-buttons">
											<?php wp_nonce_field( 'pum_license_operation_nonce', 'pum_license_operation_nonce' ); ?>

											<# if (!hasKey) { #>
											<input type="submit" class="button button-primary pum-license-activate" id="{{data.id}}_activate" name="pum_license_operation[activate]" value="<?php esc_attr_e( 'Activate', 'popup-maker' ); ?>" disabled/>

											<# } else if (isActive && !isAutoActivated) { #>
											<span class="pum-license-status {{statusClasses}}"><?php esc_html_e( 'Active', 'popup-maker' ); ?></span>
											<input type="submit" class="button button-secondary pum-license-deactivate" id="{{data.id}}_deactivate" name="pum_license_operation[deactivate]" value="<?php esc_attr_e( 'Deactivate', 'popup-maker' ); ?>"/>
											<input type="submit" class="button pum-license-delete" id="{{data.id}}_delete" name="pum_license_operation[delete]" value="<?php esc_attr_e( 'Delete', 'popup-maker' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this license key? This action cannot be undone.', 'popup-maker' ); ?>')"/>

											<# } else if (isDeactivated && !isAutoActivated) { #>
											<span class="pum-license-status {{statusClasses}}"><?php esc_html_e( 'Deactivated', 'popup-maker' ); ?></span>
											<input type="submit" class="button button-primary pum-license-activate" id="{{data.id}}_activate" name="pum_license_operation[activate]" value="<?php esc_attr_e( 'Activate', 'popup-maker' ); ?>"/>
											<input type="submit" class="button pum-license-delete" id="{{data.id}}_delete" name="pum_license_operation[delete]" value="<?php esc_attr_e( 'Delete', 'popup-maker' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this license key? This action cannot be undone.', 'popup-maker' ); ?>')"/>

											<# } else if (hasKey && !isActive && !isDeactivated && !isAutoActivated) { #>
											<span class="pum-license-status {{statusClasses}}"><?php esc_html_e( 'Inactive', 'popup-maker' ); ?></span>
											<input type="submit" class="button button-primary pum-license-activate" id="{{data.id}}_activate" name="pum_license_operation[activate]" value="<?php esc_attr_e( 'Activate', 'popup-maker' ); ?>"/>
											<input type="submit" class="button pum-license-delete" id="{{data.id}}_delete" name="pum_license_operation[delete]" value="<?php esc_attr_e( 'Delete', 'popup-maker' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this license key? This action cannot be undone.', 'popup-maker' ); ?>')"/>

											<# } else if (isActive && isAutoActivated) { #>
											<span class="pum-license-status {{statusClasses}}"><?php esc_html_e( 'Active', 'popup-maker' ); ?></span>
											<span class="description" style="color: #0073aa; font-style: italic;"><?php esc_html_e( '(Auto-activated)', 'popup-maker' ); ?></span>
											<# } #>

											<!-- Install Pro Button - when Pro is not installed -->
											<# if (isActive && !isProInstalled) { #>
												<button type="button" class="button pum-install-pro-button pum-license-connect-trigger <# if (isProPlus) { #>pum-install-pro-plus<# } #>" data-source="settings-page" data-product="popup-maker-pro">
													<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="download-icon"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7,10 12,15 17,10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
													<?php esc_html_e( 'INSTALL PRO!', 'popup-maker' ); ?>
												</button>
											<!-- Activate Pro Button - when Pro is installed but not active -->
											<# } else if (isActive && isProInstalled && !isProActive) { #>
												<button type="button" class="button pum-install-pro-button pum-license-connect-trigger <# if (isProPlus) { #>pum-install-pro-plus<# } #>" data-source="settings-page" data-product="popup-maker-pro">
													<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="power-icon"><circle cx="12" cy="12" r="10"></circle><path d="m12 6-4 6 7 0-4 6"></path></svg>
													<?php esc_html_e( 'ACTIVATE PRO NOW!', 'popup-maker' ); ?>
												</button>
											<# } #>
										</div>

									</div>

								</div>

							</div>

							<# if (!isActive && !isDeactivated) { #>
								<div class="pum-flex pum-flex--column pum-flex--gap-xs">
									<div class="pum-license-upgrade-text">
										<?php esc_html_e( 'Enter your license key to activate. If you do not have a license key, you can', 'popup-maker' ); ?>
										<a href="https://wppopupmaker.com/pricing/?utm_source=plugin-settings&utm_medium=pro-license-field&utm_campaign=upgrade" target="_blank"><?php esc_html_e( 'purchase one here', 'popup-maker' ); ?></a>
									</div>
								</div>
							<# } else { #>
								<# if (isActive) { #>
									<# if (isProPlus) { #>
										<!-- Pro installed and Pro+ license -->
										<div class="pum-license-callout pum-license-callout--activated">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="star-icon"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path><path d="M4 17v2"></path><path d="M5 18H3"></path></svg>
											<?php esc_html_e( 'Your Pro+ license key is active.', 'popup-maker' ); ?>
											<?php esc_html_e( 'Thank you for supporting Popup Maker!', 'popup-maker' ); ?> 😊
										</div>
									<# } else { #>
										<!-- Pro installed and regular Pro license -->
										<div class="pum-license-callout pum-license-callout--activated">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="star-icon"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path><path d="M4 17v2"></path><path d="M5 18H3"></path></svg>
											<?php esc_html_e( 'Your Pro license key is active.', 'popup-maker' ); ?>
											<?php esc_html_e( 'Thank you for supporting Popup Maker!', 'popup-maker' ); ?> 😊
										</div>
									<# } #>
								<# } else if (isDeactivated) { #>
									<div class="pum-license-callout pum-license-callout--deactivated">
										<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="info-icon"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
										<span><?php esc_html_e( 'Your Pro license is valid but deactivated on this site.', 'popup-maker' ); ?> <?php esc_html_e( 'Activate it to enable Pro features!', 'popup-maker' ); ?> 🔑</span>
									</div>
								<# } #>

							<# } #>

						<# } else { #>
							<div class="pum-license-callout pum-license-callout--activated">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="star-icon"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path><path d="M4 17v2"></path><path d="M5 18H3"></path></svg>
								<# if (isProPlus) { #>
									<?php esc_html_e( 'Your Pro+ license is automatically activated.', 'popup-maker' ); ?>
								<# } else { #>
									<?php esc_html_e( 'Your Pro license is automatically activated.', 'popup-maker' ); ?>
								<# } #>
								<?php esc_html_e( 'Thank you for supporting Popup Maker!', 'popup-maker' ); ?> 😊
							</div>
						<# } #>


					</div>


					<# if (safeValue.messages && safeValue.messages.length) { #>
					<div class="pum-license-messages">
						<# for(var i=0; i < safeValue.messages.length; i++) { #>
						<p class="error">{{{safeValue.messages[i]}}}</p>
						<# } #>
					</div>
					<# } #>

					<# if (! isAutoActivated && (isActive || isDeactivated)) { #>
						<div class="pum-license-details <# if (isActive) { #>pum-license-details--active<# } #> <# if (isProPlus) { #>pum-license-details--pro-plus<# } #>">
							<div class="pum-license-details-header">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg>
								<h4><?php esc_html_e( 'License Details', 'popup-maker' ); ?></h4>
							</div>
							<table class="pum-license-details-table">
								<tr>
									<th><?php esc_html_e( 'Status:', 'popup-maker' ); ?></th>
									<td class="pum-license-status <# if (isActive) { #>pum-license-status--active<# } else if (isDeactivated) { #>pum-license-status--deactivated<# } #>"><?php esc_html_e( 'Active', 'popup-maker' ); ?></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'License Key:', 'popup-maker' ); ?></th>
									<td>{{displayValue}}</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Pro Plugin:', 'popup-maker' ); ?></th>
									<td class="pum-pro-status <# if (isProActive) { #>pum-pro-status--active<# } else if (isProInstalled) { #>pum-pro-status--installed<# } else { #>pum-pro-status--not-installed<# } #>">
										<# if (isProActive) { #>
											<?php esc_html_e( 'Active', 'popup-maker' ); ?><# if (safeValue.pro_version) { #> (v{{safeValue.pro_version}})<# } #>
										<# } else if (isProInstalled) { #>
											<?php esc_html_e( 'Installed', 'popup-maker' ); ?><# if (safeValue.pro_version) { #> (v{{safeValue.pro_version}})<# } #>
										<# } else { #>
											<?php esc_html_e( 'Not Installed', 'popup-maker' ); ?>
										<# } #>
									</td>
								</tr>
								<# if (safeValue.expires && safeValue.expires !== 'lifetime') { #>
								<tr>
									<th><?php esc_html_e( 'Expires:', 'popup-maker' ); ?></th>
									<td>{{safeValue.expires}}</td>
								</tr>
								<# } else if (safeValue.expires === 'lifetime') { #>
								<tr>
									<th><?php esc_html_e( 'License Type:', 'popup-maker' ); ?></th>
									<td>
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="crown-icon"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"></path><path d="M5 21h14"></path></svg>
										<# if (isProPlus) { #>
											<?php esc_html_e( 'Pro+', 'popup-maker' ); ?>
										<# } else { #>
											<?php esc_html_e( 'Pro', 'popup-maker' ); ?>
										<# } #>
										<?php esc_html_e( 'Lifetime License', 'popup-maker' ); ?>
									</td>
								</tr>
								<# } #>
							</table>
						</div>
					<# } #>

					<hr class="pum-license-separator" />

					<div class="pum-pro-features">
						<div class="pum-features-heading">
							<# if (isActive && isProPlus) { #>
								<?php esc_html_e( '🎉 You have access to these powerful Pro+ features:', 'popup-maker' ); ?>
								<span><?php esc_html_e( 'Plus exclusive Pro+ ecommerce tracking capabilities!', 'popup-maker' ); ?></span>
							<# } else if (isActive) { #>
								<?php esc_html_e( '🎉 You have access to these powerful Pro features:', 'popup-maker' ); ?>
							<# } else if (isDeactivated) { #>
								<?php esc_html_e( 'Activate your Pro license above to unlock these powerful features:', 'popup-maker' ); ?>
							<# } else { #>
								<?php esc_html_e( 'To unlock these game-changing features,', 'popup-maker' ); ?>
								<a href="https://wppopupmaker.com/pricing/?utm_source=plugin-settings&utm_medium=pro-license-field&utm_campaign=upgrade" target="_blank"><?php esc_html_e( 'upgrade to Pro', 'popup-maker' ); ?></a>
								<?php esc_html_e( ' and enter your license key above.', 'popup-maker' ); ?>
							<# } #>
						</div>

						<div class="pum-features-grid">

							<div class="pum-feature-column">
								<h4 class="pum-feature-heading"><?php esc_html_e( 'Smart Targeting & Automation', 'popup-maker' ); ?></h4>
								<ul>
									<li class="pro-feature">
										<span class="feature-icon dashicons dashicons-yes-alt"></span>
										<div>
											<strong><?php esc_html_e( 'FluentCRM Integration', 'popup-maker' ); ?></strong>
											<?php esc_html_e( ' Seamlessly add tags, trigger automation sequences, and create smart links. Perfect for lead nurturing and customer journey automation.', 'popup-maker' ); ?>
										</div>
									</li>
									<li class="pro-feature">
										<span class="feature-icon dashicons dashicons-yes-alt"></span>
										<div>
											<strong><?php esc_html_e( 'Advanced Targeting & Behavioral Triggers', 'popup-maker' ); ?></strong>
											<?php esc_html_e( ' Target by user role, comment history, device, and custom behaviors. Show the right message to the right person at the perfect time.', 'popup-maker' ); ?>
										</div>
									</li>
									<li class="pro-feature">
										<span class="feature-icon dashicons dashicons-yes-alt"></span>
										<div>
											<strong><?php esc_html_e( 'Professional CTA Management', 'popup-maker' ); ?></strong>
											<?php esc_html_e( ' One-click export/import for CTAs. Create shareable vanity URLs. Bulk operations for enterprise-scale management.', 'popup-maker' ); ?>
										</div>
									</li>

									<li class="pro-feature">
										<span class="feature-icon dashicons dashicons-yes-alt"></span>
										<div>
											<strong><?php esc_html_e( 'Time-Based Campaign Scheduling', 'popup-maker' ); ?></strong>
											<?php esc_html_e( ' Smart campaign scheduling with timezone support and recurring campaigns. Perfect timing for seasonal promotions and global audiences.', 'popup-maker' ); ?>
										</div>
									</li>
								</ul>
							</div>

							<div class="pum-feature-column">
								<h4 class="pum-feature-heading"><?php esc_html_e( 'Monetize & Track Every Conversion', 'popup-maker' ); ?></h4>
								<ul>
									<li class="pro-feature pro-plus-enhanced-feature">
										<span class="feature-icon dashicons dashicons-yes-alt"></span>
										<div>
											<strong><?php esc_html_e( 'Revenue Attribution & Analytics', 'popup-maker' ); ?><span class="pum-pro-plus-badge"><?php esc_html_e( 'Enhanced with Pro+', 'popup-maker' ); ?></span></strong>
											<?php esc_html_e( ' Track every dollar earned through your popups. Prove ROI with complete conversion tracking and detailed revenue reports.', 'popup-maker' ); ?>
										</div>
									</li>
									<li class="pro-feature pro-plus-enhanced-feature">
										<span class="feature-icon dashicons dashicons-yes-alt"></span>
										<div>
											<strong><?php esc_html_e( 'Real-Time Analytics & Insights', 'popup-maker' ); ?><span class="pum-pro-plus-badge"><?php esc_html_e( 'Enhanced with Pro+', 'popup-maker' ); ?></span></strong>
											<?php esc_html_e( ' Live performance tracking with detailed conversion metrics. Monitor popup effectiveness in real-time and make data-driven optimization decisions instantly.', 'popup-maker' ); ?>
										</div>
									</li>
									<li class="pro-plus-exclusive-feature">
										<span class="feature-icon dashicons dashicons-yes-alt"></span>
										<div>
											<strong><?php esc_html_e( 'WooCommerce Integration', 'popup-maker' ); ?><span class="pum-pro-plus-badge"><?php esc_html_e( 'Pro+', 'popup-maker' ); ?> <?php esc_html_e( 'Exclusive', 'popup-maker' ); ?></span></strong>
											<?php esc_html_e( ' Add products to cart, apply discounts, and recover abandoned carts with complete revenue attribution. Proven to increase sales by 15-40%.', 'popup-maker' ); ?>
										</div>
									</li>
									<li class="pro-plus-exclusive-feature">
										<span class="feature-icon dashicons dashicons-yes-alt"></span>
										<div>
											<strong><?php esc_html_e( 'Easy Digital Downloads Integration', 'popup-maker' ); ?><span class="pum-pro-plus-badge"><?php esc_html_e( 'Pro+', 'popup-maker' ); ?> <?php esc_html_e( 'Exclusive', 'popup-maker' ); ?></span></strong>
											<?php esc_html_e( ' Perfect for software and digital product sales with license management. Seamlessly integrate checkout flows and boost digital sales conversion rates.', 'popup-maker' ); ?>
										</div>
									</li>
								</ul>
							</div>
						</div>

						<!-- Backup features for A/B testing -->
						<!--
							Alternative high-value features for randomization:
								- A/B Testing & Optimization: Test popup variations automatically to maximize conversion rates
								- Time-Based Scheduling: Smart campaign scheduling with timezone support and recurring campaigns
						- User Role Targeting: Show different content to different user types (Admin, Subscriber, etc.)
						- LifterLMS Integration: Automate student enrollment and rewards for course creators
						- Easy Digital Downloads: Perfect for software and digital product sales with license management
						- Advanced Shortcodes: Developer-friendly trigger customization and complex display logic
						-->
					</div>
				</div>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-field-datetime">
			<div class="pum-datetime">
				<input placeholder="{{data.placeholder}}" data-input class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
				<a class="input-button" data-toggle><i class="dashicons dashicons-calendar-alt"></i></a>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-field-datetimerange">
			<div class="pum-datetime-range">
				<input placeholder="{{data.placeholder}}" data-input class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
				<a class="input-button" data-toggle><i class="dashicons dashicons-calendar-alt"></i></a>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-field-ga_event_labels">
			<# data.value = _.extend({
			category:'',
			action: '',
			label: '',
			value: 0,
			}, data.value); #>

			<table>
				<tbody>
				<tr>
					<td>
						<label for="{{data.id}}_category" style="padding-left: 3px;"><?php esc_html_e( 'Category', 'popup-maker' ); ?></label>
						<input type="text" style="width:100%;" id="{{data.id}}_category" name="{{data.name}}[category]" value="{{data.value.category}}"/>
					</td>
					<td>
						<label for="{{data.id}}_action" style="padding-left: 3px;"><?php esc_html_e( 'Action', 'popup-maker' ); ?></label>
						<input type="text" style="width:100%;" id="{{data.id}}_action" name="{{data.name}}[action]" value="{{data.value.action}}"/>
					</td>
					<td>
						<label for="{{data.id}}_label" style="padding-left: 3px;"><?php esc_html_e( 'Label', 'popup-maker' ); ?></label>
						<input type="text" style="width:100%;" id="{{data.id}}_label" name="{{data.name}}[label]" value="{{data.value.label}}"/>
					</td>
					<td>
						<label for="{{data.id}}_value" style="padding-left: 3px;"><?php esc_html_e( 'Value', 'popup-maker' ); ?></label>
						<input type="number" style="width:100%;height: auto;" id="{{data.id}}_value" name="{{data.name}}[value]" value="{{data.value.value}}" step="0.01" max="999999" min="0"/>
					</td>
				</tr>
				</tbody>
			</table>

			<hr/>
		</script>
		<?php
	}

	/**
	 *
	 */
	public static function misc_fields() {
		?>
		<script type="text/html" id="tmpl-pum-field-section">
			<div class="pum-field-section {{data.classes}}">
				<# _.each(data.fields, function(field) { #>
				{{{field}}}
				<# }); #>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-field-wrapper">
			<div class="pum-field pum-field-{{data.type}} {{data.id}}-wrapper {{data.classes}}"
					data-id="{{data.id}}" <# print( data.dependencies !== '' ? "data-pum-dependencies='" + data.dependencies + "'" : ''); #> <# print( data.dynamic_desc !== '' ? "data-pum-dynamic-desc='" + data.dynamic_desc + "'" : ''); #>>
			<# if (typeof data.label === 'string' && data.label.length > 0) { #>
			<label for="{{data.id}}">
				{{data.label}}
				<# if (typeof data.doclink === 'string' && data.doclink !== '') { #>
				<a href="{{data.doclink}}" title="<?php esc_attr_e( 'Documentation', 'popup-maker' ); ?>: {{data.label}}" target="_blank" class="pum-doclink dashicons dashicons-editor-help"></a>
				<# } #>
			</label>
			<# } else { #>
			<# if (typeof data.doclink === 'string' && data.doclink !== '') { #>
			<a href="{{data.doclink}}" title="<?php esc_attr_e( 'Documentation', 'popup-maker' ); ?>: {{data.label}}" target="_blank" class="pum-doclink dashicons dashicons-editor-help"></a>
			<# } #>
			<# } #>
			{{{data.field}}}
			<# if (typeof data.desc === 'string' && data.desc.length > 0) { #>
			<span class="pum-desc desc">{{{data.desc}}}</span>
			<# } #>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-field-html">
			{{{data.content}}}
		</script>

		<script type="text/html" id="tmpl-pum-field-heading">
			<h3 class="pum-field-heading">{{data.desc}}</h3>
		</script>

		<script type="text/html" id="tmpl-pum-field-separator">
			<# if (typeof data.desc === 'string' && data.desc.length > 0 && data.desc_position === 'top') { #>
			<h3 class="pum-field-heading">{{data.desc}}</h3>
			<# } #>
			<hr {{{data.meta}}}/>
			<# if (typeof data.desc === 'string' && data.desc.length > 0 && data.desc_position === 'bottom') { #>
			<h3 class="pum-field-heading">{{data.desc}}</h3>
			<# } #>
		</script>
		<?php
	}

	/**
	 *
	 */
	public static function helpers() {
		?>
		<script type="text/html" id="tmpl-pum-modal">
			<div id="{{data.id}}" class="pum-modal-background {{data.classes}}" role="dialog" aria-modal="false" aria-labelledby="{{data.id}}-title" aria-describedby="{{data.id}}-description" {{{data.meta}}}>
				<div class="pum-modal-wrap">
					<form class="pum-form">
						<div class="pum-modal-header">
							<# if (data.title.length) { #>
							<span id="{{data.id}}-title" class="pum-modal-title">{{data.title}}</span>
							<# } #>
							<button type="button" class="pum-modal-close" aria-label="<?php esc_attr_e( 'Close', 'popup-maker' ); ?>"></button>
						</div>
						<# if (data.description.length) { #>
						<span id="{{data.id}}-description" class="screen-reader-text">{{data.description}}</span>
						<# } #>
						<div class="pum-modal-content">
							{{{data.content}}}
						</div>
						<# if (data.save_button || data.cancel_button) { #>
						<div class="pum-modal-footer submitbox">
							<# if (data.cancel_button) { #>
							<div class="cancel">
								<button type="button" class="submitdelete no-button" href="#">{{data.cancel_button}}</button>
							</div>
							<# } #>
							<# if (data.save_button) { #>
							<div class="pum-submit">
								<span class="spinner"></span>
								<button class="button button-primary">{{data.save_button}}</button>
							</div>
							<# } #>
						</div>
						<# } #>
					</form>
				</div>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-tabs">
			<div class="pum-tabs-container {{data.classes}}" {{{data.meta}}}>
				<ul class="tabs">
					<# _.each(data.tabs, function(tab, key) { #>
					<li class="tab">
						<a href="#{{data.id + '_' + key}}">{{tab.label}}</a>
					</li>
					<# }); #>
				</ul>
				<# _.each(data.tabs, function(tab, key) { #>
				<div id="{{data.id + '_' + key}}" class="tab-content">
					{{{tab.content}}}
				</div>
				<# }); #>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-shortcode">
			[{{{data.tag}}} {{{data.meta}}}]
		</script>

		<script type="text/html" id="tmpl-pum-shortcode-w-content">
			[{{{data.tag}}} {{{data.meta}}}]{{{data.content}}}[/{{{data.tag}}}]
		</script>
		<?php
	}

	/**
	 *
	 */
	public static function triggers_editor() {
		?>
		<script type="text/html" id="tmpl-pum-field-triggers">
			<# print(PUM_Admin.triggers.template.editor({triggers: data.value, name: data.name})); #>
		</script>

		<script type="text/html" id="tmpl-pum-trigger-editor">
			<div class="pum-popup-trigger-editor  <# if (data.triggers && data.triggers.length) { print('has-list-items'); } #>" data-field_name="{{data.name}}">
				<button type="button" class="button button-primary pum-add-new no-button"><?php esc_html_e( 'Add New Trigger', 'popup-maker' ); ?></button>

				<p>
					<strong>
						<?php
						$learn_more_text = sprintf(
							/* translators: 1. contextual help link text. */
							__( 'Learn more about %s', 'popup-maker' ),
							__( 'Triggers', 'popup-maker' )
						);
						?>
						<?php esc_html_e( 'Triggers cause a popup to open.', 'popup-maker' ); ?>
						<a href="<?php echo esc_url( 'https://wppopupmaker.com/docs/getting-started/triggers/?utm_campaign=contextual-help&utm_medium=inline-doclink&utm_source=plugin-popup-editor&utm_content=triggers-option-settings' ); ?>" target="_blank"
							class="pum-doclink dashicons dashicons-editor-help" title="<?php echo esc_attr( $learn_more_text ); ?>"></a>
					</strong>
				</p>

				<table class="list-table form-table">
					<thead>
					<tr>
						<th><?php esc_html_e( 'Type', 'popup-maker' ); ?></th>
						<th><?php esc_html_e( 'Cookie', 'popup-maker' ); ?></th>
						<th><?php esc_html_e( 'Settings', 'popup-maker' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'popup-maker' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<#
					_.each(data.triggers, function (trigger, index) {
					print(PUM_Admin.triggers.template.row({
					index: index,
					type: trigger.type,
					name: data.name,
					settings: trigger.settings || {}
					}));
					});
					#>
					</tbody>
				</table>

				<!--				<div class="no-triggers  no-list-items">-->
				<!--					<div class="pum-field pum-field-select pum-field-select2">-->
				<!--						<label for="pum-first-trigger">--><?php // _e( 'Choose a type of trigger to get started.', 'popup-maker' ); ?><!--</label>-->
				<!--						<# print(PUM_Admin.triggers.template.selectbox({id: 'pum-first-trigger', name: "", placeholder: "--><?php // _e( 'Select a trigger type.', 'popup-maker' ); ?><!--"})); #>-->
				<!--					</div>-->
				<!--				</div>-->
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-trigger-row">
			<tr data-index="{{data.index}}">
				<td class="type-column">
					<button type="button" class="edit no-button link-button" aria-label="<?php esc_attr_e( 'Edit this trigger', 'popup-maker' ); ?>">{{PUM_Admin.triggers.getLabel(data.type)}}</button>
					<input class="popup_triggers_field_type" type="hidden" name="{{data.name}}[{{data.index}}][type]" value="{{data.type}}"/>
					<input class="popup_triggers_field_settings" type="hidden" name="{{data.name}}[{{data.index}}][settings]" value="{{JSON.stringify(data.settings)}}"/>
				</td>
				<td class="cookie-column">
					<code>{{PUM_Admin.triggers.cookie_column_value(data.settings.cookie_name)}}</code>
				</td>
				<td class="settings-column">{{{PUM_Admin.triggers.getSettingsDesc(data.type, data.settings)}}}</td>
				<td class="list-item-actions">
					<button type="button" class="edit dashicons dashicons-edit no-button" aria-label="<?php esc_attr_e( 'Edit this trigger', 'popup-maker' ); ?>"></button>
					<button type="button" class="remove dashicons dashicons-no no-button" aria-label="<?php esc_attr_e( 'Delete this trigger', 'popup-maker' ); ?>"></button>
				</td>
			</tr>
		</script>

		<?php
		$presets = apply_filters(
			'pum_click_selector_presets',
			[
				'a[href="exact_url"]'    => __( 'Link: Exact Match', 'popup-maker' ),
				'a[href*="contains"]'    => __( 'Link: Containing', 'popup-maker' ),
				'a[href^="begins_with"]' => __( 'Link: Begins With', 'popup-maker' ),
				'a[href$="ends_with"]'   => __( 'Link: Ends With', 'popup-maker' ),
			]
		);
		?>

		<script type="text/html" id="tmpl-pum-click-selector-presets">
			<div class="pum-click-selector-presets">
				<span class="dashicons dashicons-arrow-left" title="<?php esc_attr_e( 'Insert Preset', 'popup-maker' ); ?>"></span>
				<ul>
					<?php foreach ( $presets as $preset => $label ) : ?>
						<li data-preset='<?php echo esc_attr( $preset ); ?>'>
							<span><?php echo esc_html( $label ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-trigger-add-type">
			<#
			var form_args =
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo PUM_Utils_Array::safe_json_encode(
				[
					'id'     => 'pum-add-trigger',
					'fields' => [
						'popup_trigger_add_type'         => [
							'id'      => 'popup_trigger_add_type',
							'name'    => '',
							'label'   => esc_html__( 'What kind of trigger do you want?', 'popup-maker' ),
							'type'    => 'select',
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'options' => PUM_Triggers::instance()->dropdown_list(),
						],
						'popup_trigger_add_cookie'       => [
							'id'    => 'popup_trigger_add_cookie',
							'name'  => '',
							'type'  => 'checkbox',
							'std'   => true,
							'label' => esc_html__( 'Prevent popup from showing to visitor again using a cookie?', 'popup-maker' ),
							'meta'  => [ 'checked' => 'checked' ],
						],
						'popup_trigger_add_cookie_event' => [
							'id'           => 'popup_trigger_add_cookie_event',
							'name'         => '',
							'type'         => 'select',
							'label'        => esc_html__( 'Stop showing popup once visitor takes this action', 'popup-maker' ),
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'options'      => PUM_Cookies::instance()->dropdown_list(),
							'dependencies' => [
								'popup_trigger_add_cookie' => true,
							],
						],
						'popup_trigger_add_cookie_info'  => [
							'id'      => 'popup_trigger_add_cookie_info',
							'type'    => 'html',
							'content' => '<p>Learn more about <a href="https://wppopupmaker.com/docs/controlling-popups/popup-settings-box-cookies-option-settings/?utm_campaign=contextual-help&utm_medium=inline-doclink&utm_source=plugin-popup-editor&utm_content=new-trigger-cookie-info">how Popup Maker cookies work</a>.</p>',
						],
					],
				]
			);
			?>
			,
			content = PUM_Admin.forms.render(form_args, {});

			print(PUM_Admin.templates.modal({
				id: 'pum_trigger_add_type_modal',
				title: '<?php esc_attr_e( 'New Trigger', 'popup-maker' ); ?>',
				content: content,
				save_button: pum_admin_vars.I10n.add || '<?php esc_attr__( 'Add', 'popup-maker' ); ?>'
			}));
			#>
		</script>

		<?php
	}

	/**
	 *
	 */
	public static function conditions_editor() {
		?>
		<script type="text/html" id="tmpl-pum-field-conditions">
			<# print(PUM_Admin.conditions.template.editor({groups: data.value})); #>
		</script>

		<script type="text/html" id="tmpl-pum-condition-editor">
			<div class="facet-builder <# if (data.groups && data.groups.length) { print('has-conditions'); } #>">
				<p>
					<strong>
						<?php esc_html_e( 'Conditions allow you to show your popup to a targeted segment of your sites users.', 'popup-maker' ); ?>

						<?php
						printf(
							'%2$s<i class="dashicons dashicons-editor-help" title="%1$s"></i>%3$s',
							sprintf(
								/* translators: 1. contextual help link text. */
								esc_html__( 'Learn more about %s', 'popup-maker' ),
								esc_html__( 'Targeting Conditions', 'popup-maker' )
							),
							'<a href="https://wppopupmaker.com/docs/controlling-popups/popup-settings-box-targeting-option-settings/?utm_medium=inline-doclink&utm_campaign=contextual-help&utm_source=plugin-popup-editor&utm_content=targeting-option-settings" target="_blank">',
							'</a>'
						);
						?>
					</strong>
				</p>

				<ul class="ul-disc">
					<li><?php esc_html_e( 'By default, this popup will be loaded on every page of your site for all users.', 'popup-maker' ); ?></li>
					<li><?php esc_html_e( 'Target the popup to a specific segment by adding conditions below.', 'popup-maker' ); ?></li>
					<li>
					<?php
					printf(
						/* translators: 1. button text. 2. warning icon. */
						esc_html__( 'Click the %1$s button for any condition to check the opposite of the chosen condition. The button will turn red %2$s when active.', 'popup-maker' ),
						'<i style="font-size: 1em; width: 1em; height: 1em; line-height:1.5em;" class="dashicons dashicons-warning"></i>',
						'<i style="width: 1em; height: 1em; font-size: 1em; line-height:1.5em; color:#a00;" class="dashicons dashicons-warning"></i>'
					);
					?>
					</li>
				</ul>

				<section class="pum-alert-box" style="display:none"></section>
				<div class="facet-groups condition-groups">
					<#
					_.each(data.groups, function (group, group_ID) {
					print(PUM_Admin.conditions.template.group({
					index: group_ID,
					facets: group
					}));
					});
					#>
				</div>
				<div class="no-facet-groups">
					<label for="pum-first-condition"><?php esc_html_e( 'Choose a condition to target your popup to specific content or various other segments.', 'popup-maker' ); ?></label>
					<div class="facet-target">
						<button type="button" class="pum-not-operand dashicons-before dashicons-warning no-button" aria-label="<?php esc_attr_e( 'Enable the Not Operand', 'popup-maker' ); ?>">
							<input type="checkbox" id="pum-first-facet-operand" value="1"/>
						</button>
						<# print(PUM_Admin.conditions.template.selectbox({id: 'pum-first-condition', name: "", placeholder: "<?php esc_attr_e( 'Choose a condition', 'popup-maker' ); ?>"})); #>
					</div>
				</div>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-condition-group">

			<div class="facet-group-wrap" data-index="{{data.index}}">
				<section class="facet-group">
					<div class="facet-list">
						<# _.each(data.facets, function (facet) {
						print(PUM_Admin.conditions.template.facet(facet));
						}); #>
					</div>
					<div class="add-or">
						<button type="button" class="add add-facet no-button" aria-label="<?php echo esc_attr_x( 'Add another OR condition', 'aria-label for add new OR condition button', 'popup-maker' ); ?>"><?php esc_html_e( 'or', 'popup-maker' ); ?></button>
					</div>
				</section>
				<p class="and">
					<button type="button" class="add-facet no-button" aria-label="<?php echo esc_attr_x( 'Add another AND condition group', 'aria-label for add new AND condition button', 'popup-maker' ); ?>"><?php esc_html_e( 'and', 'popup-maker' ); ?></button>
				</p>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-condition-facet">
			<div class="facet" data-index="{{data.index}}" data-target="{{data.target}}">
				<i class="or"><?php esc_html_e( 'or', 'popup-maker' ); ?></i>
				<div class="facet-col facet-target <# if (typeof data.not_operand !== 'undefined' && data.not_operand == '1') print('not-operand-checked'); #>">
					<button type="button" class="pum-not-operand dashicons-before dashicons-warning no-button" aria-label="<?php esc_attr_e( 'Enable the Not Operand', 'popup-maker' ); ?>">
						<input type="checkbox" name="popup_settings[conditions][{{data.group}}][{{data.index}}][not_operand]" value="1"
						<# if (typeof data.not_operand !== 'undefined') print(PUM_Admin.utils.checked(data.not_operand, true, true)); #> />
					</button>
					<# print(PUM_Admin.conditions.template.selectbox({index: data.index, group: data.group, value: data.target, placeholder: "<?php esc_attr_e( 'Choose a condition', 'popup-maker' ); ?>"})); #>
				</div>

				<div class="facet-settings facet-col">
					<# print(PUM_Admin.conditions.template.settings(data, data.settings)); #>
				</div>

				<div class="facet-actions">
					<button type="button" class="remove remove-facet dashicons dashicons-dismiss no-button" aria-label="<?php esc_attr_e( 'Remove Condition', 'popup-maker' ); ?>"></button>
				</div>
			</div>
		</script>
		<?php
	}

	/**
	 *
	 */
	public static function cookies_editor() {
		?>
		<script type="text/html" id="tmpl-pum-field-cookies">
			<# print(PUM_Admin.cookies.template.editor({cookies: data.value, name: data.name})); #>
		</script>

		<script type="text/html" id="tmpl-pum-cookie-editor">
			<div class="pum-popup-cookie-editor  <# if (data.cookies && data.cookies.length) { print('has-list-items'); } #>" data-field_name="{{data.name}}">
				<button type="button" class="button button-primary pum-add-new no-button"><?php esc_html_e( 'Add New Cookie', 'popup-maker' ); ?></button>

				<p>
					<strong>
						<?php
						$title_text = sprintf(
							/* translators: 1. contextual help link text. */
							__( 'Learn more about %s', 'popup-maker' ),
							__( 'Cookies', 'popup-maker' )
						);
						?>
						<?php esc_html_e( 'Cookies control the repeat display of a popup.', 'popup-maker' ); ?>
						<a href="<?php echo esc_url( 'https://wppopupmaker.com/docs/controlling-popups/popup-settings-box-cookies-option-settings/?utm_campaign=contextual-help&utm_medium=inline-doclink&utm_source=plugin-popup-editor&utm_content=cookies-option-settings' ); ?>"
							target="_blank" class="pum-doclink dashicons dashicons-editor-help" title="<?php echo esc_attr( $title_text ); ?>"></a>
					</strong>
				</p>

				<table class="list-table form-table">
					<thead>
					<tr>
						<th><?php esc_html_e( 'Event', 'popup-maker' ); ?></th>
						<th><?php esc_html_e( 'Name', 'popup-maker' ); ?></th>
						<th><?php esc_html_e( 'Settings', 'popup-maker' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'popup-maker' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<#
					_.each(data.cookies, function (cookie, index) {
					print(PUM_Admin.cookies.template.row({
					index: index,
					event: cookie.event,
					name: data.name,
					settings: cookie.settings || {}
					}));
					});
					#>
					</tbody>
				</table>

				<!--				<div class="no-cookies  no-list-items">-->
				<!--					<div class="pum-field pum-field-select pum-field-select2">-->
				<!--						<label for="pum-first-cookie">--><?php // _e( 'Choose when you want to set a cookie to get started.', 'popup-maker' ); ?><!--</label>-->
				<!--						<# print(PUM_Admin.cookies.template.selectbox({id: 'pum-first-cookie', name: "", placeholder: "--><?php // _e( 'Select an event.', 'popup-maker' ); ?><!--"})); #>-->
				<!--					</div>-->
				<!--				</div>-->
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-cookie-row">
			<tr data-index="{{data.index}}">
				<td class="event-column">
					<button type="button" class="edit no-button link-button" aria-label="<?php esc_attr_e( 'Edit this cookie', 'popup-maker' ); ?>">{{PUM_Admin.cookies.getLabel(data.event)}}</button>
					<input class="popup_cookies_field_event" type="hidden" name="{{data.name}}[{{data.index}}][event]" value="{{data.event}}"/>
					<input class="popup_cookies_field_settings" type="hidden" name="{{data.name}}[{{data.index}}][settings]" value="{{JSON.stringify(data.settings)}}"/>
				</td>
				<td class="name-column">
					<code>{{data.settings.name}}</code>
				</td>
				<td class="settings-column">{{{PUM_Admin.cookies.getSettingsDesc(data.event, data.settings)}}}</td>
				<td class="list-item-actions">
					<button type="button" class="edit dashicons dashicons-edit no-button" aria-label="<?php esc_attr_e( 'Edit this cookie', 'popup-maker' ); ?>"></button>
					<button type="button" class="remove dashicons dashicons-no no-button" aria-label="<?php esc_attr_e( 'Delete this cookie', 'popup-maker' ); ?>"></button>
				</td>
			</tr>
		</script>

		<script type="text/html" id="tmpl-pum-cookie-add-event">
			<#
			print(PUM_Admin.templates.modal({
			id: 'pum_cookie_add_event_modal',
			title: '<?php esc_attr_e( 'When should your cookie be created?', 'popup-maker' ); ?>',
			content: PUM_Admin.cookies.template.selectbox({id: 'popup_cookie_add_event', name: "", placeholder: "<?php esc_attr_e( 'Select a cookie type.', 'popup-maker' ); ?>"}),
			save_button: pum_admin_vars.I10n.add || '<?php __( 'Add', 'popup-maker' ); ?>'
			}));
			#>
		</script>

		<script type="text/html" id="tmpl-pum-field-cookie_key">
			<div class="cookie-key">
				<button type="button" class="reset dashicons-before dashicons-image-rotate" title="<?php esc_attr_e( 'Reset Cookie Key', 'popup-maker' ); ?>"></button>
				<input type="text" placeholder="{{data.placeholder}}" class="{{data.size}}-text dashicons-before dashicons-image-rotate" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
			</div>
		</script>

		<?php
	}
}
