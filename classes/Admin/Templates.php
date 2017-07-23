<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class PUM_Admin_Templates {

	public static function init() {
		add_action( 'admin_footer', array( __CLASS__, 'fields' ) );
		add_action( 'admin_footer', array( __CLASS__, 'helpers' ) );
		add_action( 'admin_footer', array( __CLASS__, 'conditions_editor' ) );
	}

	public static function fields() {
		?>
		<script type="text/html" id="tmpl-pum-field-section">
			<div class="pum-field-section {{data.classes}}">
				<# _.each(data.fields, function(field) { #>
					{{{field}}}
				<# }); #>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-field-wrapper">
			<div class="pum-field pum-field-{{data.type}} {{data.id}}-wrapper {{data.classes}}" data-id="{{data.id}}" <?php /* data-field-args="{{JSON.stringify(data)}}" */ ?>
				<# print( data.dependencies !== '' ? "data-pum-dependencies='" + data.dependencies + "'" : ''); #>
				<# print( data.dynamic_desc !== '' ? "data-pum-dynamic-desc='" + data.dynamic_desc + "'" : ''); #>>
				<# if (typeof data.label === 'string' && data.label.length > 0) { #>
				<label for="{{data.id}}">{{{data.label}}}</label>
				<# } #>
				{{{data.field}}}
				<# if (typeof data.desc === 'string' && data.desc.length > 0) { #>
				<span class="pum-desc desc">{{{data.desc}}}</span>
				<# } #>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-field-heading">
			<h3 class="pum-field-heading">{{data.desc}}</h3>
		</script>

		<script type="text/html" id="tmpl-pum-field-separator">
			<# if (typeof data.desc === 'string' && data.desc.length > 0) { #>
			<h3 class="pum-field-heading">{{data.desc}}</h3>
			<# } #>
			<hr {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-text">
			<input type="text" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-link">
			<button type="button" class="dashicons dashicons-admin-generic button"></button>
			<input type="text" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-range">
			<input type="range" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-search">
			<input type="search" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-number">
			<input type="number" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-email">
			<input type="email" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-url">
			<input type="url" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-tel">
			<input type="tel" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-password">
			<input type="password" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-textarea">
			<textarea name="{{data.name}}" id="{{data.id}}" class="{{data.size}}-text" {{{data.meta}}}>{{data.value}}</textarea>
		</script>

		<script type="text/html" id="tmpl-pum-field-editor">
			<textarea name="{{data.name}}" id="{{data.id}}" class="pum-wpeditor {{data.size}}-text" {{{data.meta}}}>{{data.value}}</textarea>
		</script>

		<script type="text/html" id="tmpl-pum-field-hidden">
			<input type="hidden" class="{{data.classes}}" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-select">
			<select id="{{data.id}}" name="{{data.name}}" data-allow-clear="true" {{{data.meta}}}>
				<# _.each(data.options, function(option, key) {

					if (option.options !== undefined && option.options.length) { #>

						<optgroup label="{{{option.label}}}">

							<# _.each(option.options, function(option, key) { #>
								<option value="{{option.value}}" {{{option.meta}}}>{{{option.label}}}</option>
							<# }); #>

						</optgroup>

					<# } else { #>
						<option value="{{option.value}}" {{{option.meta}}}>{{{option.label}}}</option>
					<# }

				}); #>
			</select>
		</script>

		<script type="text/html" id="tmpl-pum-field-checkbox">
			<input type="checkbox" id="{{data.id}}" name="{{data.name}}" value="1" {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-multicheck">
			<ul class="pum-field-mulitcheck-list">
				<# _.each(data.options, function(option, key) { #>
					<li>
						<input type="checkbox" id="{{data.id}}_{{key}}" name="{{data.name}}[{{option.value}}]" value="1" {{{option.meta}}} />
						<label for="{{data.id}}_{{key}}">{{option.label}}</label>
					</li>
					<# }); #>
			</ul>
		</script>

		<script type="text/html" id="tmpl-pum-field-radio">
			<ul class="pum-field-radio-list">
				<# _.each(data.options, function(option, key) { #>
					<li <# print(option.value === data.value ? 'class="pum-selected"' : ''); #>>
						<input type="radio" id="{{data.id}}_{{key}}" name="{{data.name}}" value="{{option.value}}" {{{option.meta}}} />
						<label for="{{data.id}}_{{key}}">{{{option.label}}}</label>
					</li>
				<# }); #>
			</ul>
		</script>

		<script type="text/html" id="tmpl-pum-field-datetime">
			<div class="pum-datetime">
				<input placeholder="{{data.placeholder}}" data-input class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}} />
				<a class="input-button" data-toggle><i class="dashicons dashicons-calendar-alt"></i></a>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-field-datetimerange">
			<div class="pum-datetime-range">
				<input placeholder="{{data.placeholder}}" data-input class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}} />
				<a class="input-button" data-toggle><i class="dashicons dashicons-calendar-alt"></i></a>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-field-rangeslider">
			<input type="text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" class="pum-range-manual" {{{data.meta}}} />
			<span class="pum-range-value-unit regular-text">{{data.unit}}</span>
		</script>

		<script type="text/html" id="tmpl-pum-field-color">
			<input type="text" class="pum-color-picker color-picker" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" data-default-color="{{data.std}}" {{{data.meta}}} />
		</script>

		<script type="text/html" id="tmpl-pum-field-measure">
			<input type="number" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" size="5"  {{{data.meta}}} />
			<select id="{{data.id}}_unit" name="<# print(data.name.replace(data.id, data.id + '_unit')); #>">
				<# _.each(data.units, function(option, key) { #>
					<option value="{{option.value}}" {{{option.meta}}}>{{{option.label}}}</option>
				<# }); #>
			</select>
		</script>

		<?php
	}

	public static function helpers() {
		?>
		<script type="text/html" id="tmpl-pum-modal">
			<div id="{{data.id}}" class="pum-modal-background {{data.classes}}" role="dialog" aria-hidden="true" aria-labelledby="{{data.id}}-title" aria-describedby="{{data.id}}-description" {{{data.meta}}}>

				<div class="pum-modal-wrap">

					<form class="pum-form">

						<div class="pum-modal-header">

							<# if (data.title.length) { #>
								<span id="{{data.id}}-title" class="pum-modal-title">{{data.title}}</span>
								<# } #>

									<button type="button" class="pum-modal-close" aria-label="<?php _e( 'Close', 'popup-maker' ); ?>"></button>

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

	public static function conditions_editor() {
		?>
		<script type="text/html" id="tmpl-pum-field-conditions">
			<# print(PUM_Admin.conditions.template.editor({groups: data.value})); #>
		</script>

		<script type="text/html" id="tmpl-pum-condition-editor">
			<div class="facet-builder <# if (data.groups && data.groups.length) { print('has-conditions'); } #>">
				<p>
					<strong>
						<?php _e( 'These conditions determine when this popup will be display.', 'popup-maker' ); ?><?php printf( '%2$s<i class="dashicons dashicons-editor-help" title="%1$s"></i>%3$s', __( 'Learn more about conditions', 'popup-maker' ), '<a href="http://docs.pum-messaging.com/article/140-conditions" target="_blank">', '</a>' ); ?>
					</strong>
				</p>

				<p><?php _e( 'When users visit your site, the plugin will check the viewed content against your selection below and determine if this popup should be shown.', 'popup-maker' ); ?></p>

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
					<label for="pum-first-condition"><?php _e( 'Choose a condition to get started.', 'popup-maker' ); ?></label>
					<div class="pum-field select pumselect2 pum-facet-target">
						<button type="button" class="pum-not-operand" aria-label="<?php _e( 'Enable the Not Operand', 'popup-maker' ); ?>">
							<span class="is"><?php _e( 'Is', 'popup-maker' ); ?></span>
							<span class="not"><?php _e( 'Is Not', 'popup-maker' ); ?></span>
							<input type="checkbox" id="pum-first-facet-operand" value="1" />
						</button>
						<# print(PUM_Admin.conditions.template.selectbox({id: 'pum-first-condition', name: "", placeholder: "<?php _e( 'Choose a condition to get started.', 'popup-maker' ); ?>"})); #>
					</div>
				</div>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-condition-group">

			<div class="facet-group-wrap" data-index="{{data.index}}">
				<section class="facet-group">
					<div class="facet-list">
						<#
							_.each(data.facets, function (facet) {
							print(PUM_Admin.conditions.template.facet(facet));
							});
							#>
					</div>
					<div class="add-or">
						<button type="button" class="add add-facet no-button" aria-label="<?php _ex( 'Add another OR condition', 'aria-label for add new OR condition button', 'popup-maker' ); ?>"><?php _e( 'or', 'popup-maker' ); ?></button>
					</div>
				</section>
				<p class="and">
					<button type="button" class="add-facet no-button" aria-label="<?php _ex( 'Add another AND condition group', 'aria-label for add new AND condition button', 'popup-maker' ); ?>"><?php _e( 'and', 'popup-maker' ); ?></button>
				</p>
			</div>
		</script>

		<script type="text/html" id="tmpl-pum-condition-facet">
			<div class="facet" data-index="{{data.index}}" data-target="{{data.target}}">
				<i class="or"><?php _e( 'or', 'popup-maker' ); ?></i>
				<div class="facet-col pum-field pum-facet-target select pumselect2 <# if (typeof data.not_operand !== 'undefined' && data.not_operand == '1') print('not-operand-checked'); #>">
					<button type="button" class="pum-not-operand" aria-label="<?php _e( 'Enable the Not Operand', 'popup-maker' ); ?>">
						<span class="is"><?php _e( 'Is', 'popup-maker' ); ?></span>
						<span class="not"><?php _e( 'Is Not', 'popup-maker' ); ?></span>
						<input type="checkbox" name="popup_meta[conditions][{{data.group}}][{{data.index}}][not_operand]" value="1"
						<# if (typeof data.not_operand !== 'undefined') print(PUM_Admin.utils.checked(data.not_operand, true, true)); #> />
					</button>
					<# print(PUM_Admin.conditions.template.selectbox({index: data.index, group: data.group, value: data.target, placeholder: "<?php _e( 'Choose a condition', 'popup-maker' ); ?>"})); #>
				</div>

				<div class="facet-settings facet-col">
					<#
						print(PUM_Admin.conditions.template.settings(data, data.settings));
						#>
				</div>

				<div class="facet-actions">
					<button type="button" class="remove remove-facet dashicons dashicons-dismiss no-button" aria-label="<?php _e( 'Remove Condition', 'popup-maker' ); ?>"></button>
				</div>
			</div>
		</script>
		<?php
	}

}
