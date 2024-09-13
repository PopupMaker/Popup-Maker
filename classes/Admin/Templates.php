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
			<input type="checkbox" id="{{data.id}}" name="{{data.name}}" value="1" {{{data.meta}}}/>
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
			<input class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value.key}}" autocomplete="off" {{{data.meta}}}/>

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
						<a href="<?php echo esc_url( 'https://docs.wppopupmaker.com/article/141-triggers?utm_campaign=contextual-help&utm_medium=inline-doclink&utm_source=plugin-popup-editor&utm_content=triggers-option-settings' ); ?>" target="_blank"
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
							'content' => '<p>Learn more about <a href="https://docs.wppopupmaker.com/article/358-popup-settings-box-cookies-option-settings?utm_campaign=contextual-help&utm_medium=inline-doclink&utm_source=plugin-popup-editor&utm_content=new-trigger-cookie-info">how Popup Maker cookies work</a>.</p>',
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
							'<a href="https://docs.wppopupmaker.com/article/351-popup-settings-box-targeting-option-settings?utm_medium=inline-doclink&utm_campaign=contextual-help&utm_source=plugin-popup-editor&utm_content=targeting-option-settings" target="_blank">',
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
						<a href="<?php echo esc_url( 'https://docs.wppopupmaker.com/article/358-popup-settings-box-cookies-option-settings?utm_campaign=contextual-help&utm_medium=inline-doclink&utm_source=plugin-popup-editor&utm_content=cookies-option-settings' ); ?>"
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
