jQuery(function ($) {

    var notice_template = wp.template('customizer-preview-for-demo-notice');
    var button_template = wp.template('customizer-preview-for-demo-button');

    $('#save').remove();
    $('#customize-info').before(notice_template());
    $('#customize-header-actions > .spinner').before(button_template(CustomizerDemoPreview));

});