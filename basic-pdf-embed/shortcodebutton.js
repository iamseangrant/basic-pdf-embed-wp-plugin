/**
 * This creates the PDF button on Tiny MCE when editing a post.
 *
 * Button then wraps the highlighted text (PDF URL) with the 
 * shortcode for displaying/embedding a PDF in <object> tag.
 */
(function() {
    tinymce.create('tinymce.plugins.pdfembed', {
        init : function(ed, url) {
            ed.addButton('pdfembed', {
                title : 'Insert Basic PDF Embed Shortcode',
                image : url+'/img/icon-pdf-mce.png',
                onclick : function() {
                     ed.selection.setContent('[pdfembed url="' + ed.selection.getContent() + '"]');
 
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('pdfembed', tinymce.plugins.pdfembed);
})();
