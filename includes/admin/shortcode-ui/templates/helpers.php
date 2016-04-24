<script type="text/html" id="tmpl-pum-modal">
	<div id="{{data.id}}" class="pum-modal-background {{data.classes}}" role="dialog" aria-hidden="true" aria-labelledby="{{data.id}}-title" aria-describedby="{{data.id}}-description" {{data.meta}}>

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
	<div class="pum-tabs-container {{data.classes}}" {{data.meta}}>

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