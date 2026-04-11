/* global jQuery, q2sEdit */
(function ($) {
	'use strict';

	var slugCheckTimer = null;

	// --- Slug field: live preview + collision check ---

	$('#q2s_slug').on('input', function () {
		var raw = $(this).val();
		// Mimic sanitize_title: lowercase, keep only a-z, 0-9, hyphens.
		var slug = raw.toLowerCase().replace(/[^a-z0-9-]/g, '').replace(/-+/g, '-');
		if (raw !== slug) {
			$(this).val(slug);
		}
		$('#q2s-slug-preview').text(slug || '...');

		// Show feedback if input was modified by sanitization.
		if (raw !== slug && slug.length > 0) {
			$('#q2s-slug-sanitized').text(q2sEdit.strings.sanitized).removeClass('q2s-slug-sanitized-hidden');
		} else {
			$('#q2s-slug-sanitized').addClass('q2s-slug-sanitized-hidden');
		}

		clearTimeout(slugCheckTimer);
		if (slug.length > 0) {
			$('#q2s-slug-status')
				.text(q2sEdit.strings.checking)
				.attr('class', 'q2s-checking');

			slugCheckTimer = setTimeout(function () {
				checkSlugAvailability(slug);
			}, 400);
		} else {
			$('#q2s-slug-status').text('').attr('class', '');
		}
	});

	function checkSlugAvailability(slug) {
		$.post(q2sEdit.ajaxUrl, {
			action: 'q2s_check_slug',
			_ajax_nonce: q2sEdit.nonce,
			slug: slug,
			rule_id: $('input[name="q2s_rule_id"]').val()
		}, function (response) {
			if (response.success) {
				$('#q2s-slug-status')
					.text(q2sEdit.strings.available)
					.attr('class', 'q2s-available');
			} else {
				$('#q2s-slug-status')
					.text(q2sEdit.strings.taken)
					.attr('class', 'q2s-taken');
			}
		});
	}

	// --- Filter rows: add / remove ---

	$('#q2s-add-filter').on('click', function () {
		var row = '<div class="q2s-filter-row">' +
			'<input type="text" name="q2s_filter_key[]" placeholder="Parameter (e.g. product_cat)" class="regular-text q2s-filter-key">' +
			'<span class="q2s-filter-eq">=</span>' +
			'<input type="text" name="q2s_filter_value[]" placeholder="Value (e.g. t-shirt)" class="regular-text q2s-filter-value">' +
			'<button type="button" class="button q2s-remove-filter" title="Remove">&minus;</button>' +
			'</div>';
		$('#q2s-filters-container').append(row);
		initAutocomplete($('#q2s-filters-container .q2s-filter-row:last'));
	});

	$(document).on('click', '.q2s-remove-filter', function () {
		var container = $('#q2s-filters-container');
		if (container.find('.q2s-filter-row').length > 1) {
			$(this).closest('.q2s-filter-row').remove();
		}
	});

	// --- Autocomplete for filter keys (taxonomies) and values (terms) ---

	function initAutocomplete($row) {
		$row.find('.q2s-filter-key').autocomplete({
			source: function (request, response) {
				$.post(q2sEdit.ajaxUrl, {
					action: 'q2s_get_taxonomies',
					_ajax_nonce: q2sEdit.nonce,
					term: request.term
				}, function (data) {
					response(data);
				});
			},
			minLength: 1,
			select: function (event, ui) {
				$(this).val(ui.item.value);
				return false;
			}
		});

		$row.find('.q2s-filter-value').autocomplete({
			source: function (request, response) {
				var taxonomy = $row.find('.q2s-filter-key').val();
				if (!taxonomy) {
					response([]);
					return;
				}
				$.post(q2sEdit.ajaxUrl, {
					action: 'q2s_get_terms',
					_ajax_nonce: q2sEdit.nonce,
					taxonomy: taxonomy,
					term: request.term
				}, function (data) {
					response(data);
				});
			},
			minLength: 1,
			select: function (event, ui) {
				$(this).val(ui.item.value);
				return false;
			}
		});
	}

	// Init autocomplete on existing rows.
	$('#q2s-filters-container .q2s-filter-row').each(function () {
		initAutocomplete($(this));
	});

})(jQuery);
