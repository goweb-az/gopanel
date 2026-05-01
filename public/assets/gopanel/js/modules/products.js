$(function () {
    var productEditors = [];

    function initProductEditors(context) {
        if (typeof ClassicEditor === 'undefined') {
            return;
        }

        $(context || document).find('.ckeditor').not('[data-ckeditor-initialized]').each(function () {
            var element = this;
            $(element).attr('data-ckeditor-initialized', 'true');

            ClassicEditor
                .create(element)
                .then(function (editor) {
                    element.ckeditorInstance = editor;
                    productEditors.push(editor);
                })
                .catch(function (error) {
                    console.error(error);
                });
        });
    }

    initProductEditors(document);

    $(document).on('submit', '#static-form', function () {
        productEditors.forEach(function (editor) {
            if (typeof editor.updateSourceElement === 'function') {
                editor.updateSourceElement();
            }
        });
    });
});
