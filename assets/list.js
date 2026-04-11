/* global jQuery, q2sList */
(function ($) {
	'use strict';

	// Confirm before deleting a rule.
	$(document).on('click', '.q2s-delete', function (e) {
		if (!confirm(q2sList.strings.confirmDelete)) {
			e.preventDefault();
		}
	});

	// Toggle rule status via AJAX.
	$(document).on('click', '.q2s-toggle-status', function () {
		var $btn   = $(this);
		var ruleId = $btn.data('rule-id');
		var $icon  = $btn.find('.dashicons');

		$.post(q2sList.ajaxUrl, {
			action: 'q2s_toggle_status',
			_ajax_nonce: q2sList.nonce,
			rule_id: ruleId
		}, function (response) {
			if (response.success) {
				if (response.data.status === 1) {
					$icon.removeClass('dashicons-marker q2s-status-inactive')
						.addClass('dashicons-yes-alt q2s-status-active');
				} else {
					$icon.removeClass('dashicons-yes-alt q2s-status-active')
						.addClass('dashicons-marker q2s-status-inactive');
				}
			}
		});
	});


})(jQuery);
