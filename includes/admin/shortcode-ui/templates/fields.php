<script type="text/html" id="tmpl-pum-field-section">
	<div class="pum-field-section {{data.classes}}">
		<# _.each(data.fields, function(field) { #>
			{{{field}}}
		<# }); #>
	</div>
</script>

<script type="text/html" id="tmpl-pum-field-wrapper">
	<div class="pum-field pum-field-{{data.type}} {{data.id}}-wrapper {{data.classes}}">
		<label for="{{data.id}}">{{data.label}}</label>
		{{{data.field}}}
		<# if (data.desc) { #>
		<p class="pum-desc desc">{{data.desc}}</p>
		<# } #>
	</div>
</script>

<script type="text/html" id="tmpl-pum-field-heading">
	<h3 class="pum-field-heading">{{data.desc}}</h3>
</script>

<script type="text/html" id="tmpl-pum-field-text">
	<input type="text" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
</script>

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

<script type="text/html" id="tmpl-pum-field-password">
	<input type="password" placeholder="{{data.placeholder}}" class="{{data.size}}-text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
</script>

<script type="text/html" id="tmpl-pum-field-textarea">
	<textarea name="{{data.name}}" id="{{data.id}}" class="{{data.size}}-text" {{{data.meta}}}>{{data.value}}</textarea>
</script>

<script type="text/html" id="tmpl-pum-field-hidden">
	<input type="hidden" class="{{data.classes}}" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" {{{data.meta}}}/>
</script>

<script type="text/html" id="tmpl-pum-field-select">
    <select id="{{data.id}}" name="{{data.name}}" data-allow-clear="true" {{{data.meta}}}>
		<# _.each(data.options, function(option, key) { #>
		<option value="{{option.value}}" {{{option.meta}}}>{{option.label}}</option>
		<# }); #>
	</select>
</script>

<script type="text/html" id="tmpl-pum-field-checkbox">
	<input type="checkbox" id="{{data.id}}" name="{{data.name}}" value="1" {{{data.meta}}}/>
</script>

<script type="text/html" id="tmpl-pum-field-multicheck">
	<ul class="pum-field-mulitcheck-list">
		<# _.each(data.options, function(option, key) { #>
		<li>
			<input type="checkbox" id="{{data.id}}_{{key}}" name="{{data.name}}[{{option.value}}]" value="1" {{{option.meta}}}/>
			<label for="{{data.id}}_{{key}}">{{option.label}}</label>
		</li>
		<# }); #>
	</ul>
</script>

<script type="text/html" id="tmpl-pum-field-rangeslider">
	<input type="text" id="{{data.id}}" name="{{data.name}}" value="{{data.value}}" class="popmake-range-manual pum-range-manual" {{{data.meta}}}/>
	<span class="range-value-unit regular-text">{{data.unit}}</span>
</script>