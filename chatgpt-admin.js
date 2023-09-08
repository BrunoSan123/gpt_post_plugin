function recreate_text(link) {
    var postId = link.getAttribute('data-post-id');
    var loadingImage = link.nextElementSibling;

    // Mostrar imagem de carregamento
    loadingImage.style.display = 'inline';

    // Requisi«®«ªo AJAX
    jQuery.post(chatgpt_ajax_object.ajax_url, {
        action: 'chatgpt_recreate_text_ajax',
        security: chatgpt_ajax_object.ajax_nonce,
        post_id: postId
    }, function (response) {
        if (response.success) {
            alert('Texto recriado com sucesso.');
        } else {
            alert(response.data.message);
        }

        // Ocultar imagem de carregamento
        loadingImage.style.display = 'none';
    });
}



