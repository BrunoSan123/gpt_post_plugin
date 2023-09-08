jQuery(document).ready(function($) {
    $('#chatgpt-model-selector').change(function() {
        var selected_model = $(this).val();
        
        $.ajax({
            url: chatgpt_plugin_ajax.ajax_url,
            type: "POST",
            data: {
                action: 'save_model',
                model: selected_model
            },
            success: function(response) {
                if(response.success) {
                    console.log('Modelo salvo com sucesso');
                } else {
                    console.log('Erro ao salvar modelo:', response.data);
                }
            }
        });
    });
});
