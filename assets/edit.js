/* global jQuery, q2slugEdit */
(function ($) {
	'use strict';

	var slugCheckTimer = null;

	// --- Slug field: live preview + collision check ---

	$('#q2slug_slug').on('input', function () {
		var raw = $(this).val();
		// Mimic sanitize_title: lowercase, keep only a-z, 0-9, hyphens.
		var slug = raw.toLowerCase().replace(/[^a-z0-9-]/g, '').replace(/-+/g, '-');
		if (raw !== slug) {
			$(this).val(slug);
		}
		$('#q2slug-slug-preview').text(slug || '...');

		// Show feedback if input was modified by sanitization.
		if (raw !== slug && slug.length > 0) {
			$('#q2slug-slug-sanitized').text(q2slugEdit.strings.sanitized).removeClass('q2slug-slug-sanitized-hidden');
		} else {
			$('#q2slug-slug-sanitized').addClass('q2slug-slug-sanitized-hidden');
		}

		clearTimeout(slugCheckTimer);
		if (slug.length > 0) {
			$('#q2slug-slug-status')
				.text(q2slugEdit.strings.checking)
				.attr('class', 'q2slug-checking');

			slugCheckTimer = setTimeout(function () {
				checkSlugAvailability(slug);
			}, 400);
		} else {
			$('#q2slug-slug-status').text('').attr('class', '');
		}
	});

	function checkSlugAvailability(slug) {
		$.post(q2slugEdit.ajaxUrl, {
			action: 'q2slug_check_slug',
			_ajax_nonce: q2slugEdit.nonce,
			slug: slug,
			rule_id: $('input[name="q2slug_rule_id"]').val()
		}, function (response) {
			if (response.success) {
				$('#q2slug-slug-status')
					.text(q2slugEdit.strings.available)
					.attr('class', 'q2slug-available');
			} else {
				$('#q2slug-slug-status')
					.text(q2slugEdit.strings.taken)
					.attr('class', 'q2slug-taken');
			}
		});
	}

	// --- Filter rows: add / remove ---

	$('#q2slug-add-filter').on('click', function () {
		var row = '<div class="q2slug-filter-row">' +
			'<input type="text" name="q2slug_filter_key[]" placeholder="Parameter (e.g. product_cat)" class="regular-text q2slug-filter-key">' +
			'<span class="q2slug-filter-eq">=</span>' +
			'<input type="text" name="q2slug_filter_value[]" placeholder="Value (e.g. t-shirt)" class="regular-text q2slug-filter-value">' +
			'<button type="button" class="button q2slug-remove-filter" title="Remove">&minus;</button>' +
			'</div>';
		$('#q2slug-filters-container').append(row);
		initAutocomplete($('#q2slug-filters-container .q2slug-filter-row:last'));
	});

	$(document).on('click', '.q2slug-remove-filter', function () {
		var container = $('#q2slug-filters-container');
		if (container.find('.q2slug-filter-row').length > 1) {
			$(this).closest('.q2slug-filter-row').remove();
		}
	});

	// --- Autocomplete for filter keys (taxonomies) and values (terms) ---

	function initAutocomplete($row) {
		$row.find('.q2slug-filter-key').autocomplete({
			source: function (request, response) {
				$.post(q2slugEdit.ajaxUrl, {
					action: 'q2slug_get_taxonomies',
					_ajax_nonce: q2slugEdit.nonce,
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

		$row.find('.q2slug-filter-value').autocomplete({
			source: function (request, response) {
				var taxonomy = $row.find('.q2slug-filter-key').val();
				if (!taxonomy) {
					response([]);
					return;
				}
				$.post(q2slugEdit.ajaxUrl, {
					action: 'q2slug_get_terms',
					_ajax_nonce: q2slugEdit.nonce,
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
	$('#q2slug-filters-container .q2slug-filter-row').each(function () {
		initAutocomplete($(this));
	});

})(jQuery);
