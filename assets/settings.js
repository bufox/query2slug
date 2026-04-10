/* global jQuery, q2sSettings */
(function ($) {
	'use strict';

	var checkTimer = null;

	$('#q2s_prefix').on('input', function () {
		var prefix = $(this).val().replace(/[^a-z0-9-]/g, '').toLowerCase();
		$(this).val(prefix);

		// Update URL preview.
		$('#q2s-prefix-preview').text(prefix || '...');

		clearTimeout(checkTimer);
		if (prefix.length > 0) {
			$('#q2s-prefix-status')
				.text(q2sSettings.strings.checking)
				.attr('class', 'q2s-prefix-status q2s-checking');

			checkTimer = setTimeout(function () {
				checkPrefix(prefix);
			}, 400);
		} else {
			$('#q2s-prefix-status').text('').attr('class', 'q2s-prefix-status');
		}
	});

	function checkPrefix(prefix) {
		$.post(q2sSettings.ajaxUrl, {
			action: 'q2s_check_prefix',
			_ajax_nonce: q2sSettings.nonce,
			prefix: prefix
		}, function (response) {
			if (response.success) {
				$('#q2s-prefix-status')
					.text(q2sSettings.strings.ok)
					.attr('class', 'q2s-prefix-status q2s-prefix-ok');
			} else {
				$('#q2s-prefix-status')
					.text(response.data)
					.attr('class', 'q2s-prefix-status q2s-prefix-warning');
			}
		});
	}

})(jQuery);
