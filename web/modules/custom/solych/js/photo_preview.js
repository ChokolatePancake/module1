(function ($, Drupal) {
  Drupal.behaviors.photoProview = {
    attach: function (context, settings) {
      $('.form-item--photo', context).on('change', function (e) {
        const fileInput = e.target;
        const previewContainer = $('#photo-preview');
        const maxFileSize = 2 * 1024 * 1024;

        previewContainer.empty();

        if (fileInput.files && fileInput.files[0]) {
          const file = fileInput.files[0];
          console.log(file.size);
          console.log(file.size>maxFileSize);
          if (file.size <= maxFileSize && ['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
            const reader = new FileReader();

            reader.onload = function (e) {
            previewContainer.html('<img src="' + e.target.result + '" alt="Photo preview" style="max-width: 200px; max-height: 200px;">');
            };

            reader.readAsDataURL(file);
          } else {
            previewContainer.html('<p>' + Drupal.t('Unable to load preview. Maybe you have some warnings.') + '</p>');
          }
        }
      });
    }
  };
})(jQuery, Drupal);
