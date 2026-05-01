$(function () {
    var serviceEditors = [];

    function initServiceEditors(context) {
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
                    serviceEditors.push(editor);
                })
                .catch(function (error) {
                    console.error(error);
                });
        });
    }

    initServiceEditors(document);

    var formWrap = document.getElementById('form-wrap');
    if (formWrap && window.MutationObserver) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                $(mutation.addedNodes).each(function () {
                    if (this.nodeType === 1) {
                        initServiceEditors(this);
                    }
                });
            });
        }).observe(formWrap, {childList: true, subtree: true});
    }

    $(document).on('change', '.service-icon-type', function () {
        var $form = $(this).closest('form');
        var isImage = $(this).val() === 'image';

        $form.find('.service-font-icon-wrap').toggle(!isImage);
        $form.find('.service-image-icon-wrap').toggle(isImage);
    });

    $(document).on('click submit', '#save-form-btn, #data-form', function () {
        serviceEditors.forEach(function (editor) {
            if (typeof editor.updateSourceElement === 'function') {
                editor.updateSourceElement();
            }
        });
    });
});
