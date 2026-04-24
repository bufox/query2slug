/* global jQuery, q2slugList */
(function ($) {
	'use strict';

	// Confirm before deleting a rule.
	$(document).on('click', '.q2slug-delete', function (e) {
		if (!confirm(q2slugList.strings.confirmDelete)) {
			e.preventDefault();
		}
	});

	// Toggle rule status via AJAX.
	$(document).on('click', '.q2slug-toggle-status', function () {
		var $btn   = $(this);
		var ruleId = $btn.data('rule-id');
		var $icon  = $btn.find('.dashicons');

		$.post(q2slugList.ajaxUrl, {
			action: 'q2slug_toggle_status',
			_ajax_nonce: q2slugList.nonce,
			rule_id: ruleId
		}, function (response) {
			if (response.success) {
				if (response.data.status === 1) {
					$icon.removeClass('dashicons-marker q2slug-status-inactive')
						.addClass('dashicons-yes-alt q2slug-status-active');
				} else {
					$icon.removeClass('dashicons-yes-alt q2slug-status-active')
						.addClass('dashicons-marker q2slug-status-inactive');
				}
			}
		});
	});


})(jQuery);
