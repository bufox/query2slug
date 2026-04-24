/* global jQuery, q2slugSettings */
(function ($) {
	'use strict';

	var checkTimer = null;

	$('#q2slug_prefix').on('input', function () {
		var prefix = $(this).val().replace(/[^a-z0-9-]/g, '').toLowerCase();
		$(this).val(prefix);

		// Update URL preview.
		$('#q2slug-prefix-preview').text(prefix || '...');

		clearTimeout(checkTimer);
		if (prefix.length > 0) {
			$('#q2slug-prefix-status')
				.text(q2slugSettings.strings.checking)
				.attr('class', 'q2slug-prefix-status q2slug-checking');

			checkTimer = setTimeout(function () {
				checkPrefix(prefix);
			}, 400);
		} else {
			$('#q2slug-prefix-status').text('').attr('class', 'q2slug-prefix-status');
		}
	});

	function checkPrefix(prefix) {
		$.post(q2slugSettings.ajaxUrl, {
			action: 'q2slug_check_prefix',
			_ajax_nonce: q2slugSettings.nonce,
			prefix: prefix
		}, function (response) {
			if (response.success) {
				$('#q2slug-prefix-status')
					.text(q2slugSettings.strings.ok)
					.attr('class', 'q2slug-prefix-status q2slug-prefix-ok');
			} else {
				$('#q2slug-prefix-status')
					.text(response.data)
					.attr('class', 'q2slug-prefix-status q2slug-prefix-warning');
			}
		});
	}

})(jQuery);
