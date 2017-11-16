jQuery(function ($) {
    var notice_template = wp.template('customizer-preview-for-demo-notice');
    var button_template = wp.template('customizer-preview-for-demo-button');
    var version         = parseFloat(CustomizerDemoPreview.wp_version)

    if (version < 4.9) {
        $('#save').remove();
        $('#customize-info').before(notice_template(CustomizerDemoPreview));
        $('#customize-header-actions > .spinner').before(button_template(CustomizerDemoPreview));
    }
    else {
        $('#save').remove();
        $('#publish-settings').remove();
        $('#customize-sidebar-outer-content').remove();
        $('#customize-info').before(notice_template(CustomizerDemoPreview));
        $('#customize-save-button-wrapper').html(button_template(CustomizerDemoPreview));
    }
});