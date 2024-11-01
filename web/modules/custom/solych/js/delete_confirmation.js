(function (Drupal, $) {
  Drupal.behaviors.deleteConfirmationDialog = {
    attach: function (context, settings) {
      $('.delete-confirm-yes', context).on('click', function () {
        $.ajax({
          url: settings.solych.deleteConfirmUrl,
          type: 'POST',
          data: { id: settings.solych.deleteId },
          success: function () {
            $('.ui-dialog-content').dialog('close');
            location.reload();
          }
        });
      });

      $('.delete-confirm-no', context).on('click', function () {
        $('.ui-dialog-content').dialog('close');
      });
    }
  };
})(Drupal, jQuery);
