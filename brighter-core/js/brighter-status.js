jQuery(function ($) {
  // Cycle to next status in the configured list
  function nextStatus(current, cycle) {
    const idx = cycle.indexOf(current);
    return cycle[(idx + 1) % cycle.length];
  }

  $(document).on('click', '.brighter-status-badge', function () {
    const $btn   = $(this);
    const postId = parseInt($btn.data('postid'), 10);
    const cycle  = BRIGHTER_STATUS.cycle || ['','done','opt90','opt80','opt70','improve','leave','consolidate','repurpose'];

    // compute next value and show a quick visual "saving..." state
    const current = String($btn.data('value') || '');
    const value   = nextStatus(current, cycle);

    $btn.prop('disabled', true).addClass('is-saving');

    $.post(BRIGHTER_STATUS.ajax_url, {
      action: 'brighter_save_status',
      nonce: BRIGHTER_STATUS.nonce,
      post_id: postId,
      value: value
    }).done(function (resp) {
      if (!resp || !resp.success) {
        console.warn('Save failed:', resp);
        alert((resp && resp.data && resp.data.message) || 'Save failed.');
        return;
      }

      // Update button text + class + data-value
      const label = resp.data && resp.data.label ? resp.data.label : value;

      // Remove old bg-* class
      $btn.removeClass (function (i, c) {
        return (c.match (/(^|\s)bg-\S+/g) || []).join(' ');
      });

      // Map of value -> class (keep in sync with PHP if you change)
      const classMap = {
        ''            : 'bg-gray',
        'done'        : 'bg-green',
        'opt90'       : 'bg-emerald',
        'opt80'       : 'bg-teal',
        'opt70'       : 'bg-cyan',
        'improve'     : 'bg-orange',
        'leave'       : 'bg-slate',
        'consolidate' : 'bg-purple',
        'repurpose'   : 'bg-blue'
      };

      $btn
        .text(label)
        .addClass(classMap[value] || 'bg-gray')
        .data('value', value);

    }).fail(function (xhr) {
      console.error('AJAX error', xhr && xhr.responseText);
      alert('Network / permission error saving status.');
    }).always(function () {
      $btn.prop('disabled', false).removeClass('is-saving');
    });
  });
});
