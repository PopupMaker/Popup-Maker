<script type="text/template" id="tmpl-pum-modal">
	<div id="<%= id %>" class="pum-modal-background <%= classes %>" role="dialog" aria-hidden="true" aria-labelledby="<%= id %>-title" aria-describedby="<%= id %>-description" <%= meta %>>

		<div class="pum-modal-wrap">

			<form class="pum-form">

				<div class="pum-modal-header">

					<% if (title.length) { %>
					<span id="<%= id %>-title" class="pum-modal-title"><%= title %></span>
					<% } %>

					<button type="button" class="pum-modal-close" aria-label="<?php _e( 'Close', 'popup-maker' ); ?>"></button>

				</div>

				<% if (description.length) { %>
				<span id="<%= id %>-description" class="screen-reader-text"><%= description %></span>
				<% } %>

				<div class="pum-modal-content">
					<%= content %>
				</div>

				<% if (save_button || cancel_button) { %>

				<div class="pum-modal-footer submitbox">

					<% if (cancel_button) { %>
					<div class="cancel">
						<button type="button" class="submitdelete no-button" href="#"><%= cancel_button %></button>
					</div>
					<% } %>

					<% if (save_button) { %>
					<div class="pum-submit">
						<span class="spinner"></span>
						<button class="button button-primary"><%= save_button %></button>
					</div>
					<% } %>

				</div>

				<% } %>

			</form>

		</div>

	</div>
</script>

<script type="text/template" id="tmpl-pum-tabs">
	<div class="pum-tabs-container <%= classes %>" <%= meta %>>

		<ul class="tabs">
			<% _.each(tabs, function(tab, key) { %>
				<li class="tab">
					<a href="#<% print(id + '_' + key); %>"><%= tab.label %></a>
				</li>
			<% }); %>
		</ul>

		<% _.each(tabs, function(tab, key) { %>
			<div id="<% print(id + '_' + key); %>" class="tab-content">
				<%= tab.content %>
			</div>
		<% }); %>

	</div>
</script>

<script type="text/template" id="tmpl-pum-shortcode">
	[<%= tag %> <%= meta %>]
</script>

<script type="text/template" id="tmpl-pum-shortcode-w-content">
	[<%= tag %> <%= meta %>]<%= content %>[/<%= tag %>]
</script>