<script type="text/template" id="tmpl-pum-field-section">
	<div class="pum-field-section <%= classes %>">
		<% _.each(fields, function(field) { %>
			<%= field %>
		<% }); %>
	</div>
</script>

<script type="text/template" id="tmpl-pum-field-wrapper">
	<div class="pum-field pum-field-<%= type %> <%= id %>-wrapper <%= classes %>">
		<label for="<%= id %>"><%= label %></label>
		<%= field %>
		<% if (desc) { %>
		<p class="pum-desc desc"><%= desc %></p>
		<% } %>
	</div>
</script>

<script type="text/template" id="tmpl-pum-field-heading">
	<h3 class="pum-field-heading"><%= desc %></h3>
</script>

<script type="text/template" id="tmpl-pum-field-text">
	<input type="text" placeholder="<%= placeholder %>" class="<%= size %>-text" id="<%= id %>" name="<%= name %>" value="<%= value %>" <%= meta %>/>
</script>

<script type="text/template" id="tmpl-pum-field-range">
	<input type="range" placeholder="<%= placeholder %>" class="<%= size %>-text" id="<%= id %>" name="<%= name %>" value="<%= value %>" <%= meta %>/>
</script>

<script type="text/template" id="tmpl-pum-field-search">
	<input type="search" placeholder="<%= placeholder %>" class="<%= size %>-text" id="<%= id %>" name="<%= name %>" value="<%= value %>" <%= meta %>/>
</script>

<script type="text/template" id="tmpl-pum-field-number">
	<input type="number" placeholder="<%= placeholder %>" class="<%= size %>-text" id="<%= id %>" name="<%= name %>" value="<%= value %>" <%= meta %>/>
</script>

<script type="text/template" id="tmpl-pum-field-email">
	<input type="email" placeholder="<%= placeholder %>" class="<%= size %>-text" id="<%= id %>" name="<%= name %>" value="<%= value %>" <%= meta %>/>
</script>

<script type="text/template" id="tmpl-pum-field-url">
	<input type="url" placeholder="<%= placeholder %>" class="<%= size %>-text" id="<%= id %>" name="<%= name %>" value="<%= value %>" <%= meta %>/>
</script>

<script type="text/template" id="tmpl-pum-field-tel">
	<input type="tel" placeholder="<%= placeholder %>" class="<%= size %>-text" id="<%= id %>" name="<%= name %>" value="<%= value %>" <%= meta %>/>
</script>

<script type="text/template" id="tmpl-pum-field-password">
	<input type="password" placeholder="<%= placeholder %>" class="<%= size %>-text" id="<%= id %>" name="<%= name %>" value="<%= value %>" <%= meta %>/>
</script>

<script type="text/template" id="tmpl-pum-field-textarea">
	<textarea name="<%= name %>" id="<%= id %>" class="<%= size %>-text" <%= meta %>><%= value %></textarea>
</script>

<script type="text/template" id="tmpl-pum-field-hidden">
	<input type="hidden" class="<%= classes %>" id="<%= id %>" name="<%= name %>" value="<%= value %>" <%= meta %>/>
</script>

<script type="text/template" id="tmpl-pum-field-select">
    <select id="<%= id %>" name="<%= name %>" data-allow-clear="true" <%= meta %>>
		<% _.each(options, function(option, key) { %>
		<option value="<%= option.value %>" <%= option.meta %>><%= option.label %></option>
		<% }); %>
	</select>
</script>

<script type="text/template" id="tmpl-pum-field-checkbox">
	<input type="checkbox" id="<%= id %>" name="<%= name %>" value="1" <%= meta %>/>
</script>

<script type="text/template" id="tmpl-pum-field-multicheck">
	<ul class="pum-field-mulitcheck-list">
		<% _.each(options, function(option, key) {
		<li>
			<input type="checkbox" id="<%= id %>_<%= key %>" name="<%= name %>[<%= option.value %>]" value="1" <%= option.meta %>/>
			<label for="<%= id %>_<%= key %>"><%= option.label %></label>
		</li>
		<% }); %>
	</ul>
</script>

<script type="text/template" id="tmpl-pum-field-rangeslider">
	<input type="text" id="<%= id %>" name="<%= name %>" value="<%= value %>" class="popmake-range-manual pum-range-manual" <%= meta %>/>
	<span class="range-value-unit regular-text"><%= unit %></span>
</script>

<script type="text/template" id="tmpl-pum-field-">

</script>
