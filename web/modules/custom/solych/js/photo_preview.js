(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.photoPreview = {
    attach: function (context, settings) {
      $('.form-item--photo', context).each(function () {
        const fileInput = $(this).find('input[type="file"]')[0];
        const previewContainer = $(this).closest('form').find('#photo-preview');
        const maxFileSize = 2 * 1024 * 1024; // 2 MB

        const loadPreview = function (src) {
          previewContainer.html('<img src="' + src + '" alt="Photo preview" style="max-width: 200px; max-height: 200px;">');
        };

        if (!previewContainer.data('file-changed') && drupalSettings.solych && drupalSettings.solych.photoPreviewUrl) {
          loadPreview(drupalSettings.solych.photoPreviewUrl);
        }

        $(fileInput).on('change', function (e) {
          previewContainer.data('file-changed', true);
          previewContainer.empty();
          if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];
            if (file.size <= maxFileSize && ['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
              const reader = new FileReader();
              reader.onload = function (e) {
                loadPreview(e.target.result);
              };
              reader.readAsDataURL(file);
            } else {
              previewContainer.html('<p>' + Drupal.t('Unable to load preview. Maybe you have some warnings.') + '</p>');
            }
          }
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
